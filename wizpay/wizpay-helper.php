<?php
    /*
     * Display HTML on page
     *
    */
    function process_and_print_wizpay_paragraph($html, $output_filter, $price, $plugin_dir, $logo_class = '', $display_outside_range_message = false)
    {



        
        // add popup windows 
        
        // $html = $html . get_popup_window();
        $html = wizpay_get_display_html($html, $output_filter, $price, $plugin_dir, $logo_class, $display_outside_range_message);
       

        # Allow other plugins to maniplulate or replace the HTML echoed by this funtion.
        echo apply_filters($output_filter, $html, $price);
    }


    function wizpay_get_display_html($html, $output_filter, $price, $plugin_dir, $logo_class = '', $display_outside_range_message = false){
        $settings = get_option('woocommerce_wizpay_settings', true);

        if($settings != null && is_array($settings) && array_key_exists('enabled', $settings ) && $settings['enabled'] != 'yes' ){
            echo apply_filters($output_filter, '', $price);
            return '';
        }



        $wzwmin = $settings['wz_minimum_amount'];
        $wzwmax = $settings['wz_maximum_amount'];
        $merchant_minimum_amount = $settings['merchant_minimum_amount'];
        $merchant_maximum_amount = $settings['merchant_maximum_amount'];

        $store_currency = strtoupper(get_option('woocommerce_currency'));

        if (empty($merchant_minimum_amount) || empty($merchant_maximum_amount))
        {

            $merchant_minimum_amount = $wzwmin;
            $merchant_maximum_amount = $wzwmax;
        }

        // add logo
        $logo_url = $plugin_dir . 'images/Group.png';
        $logo_html = '<img class="wz-payment-logo ' . $logo_class . '" style="width: 100px ;" src="'. $logo_url .'">';

        // check amount
        if (is_null($price) || $price <= 0 || $price < $merchant_minimum_amount || $price > $merchant_maximum_amount )
        {
            if($display_outside_range_message){
                // display outside range message
                $html = $logo_html . '<span> 
                        <input type="hidden" id="wizpay_merchant_minimum_amount" name="wizpay_merchant_minimum_amount" value="' . $merchant_minimum_amount  . '">
                        <input type="hidden" id="wizpay_merchant_maximum_amount" name="wizpay_merchant_maximum_amount" value="' . $merchant_maximum_amount . '">
						<span id="wizpay-price-range-holder">is available on purchases between $' . number_format($merchant_minimum_amount, 0) . ' and $' . number_format($merchant_maximum_amount, 0) . '</span>
						<a target="_blank" class="wizp-popup-open" style="font-size: 12px;text-decoration: underline;">
						learn more</a>
					</span>';
            }
            else
            {
                return;
            }
            
        }
        else
        {
            

            // replace key words
            $html = str_replace(array(
                '[MIN_LIMIT]',
                '[MAX_LIMIT]',
                '[AMOUNT]',
                '[OF_OR_FROM]',
                '[wizpay_logo]',
                '[Learn_More]'
            ) , array(
                display_price_html(floatval($merchant_minimum_amount)) ,
                display_price_html(floatval($merchant_maximum_amount)) ,
                display_price_html(floatval($price)) ,
                display_price_html(floatval($price / 4)) ,
                $logo_html,
                '<a target="_blank" class="wizp-popup-open" style="font-size: 12px;text-decoration: underline">learn more</a>'
            ) , $html);
        }
    
        if(strpos($html, 'learn more') === false){
            $html = '<p style="line-height: 20px;">' . $html . '</p>';
        }else{
            // add popup windows         
            $html = '<p style="line-height: 20px;">' . $html . '</p>' . get_popup_window();
        }

        return $html;
    }


    /**
     * Convert the global $post object to a WC_Product instance.
     *
     * @since	2.0.0
     * @global	WP_Post	$post
     * @uses	wc_get_product()	Available as part of the WooCommerce core plugin since 2.2.0.
     *								Also see:	WC()->product_factory->get_product()
     *								Also see:	WC_Product_Factory::get_product()
     * @return	WC_Product
     */
    function get_product_from_the_post()
    {
        global $post;

        if (function_exists('wc_get_product'))
        {
            $product = wc_get_product($post->ID);
        }
        else
        {
            $product = new WC_Product($post->ID);
        }

        return $product;
    }

    function display_price_html($price)
    {
        if (function_exists('wc_price'))
        {
            return wc_price($price);
        }
        elseif (function_exists('woocommerce_price'))
        {
            return woocommerce_price($price);
        }
        return '$' . number_format($price, 2, '.', ',');
    }


    function get_popup_window(){
        $url_popup = 'https://info.wizpay.com.au/HowItWorks/HowItWorks.html';

        return ' <div style="display: none;" class="wizp-pop-outer">
                    <div class="wizp-pop-inner">
                        <button class="wizp-popup-close" type="button">X</button><iframe src="'. $url_popup .'" title="Terms and Conditions "></iframe>
                    </div>
                </div>
            ';
    }