jQuery(document).ready(function(){

    function getParameterByName(name, url = window.location.href) {
        name = name.replace(/[\[\]]/g, '\\$&');
        var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }
    
    // For ajax coll on capture button click and display the input capture screen

    jQuery('#capture_charge').on('click',function(e){
        var order_id = getParameterByName('post');
        jQuery.ajax({
            url: ajaxurl,
            type : "POST",
            data: {
                'Content-Type': 'application/json',
                'action':'get_pending_capture_amount',
                'order_id':order_id
            },
            success:function(data) {
                // This outputs the result of the ajax request
                console.log(data);
               var totalorderamount = jQuery("#capture_all_amount").val();
               var alreadycaptureamount = totalorderamount - data;
               if(data == '00.00') {

                    jQuery("#already_capture").text('$'+alreadycaptureamount);
                    jQuery("#capture_avail").text('$'+data);
               } else {
                    jQuery("#already_capture").text('$'+alreadycaptureamount);
                    jQuery("#capture_avail").text('$'+data);
               }
               
               //jQuery("#capture_avail").val('$'+data);
            },
            error: function(errorThrown){
                console.log(errorThrown);
                alert(data);
            }
        });
        jQuery(".wc-order-capture-items, .wc-order-totals-items, .add-items").toggle();

        e.preventDefault();
    });                  

    jQuery("#cancelaction").click(function(e){
        e.preventDefault();
        jQuery(".wc-order-capture-items, .wc-order-totals-items, .add-items").toggle();
        
    });

    // Validate the input capture value

     jQuery("#capture_amount").keyup(function() { 
        var priceval = jQuery(this).val();
        var first = jQuery("#capture_avail").text();
        var newString = first.replace('$', '');
        
        if( priceval > parseFloat( newString ) || priceval < 0 ){
            jQuery("#capture_error").text('Specify a valid amount less or equal to pending amount');
        } else {
            jQuery("#capture_error").text('');
            jQuery("#priceapi").text('Capture Charge $'+ priceval);
        }

    });

     // For ajax call on click capture button and call WZ partial API
    jQuery("#priceapi").click(function(e){
        var order_id = getParameterByName('post');
        var captureamount = jQuery("#capture_amount").val();
        var capture_reason = jQuery("#capture_reason").val();
        var capture_avail = jQuery("#capture_avail").text();
        var capture_avail_new = capture_avail.replace('$', '');
        jQuery.ajax({
            url: ajaxurl,
            type : "POST",
            data: {
                'Content-Type': 'application/json',
                'action':'merchant_autherised_to_capture_amount',
                'order_id':order_id,
                'captureamount':captureamount,
                'capture_reason':capture_reason,
                'capture_avail_new':capture_avail_new
            },
            success:function(data) {
                //console.log(data);
                //console.log(data);
                //alert(data);
                location.reload();
            },
            error: function(errorThrown){
                console.log(errorThrown);
                alert(data);
            }
        });

        e.preventDefault();
    });
});