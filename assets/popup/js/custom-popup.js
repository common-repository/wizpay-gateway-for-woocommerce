jQuery(document).ready(function (){
    jQuery(".wizp-popup-open").click(function (){
        jQuery(".wizp-pop-outer").fadeIn("slow");
    });
    jQuery(".wizp-popup-close").click(function (){
        jQuery(".wizp-pop-outer").fadeOut("slow");
    });
});

jQuery(document.body).on('removed_from_cart updated_cart_totals', function () {
    jQuery(".wizp-popup-open").click(function (){
        jQuery(".wizp-pop-outer").fadeIn("slow");
    });
    jQuery(".wizp-popup-close").click(function (){
        jQuery(".wizp-pop-outer").fadeOut("slow");
    });
});
