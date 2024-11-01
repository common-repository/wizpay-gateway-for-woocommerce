jQuery(document).ready(function () {

    wizpayHideProductionEnviromentModelDescription();


    jQuery("#woocommerce_wizpay_environment_mode").change(function () {
        wizpayHideProductionEnviromentModelDescription();
    });

    jQuery("#wizardpayCustRestoreBtn").click(function () {
        if (window.confirm('Customisations have now been reset to defaults. Please review and click "Save Changes" to accept the new values.')) {
            if (tinymce.get('payment_info_on_product_text')) {
                tinymce.get('payment_info_on_product_text').setContent(wizp_def_payment_info_on_product_text);
            }

            jQuery('#payment_info_on_product_text').val(wizp_def_payment_info_on_product_text);

            jQuery('#woocommerce_wizpay_payment_info_on_product_hook').val(wizp_def_payment_info_on_product_hook);
            jQuery('#woocommerce_wizpay_payment_info_on_product_hook_priority').val(wizp_def_payment_info_on_product_hook_priority);

            jQuery('#woocommerce_wizpay_payment_info_on_cart_text').val(wizp_def_payment_info_on_cart_text);

            if (tinymce.get("payment_info_on_product_cat_text")) {
                tinymce.get("payment_info_on_product_cat_text").setContent(wizp_def_payment_info_on_product_cat_text);
            }

            jQuery("#payment_info_on_product_cat_text").val(wizp_def_payment_info_on_product_cat_text);

            jQuery("#woocommerce_wizpay_payment_info_on_product_cat_hook").val(wizp_def_payment_info_on_product_cat_hook);
            jQuery('#woocommerce_wizpay_payment_info_on_product_cat_hook_priority').val(wizp_def_payment_info_on_product_cat_hook_priority);
        }
    });



    // for admin notice function
    jQuery('#wizpay-review-later-btn').click(function () {
        jQuery('.wizpay-plugin-rating-admin-notices').hide();
    });
    jQuery("#wizpay-review-did-btn").click(function () {
        jQuery.ajax({
            url: ajaxurl,
            data: {
                action: 'wizpay_plugin_rating_did_callback'
            }
        });

        jQuery('.wizpay-plugin-rating-admin-notices').hide();
    });

});

var wizp_def_payment_info_on_product_text = 'or 4 payments [OF_OR_FROM] [AMOUNT] with Wizpay';
var wizp_def_payment_info_on_product_cat_text = 'or 4 payments [OF_OR_FROM] [AMOUNT] with Wizpay';
var wizp_def_payment_info_on_cart_text = '<div class="wizp-cart-custom-message">[wizpay_logo] <span  style = "vertical-align: super;font-size: 16px;font-weight: normal;padding-left: 20px;"> 4 x fortnightly payments of $ <? php echo esc_attr(number_format($install, 2)); ?> <a target="_blank" class="wizp-popup-open" style="font-size: 12px;text-decoration: underline;">learn more</a></span> </div> ';


var wizp_def_payment_info_on_product_hook = 'woocommerce_single_product_summary';
var wizp_def_payment_info_on_product_hook_priority = '15';
var wizp_def_payment_info_on_product_cat_hook = 'woocommerce_after_shop_loop_item';
var wizp_def_payment_info_on_product_cat_hook_priority = '99';

function wizpayHideProductionEnviromentModelDescription() {
    if (jQuery("#woocommerce_wizpay_environment_mode").val() === 'production') {
        jQuery('.wizardpay-enviroment-model-test').hide();
        jQuery('.wizardpay-enviroment-model-test').parent().parent().parent().hide();
        jQuery('.wizardpay-enviroment-model').show();
        jQuery('.wizardpay-enviroment-model').parent().parent().parent().show();
    } else {
        jQuery('.wizardpay-enviroment-model-test').parent().parent().parent().show();
        jQuery('.wizardpay-enviroment-model-test').show();
        jQuery('.wizardpay-enviroment-model').hide();
        jQuery('.wizardpay-enviroment-model').parent().parent().parent().hide();
    }
}







function wizpaySetDefaultValue(
    payment_info_on_product_text,
    payment_info_on_product_cat_text,
    payment_info_on_cart_text,
    payment_info_on_product_hook,
    payment_info_on_product_hook_priority,
    payment_info_on_product_cat_hook,
    payment_info_on_product_cat_hook_priority
) {
    wizp_def_payment_info_on_product_text = payment_info_on_product_text;
    wizp_def_payment_info_on_product_cat_text = payment_info_on_product_cat_text;
    wizp_def_payment_info_on_cart_text = payment_info_on_cart_text;
    wizp_def_payment_info_on_product_hook = payment_info_on_product_hook;
    wizp_def_payment_info_on_product_hook_priority = payment_info_on_product_hook_priority;
    wizp_def_payment_info_on_product_cat_hook = payment_info_on_product_cat_hook;
    wizp_def_payment_info_on_product_cat_hook_priority = payment_info_on_product_cat_hook_priority;
}