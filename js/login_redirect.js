jQuery(document).ready(function($) {
   
    $('.redir_settings').accordion();
   
   $('.newroles').click(function(){
       $(this).hide();
   })
   
    // autocomplete for blog posts
    $("input.redirect_input").autocomplete({
        source: function( request, response ) {
                $.getJSON( ajaxurl, {
                        term: request.term,
                        action: 'suggest_redirect'
                }, response);
        },
        minLength: 2,
        select: function( event, ui) {
            var apn = $(this).attr('data-apn') + "[inner]";
            var id_hidden = $(this).attr('id') + "_hidden";
            if( $("#" + id_hidden).length ) {
                $("#" + id_hidden).attr('value', ui.item.id);
            } else {
                if ($(this).attr('id') != 'global_redirect') {
                    $(this).parent().append('<input type="hidden" id="'+id_hidden+'" value="'+ui.item.id+'" name="'+apn+'" />');
                } else {
                    $(this).parent().append('<input type="hidden" id="'+id_hidden+'" value="'+ui.item.id+'" name="global_redirect_inner" />');
                }
            }
        }
    });   
    
    // fade out colour 
    $("input.redirect_input").focus(function() {
       if ($(this).hasClass('new')) {
            $(this).parent().parent().animate({backgroundColor: '#f8f8f8'}, 'slow');
            $(this).parent().parent().prev().animate({backgroundColor: '#e2e2e2'}, 'slow');
       } 
    });
    
    function process_response(response, detail) {
        
        alert(response);
        
        var new_response = new Array();
        var i;
        
        for (i = 0; i < response.length; i++) {
            new_response[i] = response[i][detail];
        }
        
        response = new_response;
    }
    
});