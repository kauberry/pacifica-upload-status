var trans_id_list = {};
var latest_tx_id = 0;
var inst_id = 0;
var initial_load = true;

var spinner_opts = {
    lines: 9, // The number of lines to draw
    length: 4, // The length of each line
    width: 2, // The line thickness
    radius: 4, // The radius of the inner circle
    corners: 1, // Corner roundness (0..1)
    rotate: 0, // The rotation offset
    direction: 1, // 1: clockwise, -1: counterclockwise
    color: '#111', // #rgb or #rrggbb or array of colors
    speed: 1, // Rounds per second
    trail: 60, // Afterglow percentage
    shadow: false, // Whether to render a shadow
    hwaccel: true, // Whether to use hardware acceleration
    className: 'spinner', // The CSS class to assign to the spinner
    zIndex: 2e9, // The z-index (defaults to 2000000000)
    top: '50%', // Top position relative to parent
    left: '50%' // Left position relative to parent
};




$(
    function(){
        update_breadcrumbs();
    }
);

var update_breadcrumbs = function(){
    if($('.bar_holder')) {
        inst_id = $('#instrument_selector').length > 0 ? $('#instrument_selector').val() : initial_inst_id;
        $('.bar_holder').each(
            function(index,el){
                var pattern = /\w+_\w+_(\d+)/i;
                var m = $(el).prop('id').match(pattern);
                var this_tx_id = parseInt(m[1],10);
                latest_tx_id = this_tx_id > latest_tx_id ? this_tx_id : latest_tx_id;
                if(!(this_tx_id in trans_id_list) || (trans_id_list[this_tx_id].length) == 0) {
                    var hash = "";
                    if(!$(el).html().length == 0) {
                        var hash = $(el).crypt({method:"sha1"});
                    }
                    trans_id_list[this_tx_id] = hash;
                }
            }
        );
        var data_obj = {
            'item_list' : trans_id_list,
            'instrument_id' : inst_id
        };
        if(inst_id && Object.keys(trans_id_list).length > 0) {
            var ts = moment().format('YYYYMMDDHHmmss');
            var url = '/status/get_status/' + lookup_type + '/bc_' + ts;
            $.ajax(
                {
                    type: "POST",
                    url: url,
                    data: data_obj,
                    success: function(data){
                        if(data != 0) {
                            $.each(
                                data, function(index,trans_entry){
                                    var new_item = $('#bar_holder_' + index);
                                    new_item.html(trans_entry.bar_text);
                                    var new_tx_id = trans_entry.transaction_id;
                                    var current_step = trans_entry.current_step;
                                    if(lookup_type == 'j' && new_tx_id != null && current_step >= 5) {
                                        window.location = "/view/" + new_tx_id;
                                    }
                                    if(!new_item.html().length == 0 || (trans_id_list[new_tx_id].length) == 0) {
                                        var hash = new_item.crypt({method:"sha1"});
                                    }

                                    trans_id_list[index] = hash;
                                }
                            );
                            setup_hover_info();
                        }
                    },
                    dataType: 'json'
                }
            );
        }
    }

};

var get_latest_transactions = function(){
    if(initial_instrument_id && latest_tx_id) {
        var ts = moment().format('YYYYMMDDHHmmss');
        var new_tx_url = '/status/get_latest_transactions/' + initial_instrument_id + '/' + initial_proposal_id + '/' + latest_tx_id + '/glt_' + ts;
        $.get(
            new_tx_url, function(data){
                if(data.length > 0) {
                    $('#item_info_container').prepend(data);
                    setup_tree_data();
                    setup_metadata_disclosure();
                }
            }
        );
    }
};

var update_content = function(event){
    // debugger;
    var proposal_id = $('#proposal_selector').val() != null ? $('#proposal_selector').val() : initial_proposal_id;
    var instrument_id = $('#instrument_selector').val() != null ? $('#instrument_selector').val() : initial_instrument_id;
    var time_frame = $('#timeframe_selector').val() != null ? $('#timeframe_selector').val() : 0;
    if($('#proposal_selector').val() == null){
        if(initial_proposal_id != null){
            $('#proposal_selector').val(initial_proposal_id).trigger('change');
        }else{
            $('#instrument_selector').val('').trigger('change');
        }
    }
    // debugger;
    var el = null;
    if(event != null) {
        el = $(event.target);
        if(!initial_load){
            if(el.prop('id') == 'instrument_selector' && el.val() == initial_instrument_id) {
                return false;
            }
            if(['proposal_selector','instrument_selector','timeframe_selector'].indexOf(el.prop('id')) >= 0) {
                $.cookie('myemsl_status_last_' + el.prop('id'), el.val(),{ expires: 30, path: '/' });
            }
        }else{
            if(initial_instrument_id > 0){
                $('#instrument_selector').val(initial_instrument_id);
            }
        }
    }
    initial_load = false;
    var ts = moment().format('YYYYMMDDHHmmss');
    if(proposal_id && instrument_id && time_frame) {
        var url = '/status_api/overview/' + proposal_id + '/' + instrument_id + '/' + time_frame + '/ovr_' + ts;
        initial_load = false;
        $('#item_info_container').hide();
        $('#loading_status').fadeIn(
            "slow", function(){
                $('.criterion_selector').off("change");
                var getting = $.get(url);
                getting.done(
                    function(data){
                        // debugger;
                        if(data) {
                            $('#loading_status').fadeOut(
                                200,function(){
                                    $('#item_info_container').html(data);
                                    $('#item_info_container').fadeIn(
                                        'slow',function(){
                                            setup_tree_data();
                                            setup_metadata_disclosure();
                                            setup_hover_info();
                                        }
                                    );
                                }
                            );
                        }
                    }
                );
                getting.fail(
                    function(jqxhr,textStatus,error){
                        $('#loading_status').fadeOut(
                            200,function(){
                                $('#info_message_container h2').html("An Error occurred during refresh");
                                $('#info_message_container').append("<span class='fineprint'>" + error + "</span>");
                                $('#info_message_container').show();
                            }
                        );
                    }
                );
                getting.always(
                    function(){
                        $('.criterion_selector').change(update_content);
                    }
                );
            }
        );
    }
    if(el && el.prop('id') == 'proposal_selector') {
        // debugger;
        //check to see if instrument list is current
        // if(el.val() != initial_proposal_id) {
        get_instrument_list(el.val());
        initial_proposal_id = el.val();
        // }
    }
    if(el) {
        var identifier = el.prop('id').replace('_selector','');
        var initial_ident = 'initial_' + identifier + '_id';
        if(el.val()) {
            eval(initial_ident + ' = ' + el.val());
        }else{
            eval(initial_ident + ' = 0');
        }
    }
};

var get_instrument_list = function(proposal_id){
    var inst_url = '/ajax_api/get_instruments_for_proposal/' + proposal_id;
    var target = document.getElementById('instrument_selector_spinner');
    var spinner = new Spinner(spinner_opts).spin(target);
    $('#instrument_selector').empty();
    $.getJSON(
        inst_url,function(data){
            $('#instrument_selector').select2(
                {
                    data: data.items,
                    placeholder: "Select an Instrument...",
                    templateResult: formatInstrument,
                    templateSelection: formatInstrumentSelection,
                    matcher: my_matcher,
                    escapeMarkup: function(markup) {
                        return markup;
                    }
                }
            );
            $('#instrument_selector').enable();
            initial_instrument_list = [];

            $.each(
                data.items, function(index,item){
                    initial_instrument_list.push(item.id);
                }
            );
            // debugger;
            spinner.stop();
            if(initial_instrument_list.indexOf(parseInt(initial_instrument_id,10)) < 0) {
                $('#instrument_selector').val('').trigger('change');
            }else{
                $('#instrument_selector').val(parseInt(initial_instrument_id,10)).trigger('change');
                update_content();
            }
        }
    );
};

var formatInstrument = function(item){
    if (item.loading) return item.text;
    var markup = false;
    var current_proposal_id = $('#proposal_selector').val();
    var active = item.active == 'Y' ? 'active' : 'inactive';
    if(item.id) {
        if(item.id > 0) {
            markup =  "<div id='inst_info_" + item.id + "' class='inst_info'>";
            markup += "  <div class='" + active + "_instrument'>";
            markup += "     <strong>Instrument " + item.id + "</strong>";
            markup += "  </div>";
            markup += "  <div class='inst_description'>" + item.name + "</div>";
            markup += "</div>";
        }else if (item.id == -1) {
            markup = "<div id='inst_info_" + item.id + "' class='inst_info'>";
            markup += "<strong>All Instruments for Proposal " + current_proposal_id + "</strong>";
            markup += "</div>"
        }
    }

    return markup;
};

var formatInstrumentSelection = function(item){
    var markup = "Select an Instrument...";
    var current_proposal_id = $('#proposal_selector').val();
    if(item.id > 0) {
        markup = item.text;
    }else if(item.id < 0) {
        markup = "All Instruments for Proposal " + current_proposal_id;
    }
    return markup;
};

var setup_metadata_disclosure = function(){
    $('ul.metadata_container').hide();
    $('.disclosure_button').unbind('click').click(
        function(){
            var el = $(this);
            var container = el.parentsUntil('div').siblings('ul.metadata_container');
            if(el.hasClass('dc_up')) {
                //view is rolled up and hidden
                el.removeClass('dc_up').addClass('dc_down');
                container.slideDown(200);
            }else if(el.hasClass('dc_down')) {
                //view is open and visible
                el.removeClass('dc_down').addClass('dc_up');
                container.slideUp(200);

            }else{

            }
        }
    );

};

var setup_tree_data = function(){
    $('.tree_holder').each(
        function(index, el){
            if($(el).find('ul.ui-fancytree').length == 0) {
                var el_id = $(el).prop('id').replace('tree_','');
                $(el).fancytree(
                    {
                        checkbox:true,
                        selectMode: 3,
                        activate: function(event, data){

                        },
                        select: function(event, data){
                            var dl_button = $(event.target).parent().find('#dl_button_container_' + el_id);
                            var tree = $(el).fancytree('getTree');
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
                                url: '/status/get_lazy_load_folder',
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

var setup_hover_info = function(){
    $('.status_item_bar').each(
        function(index,item){
            var el = $(item);
            var block_type = 'status_block_' + el.prop('id').slice(-1);
            var status = el.hasClass('red_bar_end') ? 'success' : 'failure';
            var info = item_info_obj[block_type][status];
            el.attr('title',item_info_obj[block_type]['title']);
        }
    );
    $('.status_item_bar').tooltip({show:true,position: { my: "right top+10", at: "right center" }});

};

var item_info_obj = {
    'status_block_0' : {
        'title' : 'Data Submitted',
        'success' : 'Your data has been sent to MyEMSL and is being examined',
        'failure' : 'Fail Whale!'
    },
    'status_block_1' : {
        'title':'Data Received',
        'success':'Your data has been received by the MyEMSL servers and is being processed',
        'failure':'Fail Whale!'
    },
    'status_block_2' : {
        'title':'Data Being Processed',
        'success': 'Your data has been accepted by the MyEMSL system and is being verified for consistency',
        'failure':'Fail Whale!'
    },
    'status_block_3' : {
        'title':'Data Verified',
        'success': 'Your data has been verified and is being moved to working storage',
        'failure':'Fail Whale!'
    },
    'status_block_4' : {
        'title':'Data Stored',
        'success':'Your data has been moved to working storage and is being readied for access',
        'failure':'Fail Whale!'
    },
    'status_block_5' : {
        'title':'Data Available',
        'success':'Your data has been fully processed by the MyEMSL system and is available for use (Note: Until the data archiving step has completed, only one copy of your data exists on our servers)',
        'failure':'Fail Whale!'
    },
    'status_block_6' : {
        'title':'Data Archived',
        'success':'Your data has been successfully copied to the EMSL archive system for safekeeping',
        'failure':'Fail Whale!'
    }
};


var get_tree_data = function(event, data){
    var id_matcher = /.+_(\d+)/i;
    var m = data.node.key.match(id_matcher);
    var trans_id = parseInt(m[1],10);
    // setup_file_download_links($(el));
    // var url =
};

function my_matcher(params, data){
    // Always return the object if there is nothing to compare
    // data.text should only be blank for the placeholder, return the item
    if ($.trim(params.term) === '' || $.trim(data.text) === '') {
        return data;
    }

    var original = data.text.toUpperCase();
    var termstring = params.term.toUpperCase();

    var terms = termstring.split(' ');
    terms = $.isArray(terms) ? terms : [terms];
    var is_match = false;

    $.each(
        terms, function(index, term){
            // Check if the text contains the term
            if (original.indexOf(term) > -1) {
                is_match = true;
            }else{
                is_match = false;
            }
            return is_match;
        }
    );

    if(is_match === true) {
        return data;
    }

    // If it doesn't contain the term, don't return anything
    return null;

}
