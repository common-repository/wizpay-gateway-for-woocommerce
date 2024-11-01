<?php
/**
* WizardPay_API class which hadles all the API calls to Wizpay
*/

class WizardPay_API {
	
	protected $wizardpay;
	protected $api_url;
	protected $api_key;


	public function __construct() {
		$this->wizardpay = WC_Gateway_WizardPay::getInstance();
		$this->api_url = $this->wizardpay->get_wizardpay_api_url();
		$this->api_key = $this->wizardpay->get_wz_api_key();
		$this->get_capture_setting = $this->wizardpay->get_capture_setting();
		$this->statement_descriptor = $this->wizardpay->get_statement_descriptor();
		$this->log = new WC_Logger();
	}
	
	public function create_wc_log( $apiresult) {

		try{
			$currency = get_woocommerce_currency();
			$capture = 'Unchecked';
			if ('yes' == $this->get_capture_setting) {
				$capture = 'Checked';
			}
			$original_amount = @$apiresult['originalAmount']; 
	
			$this->log->add( 'Wizpay', sprintf('Capture Settings = %s, merchantReference = %s, WZTransactionID = %s, Amount = %s, paymentDescription = %s, responseCode = %s, errorCode = %s, errorMessage = %s, transactionStatus = %s, paymentStatus = %s', $capture, @$apiresult['merchantReference'], @$apiresult['transactionId'], @$original_amount['amount'] . ' ' . $currency , @$apiresult['paymentDescription'], @$apiresult['responseCode'], @$apiresult['errorCode'], @$apiresult['errorMessage'], @$apiresult['transactionStatus'], @$apiresult['paymentStatus'] ) . PHP_EOL);
		
		}catch(Exception $e){
			$this->log->add( 'Wizpay', sprintf('API Request Error' . PHP_EOL));
		}		
	}

	public function set_api_key( $apikey) {
		$this->api_key = $apikey;
	}
	
	public function save_api_error( $error) {
		return update_option( 'wc_wizardpay_api_error', $error );
	}
	
	public function remove_api_error() {
		return delete_option( 'wc_wizardpay_api_error' );
	}
	
	private function get_response_headers( $response) {
		global $wp_version;

		if (version_compare( $wp_version, '4.6.0', '>=' )) {
			$headers_obj = wp_remote_retrieve_headers( $response );
			return $headers_obj->getAll();
		} else {
			$headers_arr = wp_remote_retrieve_headers( $response );
			return array_change_key_case($headers_arr, CASE_LOWER);
		}
	}
	
	public function prepare_api_input( $woo_order_id, $forapi) {
		
		// we need it to get any order details
		global $woocommerce;
		$order = wc_get_order( $woo_order_id );
		
		/*
		 * Array with parameters for API interaction
		 */

		$id = $order->get_id();
		$totalamount = floatval($order->get_total());
		$discount = $order->get_total_discount();
		$currency = get_woocommerce_currency();
		$c_phone = $order->get_billing_phone();
		$c_name = $order->get_billing_first_name();
		$c_surname = $order->get_billing_last_name();
		$c_email = $order->get_billing_email();

		$b_name = $order->get_billing_first_name();
		$b_address_1 = $order->get_billing_address_1();
		$b_address_2 = $order->get_billing_address_2();
		$b_city = $order->get_billing_city();
		$b_state = $order->get_billing_state();
		$b_postcode = $order->get_billing_postcode();
		$b_country = $order->get_billing_country();
		$b_phone = $order->get_billing_phone();

		$s_name = $order->get_shipping_first_name();
		$s_address_1 =$order->get_shipping_address_1();
		$s_address_2 = $order->get_shipping_address_2();
		$s_city = $order->get_shipping_city();
		$s_state = $order->get_shipping_state();
		$s_postcode = $order->get_shipping_postcode();
		$s_country = $order->get_shipping_country(); 
		$s_phone = $order->get_billing_phone();
		$item_sub_total = 0;
		$other_special_item_total = 0;

		if (empty($s_name)) {
			$s_name = $b_name;
		}

		if (empty($s_address_1)) {
			$s_address_1 = $b_address_1;
		}

		if (empty($s_address_2)) {
			$s_address_2 = $b_address_2;
		}

		if (empty($s_city)) {
			$s_city = $b_city;
		}

		if (empty($s_state)) {
			$s_state = $b_state;
		}

		if (empty($s_postcode)) {
			$s_postcode = $b_postcode;
		}

		if (empty($s_country)) {
			$s_country = $b_country;
		}

		if (empty($s_phone)) {
			$s_phone = $b_phone;
		}


		$description = $this->statement_descriptor;
		// $taxtotal = floatval($order->get_shipping_tax());
		// Auth Liu: @2021-07-21, change tax to total tax
		$taxtotal = $order->get_total_tax();
		$shipping_total = floatval($order->get_shipping_total());
		$shipping_tax = floatval($order->get_shipping_tax());
		$uniqid = md5(time() . $id);
		$merchantReference =  'MER' . $uniqid . '-' . $id;
		$script_url = WC()->api_request_url('WC_Gateway_Wizpay');
		$success_url =  $script_url . '?mref=' . $merchantReference . '&orderid=' . $id;
		$fail_url =  $script_url . '?mref=' . $merchantReference . '&orderid=' . $id . '&target=fail';
		
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$item_amount = floatval($cart_item['data']->get_price() * $cart_item['quantity']);
			if($item_amount <= 0){
				$item_amount = 0;
			}
			$itemsdata[] = array(
				'name' => $cart_item['data']->get_title(),
				'sku' => $cart_item['data']->get_sku(),
				'quantity' => $cart_item['quantity'],
				'ShippingRequired' => $cart_item['data']->needs_shipping(),
				'price' => array(
					'amount' => $item_amount,
					'currency' => $currency
				)
			);

			$item_sub_total = $item_sub_total + $item_amount;
		}


		// calc other special item total such as gift wrapping or any other amount.
		$other_special_item_total = $totalamount - $item_sub_total - $shipping_total - $shipping_tax - $discount;

		

		$apidata = array(
			
			'amount' => array(
				'amount'=> number_format($totalamount,2),
				'currency'=> $currency
			),

			'OtherCharges' => array(
				'amount'=> $other_special_item_total < 0 ? 0 : number_format($other_special_item_total, 2),
				'currency'=> $currency
			),
			
			'consumer'=> array(
				'phoneNumber'=> $c_phone,
				'givenNames'=> $c_name,
				'surname'=> $c_surname,
				'email'=> $c_email
			),
			'billing'=> array(
				'name'=> $b_name, 
				'line1'=>$b_address_1, 
				'line2'=> $b_address_2,
				'area1'=> $b_city,
				'area2'=> null,
				'region'=>$b_state, 
				'postCode'=> $b_postcode,
				'countryCode'=> $s_country,
				'phoneNumber'=> $s_phone
			),
			'shipping'=> array(
				'name'=> $s_name,
				'line1'=> $s_address_1,
				'line2'=> $s_address_2, 
				'area1'=> $s_city,
				'area2'=> null,
				'region'=> $s_state,
				'postCode'=> $s_postcode,
				'countryCode'=> $s_country,
				'phoneNumber'=> $s_phone
			),
			/*"courier"=> array(
				"shippedAt"=> "2018-09-22T00:00:00",
				"name"=> null,
				"tracking"=> "TRACK_800",
				"priority"=> null
			),*/
			'description'=> $description,
			'items' => $itemsdata,
			'discounts' =>array(
				array(
					'displayName'=> null,
					'discountNumber'=> 0,
					'amount'=> array(
						'amount'=> $discount,
						'currency'=> $currency
					)
				)
			),

			'merchant'=> array( 
				'redirectConfirmUrl'=> $success_url,
				'redirectCancelUrl'=> $fail_url,
			),
			'merchantReference'=> $merchantReference,
			'merchantOrderId'=> $id,
			'taxAmount'=> array( 
				'amount'=>  $taxtotal,
				'currency'=> $currency
			),
			'shippingAmount'=> array(
				'amount'=> $shipping_total + $shipping_tax,
				'currency'=> $currency
			),
		);
		
		$this->log->add( 'Wizpay', '========= collecting checkout data' . PHP_EOL );


		$this->log->add( 'Wizpay', 'API - request = ' . json_encode($apidata) . PHP_EOL);


		update_post_meta($id, 'merchantrefernce', $merchantReference);
		return $apidata;

	} // End of function prepare_api_input($woo_order_id, $forapi)

	private function parse_api_response( $response) {
		$error = false;
		
		$responsecode = wp_remote_retrieve_response_code( $response );
		$headers = $this->get_response_headers( $response );
		$responsebody = wp_remote_retrieve_body( $response );
		
		$finalresult = '';
		
		if ( '200' == $responsecode ) {
			$finalresult = json_decode( $responsebody, true );
			
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$error = true;
				$errormessage = 'Error: Invalid Json Format received from Wizpay API. Please contact customer support in this regard!!';
			}
		} else {
		 
			/* elseif ($responsecode == '402') {
				$error = true;
				$errormessage = "Error: Wizpay has declined your api request. Please contact customer support in this regard!!";
			} elseif ($responsecode == '412') {
				$error = true;
				$errormessage = "Error: Invalid set of inputs supplied to Wizpay API. Please contact customer support in this regard!!";
			}  */
		
			$error = true;
			//$errormessage = "Error: API Response Code: $responsecode. Content-Type: " . (array_key_exists('content-type', $headers) ? $headers['content-type'] : 'null') . '; http_correlation_id: ' . (array_key_exists('http_correlation_id', $headers) ? $headers['http_correlation_id'] : 'null') . '; cf-ray: ' . (array_key_exists('cf-ray', $headers) ? $headers['cf-ray'] : 'null');
			$errormessage = "Error: API Response Code: $responsecode. Some error occured while calling Wizpay API!!";
		}
		
		if ($error) {
			$this->save_api_error($errormessage);
			return false;
		} else {
			return $finalresult;
		}
				
	}

	private function get_wizardpay_api( $url) {
		$this->log->add('Wizpay', '========= api called url (get_wizardpay_api) = ' . $url . PHP_EOL);
		$response = wp_remote_get( $url, array(
			'timeout' => 80,
			'sslverify' => false,
			'headers' => array(
				'API-KEY' => $this->api_key
			)
		));

		if (!is_wp_error( $response )) {
			//return "no error";
			return $this->parse_api_response( $response );
		} else {
			//return "there was an error";
			return $response;
		}
		
	}

	private function post_wizardpay_api( $url, $requestbody) {

		$this->log->add('Wizpay', '========= api called url (post_wizardpay_api) = ' . $url . PHP_EOL);
		
		$response = wp_remote_post( $url, array(
			'timeout' => 80,
			'sslverify' => false,
			'headers' => array(
				'Content-Type' =>'application/json',
				'API-KEY' => $this->api_key
			),
			'body' => json_encode($requestbody),
		));

		if (!is_wp_error( $response )) {
			return $this->parse_api_response( $response );
		} else {
			return false;
		}
		
	}	
	
	public function get_api_error() {
		return get_option( 'wc_wizardpay_api_error' );
	}
	
	public function call_limit_api( $apikey = null, $api_url = null) {
		$actualapicall = 'GetPurchaseLimit';
		$finalapiurl = $this->api_url . $actualapicall;
		
		if (!is_null($apikey)) {
			$this->set_api_key($apikey);
		}
		
		if(!is_null($api_url)){
			$finalapiurl = $api_url . $actualapicall;
		}
		
		$apiresult = $this->get_wizardpay_api($finalapiurl);
		$this->log->add( 'Wizpay', '========= call_limit_api() function called' . PHP_EOL );
		$this->create_wc_log($apiresult);		
		if ( '' == $apiresult ) {
			$error = true;
			$errormessage = 'Error: Looks like your Website IP Address is not white-listed in Wizpay. Please connect with Wizpay support team!';
			$this->save_api_error($errormessage);
			$apiresult = $errormessage;
			$this->log->add( 'Wizpay', sprintf( 'failure : %s', $apiresult ) );

		} elseif ( false !== $apiresult && '200' == $apiresult['responseCode'] ) {
			$this->remove_api_error();
			$this->log->add( 'Wizpay', print_r( $apiresult, true ) );
		} elseif ( '402' == $apiresult['responseCode'] || '412' == $apiresult['responseCode'] ) {
			$error = true;
			$errormessage = 'Call Transaction Limit Error: ' . $apiresult['errorCode'] . ' - ' . $apiresult['errorMessage'] . ' - ' . $apiresult['paymentDescription'];
			$this->save_api_error($errormessage);
			$apiresult = $errormessage;
			$this->log->add( 'Wizpay', sprintf( 'failure : %s', $apiresult ) );
		} else {
			$error = true;
			$errormessage = 'Error: Please enter a valid Wizpay API Key!'; 
			$this->save_api_error($errormessage);
			$apiresult = $errormessage;
			$this->log->add( 'Wizpay', sprintf( 'failure : %s', $apiresult ) );
		}
		return $apiresult;
	}

	public function call_configur_merchant_plugin ($apikey, $api_url, $apidata){
		$actualapicall = 'ConfigurMerchantPlugin';
		$finalapiurl = $api_url . $actualapicall;

		if (!is_null($apikey)) {
			$this->set_api_key($apikey);
		}

		$this->log->add('Wizpay', '========= call_configur_merchant_plugin api called' . PHP_EOL);
		$this->log->add('Wizpay', '========= call_configur_merchant_plugin api called url = ' . $finalapiurl . PHP_EOL);
        $this->log->add('Wizpay', sprintf('request : %s', json_encode($apidata)) .  PHP_EOL);
		$apiresult = $this->post_wizardpay_api($finalapiurl, $apidata);
		$this->log->add('Wizpay', sprintf('result : %s', json_encode($apiresult)) .  PHP_EOL);
		
	}


	public function call_checkouts_redirect_api( $apikey, $requestbody) {
		$actualapicall = 'transactioncheckouts';
		$finalapiurl = $this->api_url . $actualapicall;
		
		if (!is_null($apikey)) {
			$this->set_api_key($apikey);
		}
		
		$apiresult = $this->post_wizardpay_api($finalapiurl, $requestbody);
		$this->log->add( 'Wizpay', '========= transactioncheckouts api called' . PHP_EOL );
		$this->create_wc_log($apiresult);

		if ( false !== $apiresult && '200' == $apiresult['responseCode'] ) {
			$this->remove_api_error();
			$this->log->add( 'Wizpay', print_r( $apiresult, true ) );
		} elseif ( '402' == $apiresult['responseCode'] || '412' == $apiresult['responseCode'] ) {
			$error = true;
			$errormessage = 'Checkout Redirect Error: ' . $apiresult['errorCode'] . ' - ' . $apiresult['errorMessage'] . ' - ' . $apiresult['paymentDescription'];
			$this->save_api_error($errormessage);
			$apiresult = $errormessage;
		} else {
			$error = true;
			$errormessage = 'Checkout Redirect Error: ' . $apiresult['responseCode']; 
			$this->save_api_error($errormessage);
			$apiresult = $errormessage;
		}
		return $apiresult;
	}

	public function get_order_payment_status_api( $apikey, $requestbody) {
		$actualapicall = 'Payment/transactionstatus';
		$finalapiurl = $this->api_url . $actualapicall;
		
		if (!is_null($apikey)) {
			$this->set_api_key($apikey);
		}
		
		$apiresult = $this->post_wizardpay_api($finalapiurl, $requestbody);
		$this->log->add( 'Wizpay', '========= transactionstatus api called' . PHP_EOL );
		$this->create_wc_log($apiresult);
		if ( false !== $apiresult && '200' == $apiresult['responseCode'] ) {
			$errormessage = '';
			$responseerror = $this->handle_order_payment_status_api_error($apiresult, $errormessage);
			if (true != $responseerror ) {
				$apiresult = $errormessage;
			} else {
				$this->remove_api_error();
			}
		} elseif ( '402' == $apiresult['responseCode'] || '412' == $apiresult['responseCode'] ) {
			$error = true;
			$errormessage = 'Order Status Error: ' . $apiresult['errorCode'] . ' - ' . $apiresult['errorMessage'] . ' - ' . $apiresult['paymentDescription'];
			$this->save_api_error($errormessage);
			$apiresult = $errormessage;
		} else {
			$error = true;
			$errormessage = 'Order Status Error: ' . $apiresult['errorCode'] . ' - ' . $apiresult['errorMessage']; 
			$this->save_api_error($errormessage);
			$apiresult = $errormessage;
		}
		return $apiresult;
	}

	public function handle_order_payment_status_api_error( $apiresult, $errormessage) {
		$error = true;
		$apiOrderId = $apiresult['transactionId'];
		if ('APPROVED' != $apiresult['transactionStatus'] && 'COMPLETED' != $apiresult['transactionStatus'] ) {

			$errormessage = 'Wizpay Payment Failed. Wizpay Transaction ' . $apiOrderId . ' has been Declined';
			$this->save_api_error($errormessage);
			$error = false;
		}           

		if ('AUTH_APPROVED' != $apiresult['paymentStatus'] && 'CAPTURED' != $apiresult['paymentStatus']  && 'PARTIALLY_CAPTURED' != $apiresult['paymentStatus'] ) {
			$orderMessage = '';
			if ('AUTH_DECLINED' == $apiresult['paymentStatus'] ) { 
				$errormessage = 'Wizpay Payment Failed. Wizpay Transaction ' . $apiOrderId . ' has been Declined';
			} elseif ('CAPTURE_DECLINED' == $apiresult['paymentStatus'] ) { 
				$errormessage = 'Wizpay Transaction Wizpay Transaction ' . $apiOrderId . ' Capture Attempt has been declined';
			} elseif ('VOIDED' == $apiresult['paymentStatus'] ) { 
				$errormessage = 'Wizpay Transaction ' . $apiOrderId . ' VOID';
			} else {
				$errormessage = 'Wizpay Transaction ' . $apiOrderId . ' Payment Failed.';
			}
			$this->save_api_error($errormessage);
			$error = false;  
		}
		return $error;
	}

	public function immediate_payment_capture( $apikey, $requestbody) {
		$actualapicall = 'Payment/transactioncapture';
		$finalapiurl = $this->api_url . $actualapicall;
		
		if (!is_null($apikey)) {
			$this->set_api_key($apikey);
		}
		
		$apiresult = $this->post_wizardpay_api($finalapiurl, $requestbody);
		$this->log->add( 'Wizpay', '========= transactioncapture (Immediate Capture) api called' . PHP_EOL );
		$this->create_wc_log($apiresult);

		if ( false !== $apiresult && '200' == $apiresult['responseCode'] ) {
			$errormessage = '';
			$responseerror = $this->handle_immediate_payment_capture_error($apiresult, $errormessage);
			if (true != $responseerror ) {
				$apiresult = $errormessage;
			} else {
				$this->remove_api_error();
			}
		} elseif ( '402' == $apiresult['responseCode'] || '412' == $apiresult['responseCode'] ) {
			$error = true;
			$errormessage = 'Immediate Payment Capture Error: ' . $apiresult['errorCode'] . ' - ' . $apiresult['errorMessage'] . ' - ' . $apiresult['paymentDescription'];
			$this->save_api_error($errormessage);
			$apiresult = $errormessage;
		} else {
			$error = true;
			$errormessage = 'Immediate Payment Capture Error: ' . $apiresult['errorCode'] . ' - ' . $apiresult['errorMessage'] . ' - ' . $apiresult['paymentDescription'];
			$this->save_api_error($errormessage);
			$apiresult = $errormessage;
		}
		return $apiresult;
	}

	public function handle_immediate_payment_capture_error( $apiresult, $errormessage) {
		$error = true;
		$apiOrderId = $apiresult['transactionId'];
		if ('APPROVED' != $apiresult['transactionStatus'] && 'COMPLETED' != $apiresult['transactionStatus'] ) {

			$errormessage = 'Wizpay Payment Failed. Wizpay Transaction ' . $apiOrderId . ' has been Declined';
			$this->save_api_error($errormessage);
			$error = false;
		}

		if ('3005' == $apiresult['errorCode'] ) {

			$errormessage = 'Wizpay Payment Failed. Wizpay Transaction ' . $apiOrderId . ' Reason: ' . $apiresult['errorMessage'];
			$this->save_api_error($errormessage);
			$error = false;
		} 

		if ('3008' == $apiresult['errorCode'] ) {

			$errormessage = 'Wizpay Payment Failed. Wizpay Transaction ' . $apiOrderId . ' Reason: ' . $apiresult['errorMessage'];
			$this->save_api_error($errormessage);
			$error = false;
		}

		if ('3006' == $apiresult['errorCode'] ) {

			$errormessage = 'Wizpay Payment Failed. Wizpay Transaction ' . $apiOrderId . ' Reason: ' . $apiresult['errorMessage'];
			$this->save_api_error($errormessage);
			$error = false;
		}                                       

		if ('AUTH_APPROVED' != $apiresult['paymentStatus'] && 'CAPTURED' != $apiresult['paymentStatus'] && 'CAPTURE_DECLINED' != $apiresult['paymentStatus']) {

			$orderMessage = '';
			if ('AUTH_DECLINED' == $apiresult['paymentStatus'] ) { 
				$errormessage = 'Wizpay Payment Failed. Wizpay Transaction ' . $apiOrderId . ' has been Declined';
			/*} elseif ('CAPTURE_DECLINED' == $apiresult['paymentStatus'] ) { 
				$errormessage = 'Wizpay Transaction ' . $apiOrderId . ' Capture Attempt has been declined';
			*/
			} elseif ('VOIDED' == $apiresult['paymentStatus'] ) { 
				$errormessage = 'Wizpay Transaction ' . $apiOrderId . ' VOID';
			} else {
				$errormessage = 'Wizpay Transaction ' . $apiOrderId . ' Payment Failed.';
			}
			$this->save_api_error($errormessage);
			$error = false;  
		}
		return $error;
	}

	public function order_partial_capture_api( $apikey, $requestbody, $apiOrderId) {
		$actualapicall = 'Payment/transactioncapture/' . $apiOrderId;
		$finalapiurl = $this->api_url . $actualapicall;

		if (!is_null($apikey)) {
			$this->set_api_key($apikey);
		}
		
		$apiresult = $this->post_wizardpay_api($finalapiurl, $requestbody);
		$this->log->add( 'Wizpay', '========= transactioncapture (Partial Capture) api called' . PHP_EOL );
		$this->create_wc_log($apiresult);

		if ( false !== $apiresult && '200' == $apiresult['responseCode'] ) {
			$errormessage = '';
			$responseerror = $this->handle_partial_payment_capture_error($apiresult, $errormessage);
			if (true != $responseerror ) {
				$apiresult = $errormessage;
			} else {
				$this->remove_api_error();
			}

		} elseif ( '402' == $apiresult['responseCode'] || '412' == $apiresult['responseCode'] ) {
			$error = true;
			$errormessage = 'Partial Payment Capture Error: ' . $apiresult['errorCode'] . ' - ' . $apiresult['errorMessage'] . ' - ' . $apiresult['paymentDescription'];
			$this->save_api_error($errormessage);
			$apiresult = $errormessage;
		} else {
			$error = true;
			$errormessage = 'Partial Payment Capture Error: ' . $apiresult['errorCode'] . ' - ' . $apiresult['errorMessage'] . ' - ' . $apiresult['paymentDescription']; 
			$this->save_api_error($errormessage);
			$apiresult = $errormessage;
		}
		return $apiresult;
	}

	public function handle_partial_payment_capture_error( $apiresult, $errormessage) {
		$error = true;
		$apiOrderId = $apiresult['transactionId'];
		if ('APPROVED' != $apiresult['transactionStatus']  && 'COMPLETED' != $apiresult['transactionStatus'] ) {

			$errormessage = 'Wizpay Payment Failed. Wizpay Transaction ' . $apiOrderId . ' has been Declined';
			$this->save_api_error($errormessage);
			$error = false;
		}

		if ('3005' == $apiresult['errorCode'] ) {

			$errormessage = 'Wizpay Payment Failed. Wizpay Transaction ' . $apiOrderId . ' Reason: ' . $apiresult['errorMessage'];
			$this->save_api_error($errormessage);
			$error = false;
		} 

		if ('3008' == $apiresult['errorCode'] ) {

			$errormessage = 'Wizpay Payment Failed. Wizpay Transaction ' . $apiOrderId . ' Reason: ' . $apiresult['errorMessage'];
			$this->save_api_error($errormessage);
			$error = false;
		}

		if ('3006' == $apiresult['errorCode'] ) {

			$errormessage = 'Wizpay Payment Failed. Wizpay Transaction ' . $apiOrderId . ' Reason: ' . $apiresult['errorMessage'];
			$this->save_api_error($errormessage);
			$error = false;
		}                    

		if ('AUTH_APPROVED' != $apiresult['paymentStatus'] && 'PARTIALLY_CAPTURED' != $apiresult['paymentStatus'] && 'CAPTURED' != $apiresult['paymentStatus'] && 'CAPTURE_DECLINED' != $apiresult['paymentStatus']) {
			$orderMessage = '';
			if ('AUTH_DECLINED' == $apiresult['paymentStatus'] ) { 
				$errormessage = 'Wizpay Payment Failed. Wizpay Transaction ' . $apiOrderId . ' has been Declined';
			/* } elseif ('CAPTURE_DECLINED' == $apiresult['paymentStatus'] ) { 
				//$errormessage = 'Wizpay Transaction ' . $apiOrderId . ' Capture Attempt has been declined';
			*/
			} elseif ('VOIDED' == $apiresult['paymentStatus'] ) { 
				$errormessage = 'Wizpay Transaction ' . $apiOrderId . ' VOID';
			} else {
				$errormessage = 'Wizpay Transaction ' . $apiOrderId . ') Payment Failed.';
			}
			$this->save_api_error($errormessage);
			$error = false;  
		}
		return $error;
	}

	public function order_refund_api( $apikey, $requestbody, $wz_txn_id) {
		$actualapicall = 'Payment/refund/' . $wz_txn_id;
		$finalapiurl = $this->api_url . $actualapicall;
		if (!is_null($apikey)) {
			$this->set_api_key($apikey);
		}

		$apiresult = $this->post_wizardpay_api($finalapiurl, $requestbody);
		$this->log->add( 'Wizpay', '========= refund api called' . PHP_EOL );
		$this->create_wc_log($apiresult);
				
		if ( false !== $apiresult && '200' == $apiresult['responseCode'] ) {
			$errormessage = '';
			$responseerror = $this->handle_order_refund_api_error($apiresult, $errormessage);
			if (true != $responseerror ) {
				$apiresult = $errormessage;
			} else {
				$this->remove_api_error();
			}

		} elseif ( '402' == $apiresult['responseCode'] || '412' == $apiresult['responseCode'] ) {
			$error = true;
			$errormessage = 'Error: ' . $apiresult['errorCode'] . ' - ' . $apiresult['errorMessage'] . ' - ' . $apiresult['paymentDescription'];
			$this->save_api_error($errormessage);
			$apiresult = $errormessage;
		} else {
			$error = true;
			$errormessage = 'Error: ' . $apiresult['errorCode'] . ' - ' . $apiresult['errorMessage']; 
			$this->save_api_error($errormessage);
			$apiresult = $errormessage;
		}
		return $apiresult;
	}

	public function handle_order_refund_api_error( $apiresult, $errormessage) {
		$error = true;
		$apiOrderId = $apiresult['transactionId'];
		if ('APPROVED' != $apiresult['transactionStatus'] && 'COMPLETED' != $apiresult['transactionStatus']) {

			$errormessage = 'Wizpay Payment Failed. Wizpay Transaction ' . $apiOrderId . ' has been Declined';
			$this->save_api_error($errormessage);
			$error = false;
		}

		if ('AUTH_APPROVED' != $apiresult['paymentStatus'] && 'PARTIALLY_CAPTURED' != $apiresult['paymentStatus'] && 'CAPTURED' != $apiresult['paymentStatus'] ) {
			$orderMessage = '';
			if ('AUTH_DECLINED' == $apiresult['paymentStatus'] ) { 
				$errormessage = 'Wizpay Payment Failed. Wizpay Transaction ' . $apiOrderId . ' has been Declined';
			/* } elseif ('CAPTURE_DECLINED' == $apiresult['paymentStatus'] ) { 
				//$errormessage = 'Wizpay Transaction ' . $apiOrderId . ' Capture Attempt has been declined';
			*/
			} elseif ('VOIDED' == $apiresult['paymentStatus'] ) { 
				$errormessage = 'Wizpay Transaction ' . $apiOrderId . ' VOID';
			} else {
				$errormessage = 'Wizpay Transaction ' . $apiOrderId . ' Payment Failed.';
			}
			$this->save_api_error($errormessage);
			$error = false;  
		}
		return $error;
	}


	public function order_voided_api( $apikey , $wz_txn_id) {
		$actualapicall = 'Payment/voidtransaction/' . $wz_txn_id;
		$finalapiurl = $this->api_url . $actualapicall;
		if (!is_null($apikey)) {
			$this->set_api_key($apikey);
		}
		$apiresult = $this->post_wizardpay_api($finalapiurl, $wz_txn_id);
		$this->log->add( 'Wizpay', '========= voidorder api called' . PHP_EOL );
		$this->create_wc_log($apiresult);

		if ( false !== $apiresult && '200' == @$apiresult['responseCode'] ) {
			$errormessage = '';
			$responseerror = $this->handle_order_voided_api_error($apiresult, $errormessage);
			if (true != $responseerror) {
				$apiresult = $errormessage;
				$this->log->add( 'Wizpay', sprintf( 'failure: %s', $apiresult ) );
			} else {
				$this->remove_api_error();
			} 
		} elseif ( '412' == @$apiresult['responseCode']) {
			$error = true;
			$errormessage = 'Cancel attempt failed because payment has already been captured for this order';
			$this->save_api_error($errormessage);
			$apiresult = $errormessage;
			$this->log->add( 'Wizpay', sprintf( 'failure: %s', $apiresult ) );
		} elseif ( '402' == @$apiresult['responseCode']) {
			$error = true;
			$errormessage = 'Error: ' . @$apiresult['errorCode'] . ' - ' . @$apiresult['errorMessage'] . ' - ' . @$apiresult['paymentDescription'];
			$this->save_api_error($errormessage);
			$apiresult = $errormessage;
			$this->log->add( 'Wizpay', sprintf( 'failure: %s', $apiresult ) );
		} else {
			$error = true;
			$errormessage = 'Error: ' . @$apiresult['errorCode'] . ' - ' . @$apiresult['errorMessage']; 
			$this->save_api_error($errormessage);
			$apiresult = $errormessage;
			$this->log->add( 'Wizpay', sprintf( 'failure: %s', $apiresult ) );
		}
		return $apiresult;
	}

	public function handle_order_voided_api_error( $apiresult, $errormessage) {
		$error = true;
		$apiOrderId = $apiresult['transactionId'];
		if ('COMPLETED' != $apiresult['transactionStatus'] && 'COMPLETED' != $apiresult['transactionStatus']) {

			$errormessage = "Wizpay Payment cancel doesn't authorised. Wizpay Transaction " . $apiOrderId . '  has been Declined!';
			$this->save_api_error($errormessage);
			$error = false;
		}               

		if ('VOIDED' != $apiresult['paymentStatus'] && 'CAPTURED' != $apiresult['paymentStatus'] ) {
			$orderMessage = '';
			   
			$errormessage = 'Wizpay Transaction ' . $apiOrderId . ' Payment Cancel Failed';
			$this->save_api_error($errormessage);
			$error = false;  
		}
		return $error;
	}

}
