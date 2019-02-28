var max_size = 1024 * 1024 * 1024 * 1024 * 5; //5 TB (base 2)
var friendly_max_size = "";
var exceed_max_size_allow = false;
var cart_create_dialog, cart_create_form, cart_auth_dialog;

$(function(){
    cart_create_dialog = $("#cart-create-dialog-form").dialog({
        autoOpen: false,
        width:"40%",
        classes: {"ui-dialog": "drop_shadow_dialog"},
        modal:true,
        buttons: [
            {
                "text": "Create",
                "click": function(){
                    f = $(this).find("form");
                    var req_fields = f.find(".required");
                    var empty_req_fields = req_fields.filter(function(){
                        return ( $(this).val() == "" );
                    });
                    if(empty_req_fields.length > 0){
                        return false;
                    }else{
                        //all req'd fields filled out
                        cart_create_dialog.dialog("close");
                        var tree_container = $("#" + f.find("#current_transaction_container").val());
                        create_cart(f.serializeFormJSON(), tree_container);
                    }
                }
            },
            {
                "text": "Cancel",
                "click": function() {
                    cart_create_form[0].reset();
                    cart_create_dialog.dialog("close");
                }
            }
        ],
        close: function() {
        }

    });
    cart_auth_dialog = $("#cart-download-auth-dialog").dialog({
        autoOpen: false,
        width:"25%",
        classes: {"ui-dialog": "drop_shadow_dialog"},
        modal:true,
        buttons: [
            {
                "text": "Ok",
                "click": function(){
                    var redir_url = $(this).data("redirect_url");
                    var this_page = window.location.href;
                    window.location.href = redir_url + "?redirectUrl=" + this_page;
                }
            },
            {
                "text": "Cancel",
                "click": function(){
                    var tree_name = $(this).data("tree_obj");
                    var tree = $("#" + tree_name).fancytree("getTree");
                    tree.visit(function(node){
                        node.setSelected(false);
                    });
                    cart_auth_dialog.dialog("close");
                }
            }
        ],
        close: function() {
        }

    });

    window.setInterval(cart_status, 30000);

    cart_create_form = cart_create_dialog.find("form").on("submit", function(event){
        event.preventDefault();
    });
    cart_status();
});

var setup_file_download_links = function(parent_item) {
    parent_item = $(parent_item);
    var tx_id = parent_item.prop("id").replace("tree_","");
    var file_object_collection = parent_item.find(".item_link");
    file_object_collection.off("click").on("click",
        function(e) {
            var file_object_data = JSON.parse($(e.target).siblings(".item_data_json").html());
            file_object_data.name = escape(file_object_data.name);
        }
    );
    var dl_button = $("#dl_button_" + tx_id);
    dl_button.off("click").on("click",
        function(){
            cart_download(parent_item);
        }
    );

};

var update_header_user_info = function(user_info){
    var new_user_string = "<em>" + user_info.full_name + " (" + user_info.eus_id + ")</em>";
    $("#login_id_container").html(new_user_string);
};

var check_download_authorization = function(event){
    var getter = $.get(cart_download_auth_url);
    getter.done(function(data){
        proxied_user_id = data.eus_id;
        if(proxied_user_id){
            update_header_user_info(data);
            setup_download_cart_button(event, data);
        }else{
            $("#cart-download-auth-dialog")
                .data("redirect_url", data.redirect_url)
                .data("tree_obj", $(event.target).prop("id"))
                .dialog("open");
        }
    });
    getter.fail(function(jqxhr){
        var response_obj = JSON.parse(jqxhr.responseText);
        $("#cart-download-auth-dialog")
            .data("redirect_url", response_obj.redirect_url)
            .data("tree_obj", $(event.target).prop("id"))
            .dialog("open");
    });
};

var generate_cart_identifier = function(){
    if(!localStorage.getItem("cart_identifier")){
        localStorage.setItem("cart_identifier",
            $().crypt({
                method: "sha1",
                source: moment().toISOString() + Math.random().toString()
            })
        );
    }
    return localStorage.getItem("cart_identifier");
};

var cart_download = function(transaction_container){
    var selected_files = get_selected_files(transaction_container);
    //check for token
    var item_id_list = Object.keys(selected_files.sizes);
    $("#cart_file_list").val(JSON.stringify(item_id_list));
    $("#current_transaction_container").val(transaction_container.prop("id"));
    cart_create_dialog.dialog("open");
};

var create_cart = function(submission_object, transaction_container){
    submission_object["files"] = JSON.parse(submission_object["files"]);
    $.post(
        cart_create_url, JSON.stringify(submission_object)
    )
        .done(
            function(data){
                if(!data.success){
                    //looks like we had an error
                    alert(data.message);
                }
                cart_status();
            }
        )
        .fail(
            function(jqxhr){
                var msg_string = jqxhr.responseJSON.message;
                alert("A problem occurred creating your cart.\n[" + msg_string + "]");
            }
        )
        .always(
            function(){
                unselect_nodes(transaction_container.fancytree("getTree"));
            }
        );
};

var cart_status = function(){
    var getter = $.get(cart_info_url);
    getter.done(function(data){
        $("#cart_listing").html(data);
        if(data.trim().length == 0){
            $("#cart_listing_container").hide();
        }else{
            $("#cart_listing_container").show();
        }
    });
    getter.fail(function(jqxhr, status, error){});
};


var cart_delete = function(cart_uuid){
    if (cart_uuid == null) {
        return;
    }
    var url = cart_delete_url + "/" + cart_uuid;
    $.ajax(
        {
            url : url,
            type : "DELETE",
            processData : false,
            dataType : "text"
        }
    )
        .done(
            function(data){
                //check how many rows are left
                // $("#cart_line_" + cart_id).remove();
                cart_status();
            }
        )
        .fail(
            function(jq, textStatus, errormsg){
                var error = errormsg;
            }
        );
};

var get_cart_count = function(){
    var cart_count = $(".cart_line").length;
    if(cart_count > 0) {
        if($("#cart_listing_container:visible").length == 0) {
            $("#cart_listing_container").slideDown("slow");
        }
    }else{
        if($("#cart_listing_container:visible").length > 0) {
            $("#cart_listing_container").slideUp("slow");
        }
    }
};

var get_selected_files = function(tree_container){
    if(typeof(tree_container) == "string") {
        tree_container = $("#" + tree_container);
    }
    var tree = tree_container.fancytree("getTree");
    var selCount = tree.countSelected();
    var selFiles = [];
    if(selCount > 0) {
        var topNode = tree.getRootNode();
        var dataNode = topNode.children[0];
        //check lazyload status and load if necessary
        if(selCount == 1 && !dataNode.isLoaded()) {
            dataNode.load()
                .done(
                    function(){
                        dataNode.render(true,true);
                        selCount = tree.countSelected();
                        selFiles = get_file_sizes(tree_container);
                        update_download_status(tree_container, selCount);
                        return selFiles;
                    }
                );
        }else{
            selFiles = get_file_sizes(tree_container);
            update_download_status(tree_container, selCount);
            return selFiles;
        }
    }else{
        selFiles = get_file_sizes(tree_container);
        update_download_status(tree_container, selCount);
    }
};

var unselect_nodes = function(tree_container){
    tree_container.visit(function(node){ node.setSelected(false); });
};

var get_file_sizes = function(tree_container){
    var tree = tree_container.fancytree("getTree");
    var total_size = 0;
    var sizes = {};
    var item_info = {};

    var item_id_list = $.map(
        tree.getSelectedNodes(), function(node){
            if(!node.folder) {
                return parseInt(node.key.replace("ft_item_",""),10);
            }
        }
    );


    tree.render(true,true);
    $.each(
        item_id_list, function(index,item){
            item_info = JSON.parse($("#item_id_" + item).html());
            sizes[item] = item_info.size;
            total_size += parseInt(item_info.size,10);
        }
    );
    var message_container = tree_container.parents(".transaction_container").find(".error");
    friendly_max_size = friendly_max_size.length == 0 ? myemsl_size_format(max_size) : friendly_max_size;
    var friendly_total_size = myemsl_size_format(total_size);
    var dl_button = tree_container.parents(".transaction_container").find(".dl_button");
    var mc_html = "";
    dl_button.show();
    if(total_size > max_size) {
        mc_html = "<div style=\"text-align:center;\">";
        mc_html += "<p>The total size of the files you have selected";
        mc_html += " (" + friendly_total_size + ") ";
        mc_html += "is greater than the ";
        mc_html += friendly_max_size;
        mc_html += " limit <br>imposed for unrestricted downloads from the system.</p>";
        if(exceed_max_size_allow) {
            mc_html += "<p>Downloads exceeding the size cutoff are allowed, ";
            mc_html += "but will be placed in an <em>Administrative Hold</em> state ";
            mc_html += "pending approval from a MyEMSL administrator</p>";
            mc_html += "</div>";
            dl_button.enable();
        }else{
            dl_button.disable();
        }
        message_container.html(mc_html).parent().show();
    }else{
        dl_button.enable();
        message_container.html("").parent().hide();
    }
    return {"total_size" : total_size, "sizes" : sizes};
};


var update_download_status = function(tree_container, selectCount){
    var el_id = $(tree_container).prop("id").replace("tree_","");
    var dl_button = $("#dl_button_container_" + el_id);
    if(selectCount > 0) {
        var fileSizes = get_file_sizes(tree_container);
        var totalSizeText = myemsl_size_format(fileSizes.total_size);
        var pluralizer = Object.keys(fileSizes.sizes).length != 1 ? "s" : "";
        $("#status_block_" + el_id).html(Object.keys(fileSizes.sizes).length + " file" + pluralizer + " selected [" + totalSizeText + "]");
        dl_button.slideDown();
    }else{
        $("#status_block_" + el_id).html("&nbsp;");
        dl_button.slideUp();
    }
};

var setup_metadata_disclosure = function(){
    $("ul.metadata_container").hide();
    $(".disclosure_button").off("click").on("click",
        function(){
            var el = $(this);
            var container = el.parentsUntil("div").siblings(".metadata_container");
            if(el.hasClass("dc_up")) {
                //view is rolled up and hidden
                el.removeClass("dc_up").addClass("dc_down");
                container.slideDown(200);
            }else {
                //view is open and visible
                el.removeClass("dc_down").addClass("dc_up");
                container.slideUp(200);
            }
        }
    );

};

var setup_download_cart_button = function(event){
    var el = $(event.target);
    var dl_button = el.parent().find(".dl_button_container");
    var tree = el.fancytree("getTree");
    // var fileSizes = get_file_sizes($(el));
    var topNode = tree.getRootNode();
    var dataNode = topNode.children[0];
    var fileSizes = get_selected_files($(el));
    if(fileSizes != null) {
        var totalSizeText = myemsl_size_format(fileSizes.total_size);
        var selectCount = Object.keys(fileSizes.sizes).length;
    }
};

var setup_tree_data = function(){
    $(".tree_holder").each(
        function(index, el){
            if($(el).find("ul.ui-fancytree").length == 0) {
                var el_id = $(el).prop("id").replace("tree_","");
                $(el).fancytree(
                    {
                        checkbox:true,
                        selectMode: 3,
                        activate: function(event, data){

                        },
                        select: function(event, data){
                            if(data.node.selected){
                                var user_id_string = check_download_authorization(event);
                                if(!user_id_string){
                                    return false;
                                }
                            }else{
                                setup_download_cart_button(event);
                            }
                        },
                        keydown: function(event, data){
                            if(event.which === 32) {
                                data.node.toggleSelected();
                                return false;
                            }
                        },
                        lazyLoad: function(event, data){
                            var node = data.node;
                            data.result = {
                                url: base_url + "file_tree",
                                data: {mode: "children", parent: node.key},
                                method:"POST",
                                cache: false,
                                complete: function(xhrobject, status){
                                    setup_file_download_links($(el));
                                }
                            };
                        },
                        loadChildren: function(event, ctx) {
                            ctx.node.fixSelection3AfterClick();
                        },
                        expand: function(event, data){
                            setup_file_download_links($(el));
                        },
                        cookieId: "fancytree_tx_" + el_id,
                        idPrefix: "fancytree_tx_" + el_id + "-"
                    }
                );
            }
        }
    );
};

var myemsl_size_format = function(bytes) {
    var suffixes = ["B", "KB", "MB", "GB", "TB", "EB"];
    if (bytes == 0) {
        suffix = "B";
    } else {
        var order = Math.floor(Math.log(bytes) / Math.log(10) / 3);
        bytes = (bytes / Math.pow(1024, order)).toFixed(1);
        suffix = suffixes[order];
    }
    return bytes + " " + suffix;
};

var cart_url_base = base_url;
var cart_identifier = generate_cart_identifier();
var cart_info_url = cart_url_base + "cart/listing/" + cart_identifier;
var cart_create_url = cart_url_base + "cart/create/" + cart_identifier;
var cart_delete_url = cart_url_base + "cart/delete/" + cart_identifier;
var cart_download_auth_url = cart_url_base + "cart/checkauth";
var proxied_user_id = null;
