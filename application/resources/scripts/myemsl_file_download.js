var cart_url_base = base_url + "cart_api";
var cart_info_url = cart_url_base + "/listing/";
var cart_create_url = cart_url_base + "/create/";
var cart_delete_url = cart_url_base + "/delete/";
var max_size = 1024 * 1024 * 1024 * 50; //50 GB (base 2)
var friendly_max_size = "";
var exceed_max_size_allow = false;
var cart_create_dialog, cart_create_form;

$(function(){
    cart_create_dialog = $("#cart-create-dialog-form").dialog({
        autoOpen: false,
        width:"90%",
        modal:true,
        buttons: {
            "Create": function(){
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
                    create_cart(f.serializeFormJSON());
                }
            },
            Cancel: function() {
                cart_create_form[0].reset();
                cart_create_dialog.dialog("close");
            }
        },
        close: function() {
        }

    });
    window.setInterval(cart_status, 30000);

    cart_create_form = cart_create_dialog.find("form").on("submit", function(event){
        event.preventDefault();
    });
    cart_status();
});

// var createCart = function(event){
//
// };

var setup_file_download_links = function(parent_item) {
    parent_item = $(parent_item);
    var tx_id = parent_item.prop("id").replace("tree_","");
    var file_object_collection = parent_item.find(".item_link");
    file_object_collection.off("click").click(
        function(e) {
            var file_object_data = JSON.parse($(e.target).siblings(".item_data_json").html());
            file_object_data.name = escape(file_object_data.name);
            download_myemsl_item(file_object_data);
        }
    );
    var dl_button = $("#dl_button_" + tx_id);
    dl_button.unbind("click").click(
        function(){
            cart_download(parent_item);
        }
    );

};


var cart_download = function(transaction_container){
    var selected_files = get_selected_files(transaction_container);
    //check for token
    var item_id_list = Object.keys(selected_files.sizes);
    $("#cart_file_list").val(JSON.stringify(item_id_list));
    cart_create_dialog.dialog("open");
};

var create_cart = function(submission_object){
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
            function(jqxhr, error, message){
                alert("A problem occurred creating your cart.\n[" + message + "]");
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
    var url = cart_delete_url + cart_uuid;
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
                        update_download_status(tree_container,selCount);
                        return selFiles;
                    }
                );
        }else{
            selFiles = get_file_sizes(tree_container);
            update_download_status(tree_container,selCount);
            return selFiles;
        }
    }else{
        selFiles = get_file_sizes(tree_container);
        update_download_status(tree_container,selCount);
    }
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
        dl_button.slideDown("slow");
    }else{
        $("#status_block_" + el_id).html("&nbsp;");
        dl_button.slideUp("slow");
    }

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

var setup_metadata_disclosure = function(){
    $("ul.metadata_container").hide();
    $(".disclosure_button").unbind("click").click(
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
                            var dl_button = $(event.target).parent().find("#dl_button_container_" + el_id);
                            var tree = $(el).fancytree("getTree");
                            // var fileSizes = get_file_sizes($(el));
                            var topNode = tree.getRootNode();
                            var dataNode = topNode.children[0];
                            var fileSizes = get_selected_files($(el));
                            if(fileSizes != null) {
                                var totalSizeText = myemsl_size_format(fileSizes.total_size);
                                var selectCount = Object.keys(fileSizes.sizes).length;
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
                                url: "/status_api/get_lazy_load_folder",
                                data: {mode: "children", parent: node.key},
                                method:"POST",
                                cache: false,
                                complete: function(xhrobject,status){
                                    setup_file_download_links($(el));
                                }
                            };
                        },
                        loadChildren: function(event, ctx) {
                            ctx.node.fixSelection3AfterClick();
                        },
                        expand: function(event,data){
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
