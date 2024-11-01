<?php
/**
 * Plugin Name: Wizpay Gateway for WooCommerce
 * Plugin URI: https://wizpay.com.au/
 * Description: A payment gateway for Wizpay.
 * Version Date: 03 Feb 2023
 * Version: 1.4.0
 * Author: Wizpay
 * Author URI: http://www.wizpay.com.au/
 * Developer: Wizpay
 * Developer URI: http://www.wizpay.com.au/
 * WC requires at least: 3.5
 * WC tested up to: 6.1
 * Copyright: © 2009-2022 wizpay.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH'))
{
    exit;
} /* Exit if accessed directly */

/**
 *
 *  Define all constant values
 */
define('WIZARDPAY_PLUGIN_ROOT', dirname(__FILE__) . '/');

register_activation_hook(__FILE__, 'wizardpay_activation');
function wizardpay_activation()
{
    global $wpdb;

    /* if ( get_option('woocommerce_wizpay_settings' ) ) {
    $cst_setting = get_option('woocommerce_wizardpay_settings');
    $cst_setting['success_url'] = '';
    $cst_setting['fail_url'] = '';
    update_option('woocommerce_wizpay_settings', $cst_setting);
    } */

    // call api and send website detail
    $wizpay_register_merchant_helper = new wizardpay_register_merchant_class();
    $wizpay_register_merchant_helper->call_register_merchant_plugin('wizardpay_activation');

}

register_deactivation_hook(__FILE__, 'wizardpay_deactivation');
function wizardpay_deactivation()
{
    global $wpdb;

    /* if ( get_option('woocommerce_wizpay_settings' ) ) {
    $cst_setting = get_option('woocommerce_wizardpay_settings');
    $cst_setting['success_url'] = '';
    $cst_setting['fail_url'] = '';
    update_option('woocommerce_wizpay_settings', $cst_setting);
    } */

    // call api and send website detail
    $wizpay_register_merchant_helper = new wizardpay_register_merchant_class();
    $wizpay_register_merchant_helper->call_register_merchant_plugin('wizardpay_deactivation');

}

// plugin uninstallation
register_uninstall_hook(__FILE__, 'wizardpay_uninstall');

function wizardpay_uninstall()
{

	// call api and send website detail
    $wizpay_register_merchant_helper = new wizardpay_register_merchant_class();
    $wizpay_register_merchant_helper->call_register_merchant_plugin('wizardpay_uninstall');

    delete_option('woocommerce_wizpay_settings');

}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))))
{

    if (!class_exists('Woocommerce_WizardPay_Init'))
    {

        /**
         * Localisation
         *
         */
        load_plugin_textdomain('woocommerce-wizardpay-gateway', false, dirname(plugin_basename(__FILE__)) . '/lang');

        final class Woocommerce_WizardPay_Init
        {

            private static $instance = null;
            public static function initialize()
            {
                if (is_null(self::$instance))
                {
                    self::$instance = new self();
                }

                return self::$instance;
            }

            public function __construct()
            {

                // called after all plugins have loaded
                add_action('plugins_loaded', array(
                    $this,
                    'plugins_loaded'
                ));
                add_filter('plugin_action_links_' . plugin_basename(__FILE__) , array(
                    $this,
                    'plugin_links'
                ));
                add_action('admin_enqueue_scripts', array(
                    $this,
                    'wc_wizardpay_register_plugin_scripts'
                ));
                add_action('wp_ajax_get_pending_capture_amount', array(
                    $this,
                    'get_pending_capture_amount'
                ));
                add_action('wp_ajax_nopriv_get_pending_capture_amount', array(
                    $this,
                    'get_pending_capture_amount'
                ));

                add_action('wp_ajax_merchant_autherised_to_capture_amount', array(
                    $this,
                    'merchant_autherised_to_capture_amount_manually'
                ));
                add_action('wp_ajax_nopriv_merchant_autherised_to_capture_amount', array(
                    $this,
                    'merchant_autherised_to_capture_amount_manually'
                ));

            }

            /**
             * Take care of anything that needs all plugins to be loaded
             */
            public function plugins_loaded()
            {

                if (!class_exists('WC_Payment_Gateway'))
                {
                    return;
                }

                /**
                 * Add the gateway to WooCommerce
                 */
                if(!class_exists('WC_Gateway_WizardPay')){
                    require_once (plugin_basename('class-wizardpay-gateway.php'));
                }
                
                add_filter('woocommerce_payment_gateways', array(
                    $this,
                    'add_wizardpay_gateway'
                ) , 10, 1);

                if(!class_exists('WizardPay_API')){
                     require_once dirname(__FILE__) . '/wizpay/wizpay_api.php';
                }
               

                if (!class_exists('wizardpay_hook_class'))
                {
                    require_once dirname(__FILE__) . '/wizpay_hook_class.php';
                }

                $hook_class = wizardpay_hook_class::initialize();
                $hook_class->register_hooks();
            }

            public function add_wizardpay_gateway($methods)
            {
                array_unshift($methods, 'WC_Gateway_WizardPay');
                //$methods[] = 'WC_Gateway_WizardPay';
                return $methods;
            }

            /**
             *   Register style sheet.
             */
            public function wc_wizardpay_register_plugin_scripts()
            {
                wp_enqueue_script('my_custom_script', plugin_dir_url(__FILE__) . 'assets/js/capture-payment.js', array() , '1.0');
            }

            public function get_pending_capture_amount()
            {

                if (isset($_POST['order_id']))
                {
                    $this->log = new WC_Logger();
                    $order_id = sanitize_text_field($_POST['order_id']);
                    $orderToken = get_post_meta($order_id, 'wz_token', true);
                    $merchantrefernce = get_post_meta($order_id, 'merchantrefernce', true);
                    $wzTxnId = get_post_meta($order_id, 'wz_txn_id', true);
                    $uniqid = md5(time() . $order_id);
                    $getsettings = get_option('woocommerce_wizpay_settings', true);
                    $apikey = $getsettings['wz_api_key'];
                    $api_data = array(
                        'transactionId' => $wzTxnId,
                        'token' => $orderToken,
                        'merchantReference' => $merchantrefernce
                    );

                    $wzapi = new WizardPay_API();
                    $wzresponse = $wzapi->get_order_payment_status_api($apikey, $api_data);
                    if (false === $wzresponse || false !== $wzapi->get_api_error())
                    {
                        $this
                            ->log
                            ->add('Wizpay', sprintf('failure: %s', $wzapi->get_api_error()));

                        esc_attr_e('00.00');
                    }
                    else
                    {

                        $pending_amount = $wzresponse['pendingCaptureAmount'];
                        $min_p_amount = $pending_amount['amount'];
                        $this
                            ->log
                            ->add('Wizpay', sprintf('success, pending capture amount: %s', $min_p_amount));

                        esc_attr_e($min_p_amount);
                    }
                    wp_die();
                }

            }

            public function merchant_autherised_to_capture_amount_manually()
            {

                if (isset($_POST['order_id']) && isset($_POST['captureamount']) && isset($_POST['capture_reason']) && isset($_POST['capture_avail_new']))
                {
                    $this->log = new WC_Logger();

                    $order_id = sanitize_text_field($_POST['order_id']);
                    $captureamount = sanitize_text_field($_POST['captureamount']);
                    $capture_reason = sanitize_text_field($_POST['capture_reason']);
                    $capture_avail = sanitize_text_field($_POST['capture_avail_new']);
                    $order = new WC_Order($order_id);
                    //$orderToken = get_post_meta( $order_id, 'wz_token', false );
                    $merchantrefernce = get_post_meta($order_id, 'merchantrefernce', true);
                    $wzTxnId = get_post_meta($order_id, 'wz_txn_id', true);
                    $currency = get_woocommerce_currency();
                    $uniqid = md5(time() . $order_id);
                    $getsettings = get_option('woocommerce_wizpay_settings', true);
                    $apikey = $getsettings['wz_api_key'];
                    $order_status = $order->get_status();

                    if ('00.00' == $capture_avail)
                    {
                        $order->add_order_note('Order capture failed. No pending balanced left to be captured.' . PHP_EOL . 'Merchant reason for capture: ' . $capture_reason . PHP_EOL . 'Amount: $' . $captureamount);
                        esc_attr_e('00.00');
                    }
                    elseif ($captureamount > $capture_avail)
                    {

                        $order->add_order_note('Order capture failed. capture amount $' . $captureamount . ' was specified greater than the pending amount $' . $capture_avail . PHP_EOL . 'Merchant reason for capture: ' . $capture_reason);
                        esc_attr_e('00.00');
                    }
                    elseif ($captureamount <= 0)
                    {

                        $order->add_order_note('Order capture failed. capture amount $' . $captureamount . ' was specified invailid amount.' . PHP_EOL . 'Merchant reason for capture: ' . $capture_reason);
                        esc_attr_e('00.00');
                    }
                    else
                    {

                        $api_data = array(
                            'RequestId' => $uniqid,
                            'merchantReference' => $merchantrefernce,
                            'amount' => array(
                                'amount' => $captureamount,
                                'currency' => $currency
                            ) ,
                            //"paymentEventMerchantReference"=>$merchantReference
                            
                        );

                        $wzapi = new WizardPay_API();
                        $wzresponse = $wzapi->order_partial_capture_api($apikey, $api_data, $wzTxnId);
                        $this
                            ->log
                            ->add('Wizpay', '========= capture (Parttial Capture) API called' . PHP_EOL);
                        if (false === $wzresponse || false !== $wzapi->get_api_error())
                        {
                            $this
                                ->log
                                ->add('Wizpay', '========= capture (Parttial Capture) API return failure' . PHP_EOL);

                            $order->add_order_note($wzapi->get_api_error());
                            esc_attr_e('00.00');

                        }
                        else
                        {
                            $this
                                ->log
                                ->add('Wizpay', '========= capture (Parttial Capture) API return success' . PHP_EOL);

                            $pending_amount = $wzresponse['pendingCaptureAmount'];
                            $avail_p_amount = $pending_amount['amount'];

                            if ('0' == $avail_p_amount && 'on-hold' == $order_status)
                            {

                                $order->add_order_note('Wizpay Payment Authorised Transaction ' . $wzTxnId . PHP_EOL . 'Merchant reason for capture: ' . $capture_reason . PHP_EOL . 'Capture Amount: $' . $captureamount);
                                $order->update_status('processing');

                            }
                            else
                            {

                                $order->add_order_note('Wizpay Payment Authorised Transaction ' . $wzTxnId . PHP_EOL . 'Merchant reason for capture: ' . $capture_reason . PHP_EOL . 'Capture Amount: $' . $captureamount);
                            }

                            esc_attr_e($pending_amount);
                        }

                    }

                    wp_die();
                }

            } // function merchant_autherised_to_capture_amount_manually()
            
            /**
             * Plugin page links
             */
            public function plugin_links($links)
            {
                // $plugin_links = array(
                // 	'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wizpay' ) . '">' . __( 'Settings', 'woocommerce-wizardpay-gateway' ) . '</a>',
                // 	'<a href="https://docs.woocommerce.com/document/">' . __( 'Docs', 'woocommerce-wizardpay-gateway' ) . '</a>',
                // );
                $plugin_links = array(
                    '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=wizpay') . '">' . __('Settings', 'woocommerce-wizardpay-gateway') . '</a>',
                    '<a href="https://wordpress.org/support/plugin/wizpay-gateway-for-woocommerce/reviews/" target="_blank">' . __('Leave a Review', 'woocommerce-wizardpay-gateway') . '</a>',
                );

                return array_merge($plugin_links, $links);
            }

            public static function wc_wizardpay_log($message)
            {
                $thislog = new WC_Logger();
                $thislog->add('WIZARDPAY_PLUGIN_ROOT', print_r($message, true));
            }

        } // final class Woocommerce_WizardPay_Init
        $GLOBALS['Woocommerce_WizardPay_Init'] = Woocommerce_WizardPay_Init::initialize();

    } // if ( ! class_exists( 'Woocommerce_WizardPay_Init' ) )
    
} // end of check woocommerce plugin activate or not
// Check checkout amount and display gatway option, under rull of Wizardpay min/max value
add_filter('woocommerce_available_payment_gateways', 'wizardpay_unset_gateway_by_price');
function wizardpay_unset_gateway_by_price($available_gateways)
{
    global $woocommerce;
    $getsettings = get_option('woocommerce_wizpay_settings', true);
    $store_currency = strtoupper(get_option('woocommerce_currency'));
    if (is_admin())
    {
        return $available_gateways;
    }
    if (!is_checkout())
    {
        return $available_gateways;
    }
    $unset = false;
    $sub_totalamount = WC()
        ->cart->total;
    if (is_array($getsettings))
    {
        if (!($sub_totalamount >= $getsettings['merchant_minimum_amount'] && $sub_totalamount <= $getsettings['merchant_maximum_amount']) || 'AUD' != $store_currency)
        {
            unset($available_gateways['wizpay']);
        }
        return $available_gateways;
    }
}

// function for register api


class wizardpay_register_merchant_class
{
    public function call_register_merchant_plugin($status)
    {
		$store_address = get_option('woocommerce_store_address');
		$store_address_2 = get_option('woocommerce_store_address_2');
		$store_city = get_option('woocommerce_store_city');
		$store_postcode = get_option('woocommerce_store_postcode');

		// // The country/state
		$store_raw_country = get_option('woocommerce_default_country');

		// // Split the country/state
		$split_country = explode(":", $store_raw_country);

		// // Country and state separated:
		$store_country = $split_country[0];
		$store_state = $split_country[1];

		$apidata = array(
			'merchantStoreUrl' => get_site_url() ,
			'merchantName' => get_bloginfo() ,
			'merchantAddress' => $store_address . ' ' . $store_address_2 . ' ' . $store_city . ' ' . $store_postcode . ' ' . $store_state . ' ' . $store_country,
			'merchantContactNumber' => '',
			'merchantEmail' => '',
			'storeCurrency' => get_woocommerce_currency() ,
			'dateInstalled' => date("Y-m-d") . 'T' . date("H:i:s") . '.000Z',
			'platform' => '2.0Dev ' . $status
		);

		include ('wizpay/access.php');

        $actualapicall = 'RegisterMerchantPlugin';
        $finalapiurl = $this->base . $actualapicall;

        $log = new WC_Logger();

        $log->add('Wizpay', '========= RegisterMerchantPlugin api called' . PHP_EOL);
		$log->add('Wizpay', '========= RegisterMerchantPlugin api called url = ' . $finalapiurl . PHP_EOL);
        $log->add('Wizpay', sprintf('request : %s', json_encode($apidata)) .  PHP_EOL);
        $apiresult = $this->post_to_api($finalapiurl, $apidata);
        $log->add('Wizpay', sprintf('result : %s', json_encode($apiresult)) .  PHP_EOL);
    }

    private function post_to_api($url, $requestbody)
    {

        $response = wp_remote_post($url, array(
            'timeout' => 80,
            'sslverify' => false,
            'headers' => array(
                'Content-Type' => 'application/json'
            ) ,
            'body' => json_encode($requestbody) ,
        ));

        if (!is_wp_error($response))
        {
            return $response;
        }
        else
        {
            return false;
        }

    }
}

