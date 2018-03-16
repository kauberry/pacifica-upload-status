$(function() {
    setup_tree_data();
    setup_metadata_disclosure();
    display_ingest_status();
    cart_status();
    var tree = $(".tree_holder").fancytree("getTree");
    tree.visit(function(node){
        node.setExpanded();
    });

    // tree.on("fancyonloadchildren", function(event, data){
    //     data.node.visit(function(subNode){
    //         if( subNode.isUndefined() && subNode.isExpanded() ) {
    //             subNode.load();
    //         }
    //     });
    // });
});

var first_load = true;
var ingest_check_interval = 5000;
var display_ingest_status = function() {
    if(!ingest_complete){
        var ingest_url = base_url + "ajax_api/get_ingest_status/" + transaction_id;
        $.get(ingest_url, function(data){
            if(!data.upload_present_on_mds || first_load) {
                format_ingest_status(data);
                first_load = false;
            }else{
                setTimeout(function(){
                    window.location.href = base_url + "view/" + transaction_id;
                }, 2000);
            }
        });
    }
};

var format_ingest_status = function(status_object) {
    var ingest_block;
    var pgb;
    var mb = $("#message_block_" + transaction_id);
    if(status_object.state == "ok"){
        mb.html("Upload in progress...");
    }else{
        if(mb.find("p").length > 0){
            $("#message_block_" + transaction_id + " .error").remove();
        }
        mb.append($("<p>").addClass("error").html(status_object.message));
        return;
    }
    if($("#ingest_status_message_" + transaction_id).length === 0){
        ingest_block =
        $("<span class=\"ingest_status_message ingest_status_animate\" id=\"ingest_status_message_" + transaction_id + "\">")
            .append("<span class=\"message_text\">Starting Ingest Process</span>")
            .append(
                $("<div class=\"progressbar_ingest\" id=\"progressbar_ingest_" + transaction_id + "\">")
                    .append(
                        $("<div class=\"progressbar_label\">0%</div>")
                    )
            );
        $("#ingest_status_block_" + transaction_id)
            .append(ingest_block)
            .append("<span class=\"ingest_update_time\" id=\"ingest_update_time_" + transaction_id + "\" style=\"display:none;\">")
            .addClass("ingest_ok");
        pgb = $("#progressbar_ingest_" + transaction_id);
        pglabel = pgb.find(".progressbar_label");
        pgb.progressbar({
            value: 0,
            min: 0,
            max: 100,
            change: function() {
                pglabel.text(pgb.progressbar("value") + "%");
            },
            complete: function() {
                $("#message_block_" + transaction_id).html("");
                window.setTimeout(function(){
                    $("#ingest_status_block_" + transaction_id).fadeOut();
                    window.refresh();
                }, ingest_check_interval);
            }
        });
        $("#ingest_status_block_" + transaction_id).fadeIn("slow");
    }else{
        if(!$("#ingest_status_block_" + transaction_id).is(":visible")) {
            $("#ingest_status_block_" + transaction_id).fadeIn("slow");
        }
        ingest_block = $("#ingest_status_message_" + transaction_id);
        pgb = $("#progressbar_ingest_" + transaction_id);
        $("#ingest_status_message_" + transaction_id).find(".message_text").html(status_object.message);
        pgb = $("#progressbar_ingest_" + transaction_id);
        pgb.progressbar("option", "value", status_object.overall_percentage);
        var tz_name = moment.tz.guess();
        var update_time = moment(status_object.updated + "-0000").tz(tz_name);
        if(!$("#ingest_update_time_" + transaction_id).hasClass("loaded_ingest_update_time")) {
            $("#ingest_update_time_" + transaction_id)
                .addClass("loaded_ingest_update_time")
                .fadeIn();
        }
        $("#ingest_update_time_" + transaction_id)
            .html(update_time.format("MMMM Do YYYY, h:mma"));
        if(status_object.state != "ok") {
            $("#ingest_status_block_" + transaction_id).removeClass("ingest_ok").addClass("ingest_error");
            $("#ingest_status_message_" + transaction_id).removeClass("ingest_status_animate");
            pgb.progressbar("disable");
        }else{
            $("#ingest_status_block_" + transaction_id).addClass("ingest_ok").removeClass("ingest_error");
            $("#ingest_status_message_" + transaction_id).addClass("ingest_status_animate");
            pgb.progressbar("enable");
        }
    }

};
