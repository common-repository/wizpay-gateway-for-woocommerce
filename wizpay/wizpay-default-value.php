<?php
/**
* Default values for the Plugin Admin Form Fields
*/

$environments = array(
			"sandbox" => array(
				"name"       =>  "Sandbox"
			),
			"production" => array(
				"name"       => "Production"
			)
		);




$def_payment_info_on_product_text = '[wizpay_logo] or 4 payments of [OF_OR_FROM] with Wizpay [Learn_More]';
$def_payment_info_on_product_cat_text = '[wizpay_logo]';
$def_payment_info_on_cart_text = '<div class="wizp-cart-custom-message">[wizpay_logo]<span style="vertical-align: super !important;">4 x fortnightly payments of [OF_OR_FROM] [Learn_More]</span></div>';


$def_payment_info_on_product_hook = 'woocommerce_single_product_summary';
$def_payment_info_on_product_hook_priority = '15';
$def_payment_info_on_product_cat_hook = 'woocommerce_after_shop_loop_item_title';
$def_payment_info_on_product_cat_hook_priority = '99';
