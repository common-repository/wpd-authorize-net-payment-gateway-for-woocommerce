<?php
/**
 * class WPD_WC_Authorizenet_Gateway extends WC_Payment_Gateway_CC
 *
 * @package WPD Authorize.net
 * @author wpplugindesign.com
 * @since 0.5
*/

if (!defined('ABSPATH')) {
	exit;
}

require_once WPD_WC_Authorizenet_Payment_Gateway::get_instance()->plugin_path.'vendor/autoload.php';

do_action('wpd_authnet_before_gateway_class');

if (!class_exists('WPD_WC_Authorizenet_Gateway')) {

class WPD_WC_Authorizenet_Gateway extends WC_Payment_Gateway_CC {

	public $authorizenet_sandbox_url = JohnConde\Authnet\AuthnetApiFactory::USE_DEVELOPMENT_SERVER;
	public $authorizenet_production_url = JohnConde\Authnet\AuthnetApiFactory::USE_PRODUCTION_SERVER;

	public $checkout_action = 'authCaptureTransaction';
	public $checkout_success_status = 'wc-completed';

	public function __construct() {
	  
		$this->id = 'wpd_authorizenet';
		$this->has_fields = true;
		$this->method_title = __('WPD Authorize.Net', 'woocommerce');
		$this->method_description = __('Credit card payments using authorize.net.');
		$this->supports = apply_filters('wpd_wc_authorizenet_gateway_supports', array());
		  
		// load settings
		$this->init_form_fields();
		$this->init_settings();
		  
		// define user set variables
		foreach ($this->form_fields as $key => $ff) {
			$this->{$key} = $this->get_option($key);
		}

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

		add_filter('wpd_authnet_checkout_success_status', function($status) {
			return $this->capt_ord_status;
		});

		$this->checkout_action = apply_filters('wpd_authnet_checkout_action', $this->checkout_action);
		$this->checkout_success_status = apply_filters('wpd_authnet_checkout_success_status', $this->checkout_success_status);
	}

	public function init_form_fields() {
		$this->form_fields = apply_filters(
			'wpd_authorizenet_form_fields',
			array(
				'enabled' => array(
					'title' => __('Enable/Disable', 'woocommerce'),
					'type' => 'checkbox',
					'label' => __('Enable WPD Authorize.Net', 'woocommerce'),
					'default' => 'yes'
				),
				'title' => array(
				  	'title' => __('Title', 'woocommerce'),
					'type' => 'text',
					'default' => __('WPD Authorize.Net', 'woocommerce'),
					'desc_tip' => true,
					'description' => __('Title customer sees during checkout.', 'woocommerce')
				),
				'description' => array(
					'title' => __('Description', 'woocommerce'),
					'type' => 'textarea',
					'default' => __('Secure Credit Card transaction via authorize.net.', 'woocommerce'),
					'desc_tip' => true,
					'description' => __('Description customer sees during checkout.', 'woocommerce')
				),
				'api_login' => array(
					'title' => __('API Login ID', 'woocommerce'),
					'type' => 'text',
					'desc_tip' => true,
					'description' => __('API Login ID', 'woocommerce'),
					'placeholder' => 'Authorize.Net API Login ID'
				),
				'transaction_key' => array(
					'title' => __('Transaction Key', 'woocommerce'),
					'type' => 'password',
					'desc_tip' => true,
					'description' => __('Transaction Key', 'woocommerce'),
					'placeholder' => 'Authorize.Net Transaction Key'
				),
				'sandbox' => array(
					'title' => __('Sandbox', 'woocommerce'),
					'type' => 'checkbox',
					'label' => __('Enable Authorize.Net sandbox', 'woocommerce'),
					'desc_tip' => true,
					'description' => __('Live mode if unchecked.', 'woocommerce'),
					'default' => 'no'
				),
				'capt_ord_status' => array(
					'title' => __('Captured Order Status', 'woocommerce'),
					'type' => 'select',
					'class' => 'chosen_select',
					'css' => 'width: 300px;',
					'desc_tip' => true,
					'description' => __('Order status the order will be set to when credit card payment is captured.', 'woocommerce'),
					'options' => WPD_WC_Authorizenet_Payment_Gateway::get_instance()->order_statuses
				),
				'held_ord_status' => array(
					'title' => __('Held For Review Order Status', 'woocommerce'),
					'type' => 'select',
					'class' => 'chosen_select',
					'css' => 'width: 300px;',
					'desc_tip' => true,
					'description' => __('Order status the order will be set to when credit card payment is held for review.', 'woocommerce'),
					'options' => WPD_WC_Authorizenet_Payment_Gateway::get_instance()->order_statuses
				),
				'held_message' => array(
					'title' => __('Held For Review Message', 'woocommerce'),
					'type' => 'textarea',
					'desc_tip' => true,
					'description' => __('Message shown on thank you page when payment is held for review.', 'woocommerce'),
					'default' => 'Thank you for your order. Your payment has been held for review.'
				),
				'show_held_message' => array(
	        		'title' => __('Show Held For Review Message', 'woocommerce'),
	        		'type' => 'checkbox',
	        		'label' => __('Show Held For Review Message', 'woocommerce'),
	        		'desc_tip' => true,
	        		'description' => __('Show Held For Review message to customer on checkout thank you page.', 'woocommerce'),
	        		'default' => 'yes'
	    		)
	    	)
		);
	}

	/**
	 * checkout - Charge Credit Card
	*/
	public function process_payment($order_id) {

		global $woocommerce;

		$cc_data = $this->get_sanitized_credit_card_data(
			$_POST[$this->id.'-card-number'], 
			$_POST[$this->id.'-card-expiry'], 
			$_POST[$this->id.'-card-cvc']
		);

		if (empty($cc_data['cc_number']) || empty($cc_data['cc_exp_date'])) {
			wc_add_notice(__('Your credit card number and expiration date are required.', 'woocommerce'), 'error');
			return;
		}

		$call = $this->checkout_api_call(
			$this->checkout_action, 
			$order_id, 
			$cc_data['cc_number'], 
			$cc_data['cc_exp_date'], 
			$cc_data['cc_cvc']
		);

		if (!empty($call['response'])) {

			$wc_order = $call['wc_order'];
			$trans_type = $call['trans_type'];
			$x = $this->parse_api_response($call['response'], $trans_type);
			unset($call);

			if ($x) {
				// store amount
				$x['amount'] = $wc_order->get_total();
				// store last 4 digits of credit card number
				$x['last_four'] = substr($cc_data['cc_number'], -4);

				switch ($x['respcode']) {
					case '1': // Approved
						wc_reduce_stock_levels($order_id); // reduce stock
						WC()->cart->empty_cart(); // empty cart
						// change order status
						$wc_order->update_status($this->checkout_success_status, __('', 'woocommerce'));
						// add order note
						$this->add_order_note($this->checkout_action.' - Approved', $x, $wc_order, $order_id);
						// store transaction in database
						$this->store_transaction_meta($order_id, $this->checkout_action, $x);
						// store trans id used for payment
						update_post_meta($order_id, 'wpdauth_payment_trans_id', $x['trans_id']);

						if ('authCaptureTransaction' == $this->checkout_action) {
							// set capture time for accounting report
							update_post_meta($wc_order->get_order_number(), 'wpdauth_payment_capture_time', $x['time']);
						}
						return array('result' => 'success', 'redirect' => $this->get_return_url($wc_order));
						break;
					case '2': // Declined
						// add order note
						$this->add_order_note($this->checkout_action.' - Declined', $x, $wc_order, $order_id);
						// store declined transaction for debugging
						$this->store_transaction_meta($order_id, $this->checkout_action, $x, 'wpd_authnet_pmet_declined');
						// add wc page notice
						wc_add_notice(__('The credit card has been declined.', 'woocommerce'), 'error');
						return null;
						break;
					case '3': // Error
						// add order note
						$this->add_order_note($this->checkout_action.' - Error', $x, $wc_order, $order_id);
						// store error transaction for debugging
						$this->store_transaction_meta($order_id, $this->checkout_action, $x, 'wpd_authnet_pmet_declined');
						// add wc page notice
						$notice = 'There was an error.';
						if (!empty($x['error_message'])) {
							$notice .= ' '.$x['error_message'];
						} elseif (!empty($x['resp_message'])) {
							$notice .= ' '.$x['resp_message'];
						}
						wc_add_notice(__($notice, 'woocommerce'), 'error');
						return null;
						break;
					case '4': // Held for Review
						// reduce stock levels and empty cart
						// if (!empty($x['auth_code'])) {
							wc_reduce_stock_levels($order_id);
							WC()->cart->empty_cart();
						// }
						// change order status
						$wc_order->update_status($this->held_ord_status, __('', 'woocommerce'));
						// add order note
						$this->add_order_note($this->checkout_action.' - Held for Review', $x, $wc_order, $order_id);
						// store transaction in database
						$this->store_transaction_meta($order_id, $this->checkout_action, $x);
						// store trans id used for payment
						update_post_meta($order_id, 'wpdauth_payment_trans_id', $x['trans_id']);
						return array(
							'result' => 'success', 
							'redirect' => add_query_arg('wpdauth_held', '1', $this->get_return_url($wc_order))
						);
				}
			} else {
				wc_add_notice(__('There was an error. Please try again.', 'woocommerce'), 'error');
			}
		} else {
			wc_add_notice(__('There was no response from the payment gateway. Please try again.', 'woocommerce'), 'error');
		}

	}

	public function checkout_api_call($trans_type = 'authCaptureTransaction', $order_id = null, $cc_number = null, $cc_exp_date = null, $cc_cvc = null) {

		global $woocommerce;

		// get order object
		$wc_order = wc_get_order($order_id);

		$ra = array(
			'refId' => $this->create_trans_ref_id(),
			'transactionRequest' => array(
				'transactionType' => $trans_type,
				'amount' => $wc_order->get_total(),
				'payment' => array(
					'creditCard' => array(
						'cardNumber' => $cc_number,
						'expirationDate' => $cc_exp_date
	                )
	            ),
	            'order' => array(
	                'invoiceNumber' => $wc_order->get_order_number(),
	                'description' => get_bloginfo('blogname') . ' Order #' . $wc_order->get_order_number()
	            ),
	            'customer' => array(
	            	'id' => $wc_order->get_user_id(),
					'email' => $wc_order->get_billing_email()
            	)
			)
		);

		// add cvc if exists
		if ($cc_cvc) {
			$ra['transactionRequest']['payment']['creditCard']['cardCode'] = $cc_cvc;
		}

		// get customer billing address
		$bill_ad = array(
			'firstName' => $wc_order->get_billing_first_name(), 
			'lastName' => $wc_order->get_billing_last_name(), 
			'company' => $wc_order->get_billing_company(), 
			'address' => $wc_order->get_billing_address_1() . ', ' . $wc_order->get_billing_address_2(),
			'city' => $wc_order->get_billing_city(), 
			'state' => $wc_order->get_billing_state(), 
			'zip' => $wc_order->get_billing_postcode(), 
			'country' => $wc_order->get_billing_country()
		);
		$ra['transactionRequest']['billTo'] = $bill_ad;

		$ship_ad = array(
			'firstName' => $wc_order->get_shipping_first_name(),
			'lastName' => $wc_order->get_shipping_last_name(),
			'company' => $wc_order->get_shipping_company(),
			'address' => $wc_order->get_shipping_address_1() . ', ' . $wc_order->get_shipping_address_2(),
			'city' => $wc_order->get_shipping_city(),
			'state' => $wc_order->get_shipping_state(),
			'zip' => $wc_order->get_shipping_postcode(),
			'country' => $wc_order->get_shipping_country()
		);
		if (trim($ship_ad['address']) == '' && trim($ship_ad['city']) == '' && trim($ship_ad['state']) == '' && trim($ship_ad['zip']) == '') {
			$ra['transactionRequest']['shipTo'] = $bill_ad;
		} else {
			$ra['transactionRequest']['shipTo'] = $ship_ad;
		}

		$request  = JohnConde\Authnet\AuthnetApiFactory::getJsonApiHandler(
			$this->api_login,
			$this->transaction_key,
			$this->get_envir_url()
		);
		$response = $request->createTransactionRequest($ra);

		return array(
			'response' => $response,
			'wc_order' => $wc_order,
			'trans_type' => $trans_type
		);

	}

	public function get_transaction_status($trans_id, $order_id) {

		$request  = JohnConde\Authnet\AuthnetApiFactory::getJsonApiHandler(
			$this->api_login,
			$this->transaction_key,
			$this->get_envir_url()
		);
		$response = $request->getTransactionDetailsRequest(array(
			'transId' => $trans_id
		));

		if ( ($response != null) && ($response->isSuccessful()) ) {

			$trans_stat = $response->transaction->transactionStatus;
			$auth_amount = $response->transaction->authAmount;
			$settle_amount = $response->transaction->settleAmount;

			// store status of payment transaction
			update_post_meta($order_id, 'wpdauth_payment_trans_status', $trans_stat);
    			
			return array(
				'trans_stat' => $trans_stat, 
				'auth_amount' => $auth_amount,
				'settle_amount' => $settle_amount
			);
		} else {
			return false;
		}
	}

	public function parse_api_response($response = null, $trans_type = 'authCaptureTransaction') {
		if (!$response) 
			return false;

		$x = array(
			'trans_type' => $trans_type,
			'refid' => $response->refId
		);

		$x['time'] = $x['refid'] ? $this->remove_non_digits_from_string($x['refid']) : time();

		if ($response->messages != null) {
			// result code
			$x['result_code'] = $response->messages->resultCode;
			// response message
			$x['resp_message_code'] = $response->messages->message[0]->code;
			$x['resp_message'] = $response->messages->message[0]->text;
		}

		if ($response->transactionResponse != null) {

			$x['respcode'] = $response->transactionResponse->responseCode; // responseCode
			$x['auth_code'] = $response->transactionResponse->authCode; // authCode
			$x['trans_id'] = $response->transactionResponse->transId; // transId
			$x['avs_code'] = $response->transactionResponse->avsResultCode; // avsResultCode
			$x['cvv_code'] = $response->transactionResponse->cvvResultCode; // cvvResultCode
			$x['cavv_code'] = $response->transactionResponse->cavvResultCode; // cavvResultCode
			$x['cc_type'] = $response->transactionResponse->accountType; // accountType

			// transaction message
            if ($response->transactionResponse->messages != null) {
				$x['trans_message_code'] = $response->transactionResponse->messages[0]->code;
				$x['trans_message'] = $response->transactionResponse->messages[0]->description;
			}

			// error message
            if ($response->isError()) {
                $x['error_code'] = $response->getErrorCode();
                $x['error_message'] = $response->getErrorText();
            }
		}
		foreach ($x as $field_name => $value) {
			if ('' == trim($value)) {
				unset($x[$field_name]);
			}
		} 
		return $x;
	}

	/*
	 * $meta_type is either 'wpd_authnet_pmet' or 'wpd_authnet_pmet_declined'
	*/
	public function store_transaction_meta($order_id, $trans_type, $x, $meta_type = 'wpd_authnet_pmet') {

		// need to have a transaction id
		if (isset($x['trans_id']) && $x['trans_id'] > 0) :

			// get post meta array from database
			$wpd_authnet_pmet = get_post_meta($order_id, $meta_type, true);
			
			// if no meta array stored in database...
			if (!is_array($wpd_authnet_pmet)) {
				$wpd_authnet_pmet = array();
			}

			// update meta array
			$wpd_authnet_pmet[ $x['trans_id'] ][$trans_type][ $x['time'] ] = $x;

			// store post meta array in database
			update_post_meta($order_id, $meta_type, $wpd_authnet_pmet);

		endif;

	}

	public function add_order_note($trans_type = 'authCaptureTransaction', $x = array(), $wc_order = null, $order_id = null) {
		if (!$wc_order) {
			// get order object
			$wc_order = wc_get_order($order_id);
		}

		$include = array(
			'resp_message',
			'trans_message',
			'error_message',
			'trans_id',
			'auth_code',
			'amount', 
			'cc_type'
		);

		$note = '';

		foreach($include as $i) {
			if (isset($x[$i])) {
				if ('trans_id' == $i) {
					if ($x[$i]) {
						$note .= ' trans id: ' . $x[$i] . ';';
					}
				} elseif ('auth_code' == $i) {
					$note .= ' auth code: ' . $x[$i] . ';';
				} else {
					$note .= ' ' . $x[$i] . ';';
				}
			}
		}
		$note = rtrim($note, ';');

		if ($note) {
			$note = $trans_type.':'.$note;
			$wc_order->add_order_note(__($note, 'woocommerce'));
		}

	}

	public function get_envir_url() {
		return ('yes' == $this->sandbox) ? $this->authorizenet_sandbox_url : $this->authorizenet_production_url;
	}

	public function remove_non_digits_from_string($string) {
		$string = preg_replace('/[^\d]/', '', $string);
		return $string;
	}

	public function remove_spaces_from_string($string) {
		$string = str_replace(' ', '', $string);
		$string = preg_replace('/\s*/m', '', $string);
		return $string;
	}

	public function get_sanitized_credit_card_data($cc_number = null, $cc_exp_date = null, $cc_cvc = null) {
		if ($cc_number) {
			$cc_number = sanitize_text_field($this->remove_spaces_from_string($cc_number));
		}

		if ($cc_exp_date) {
			// put expiration date in correct format
			$cc_exp_date = explode('/', sanitize_text_field($cc_exp_date));
			$cc_exp_month = $this->remove_spaces_from_string($cc_exp_date[0]);
			$cc_exp_year = $this->remove_spaces_from_string($cc_exp_date[1]);
			if (strlen($cc_exp_year) == 2) {
				$cc_exp_year += 2000;
			}
			$cc_exp_date = $cc_exp_year.'-'.$cc_exp_month;
		}

		if ($cc_cvc) {
			$cc_cvc = sanitize_text_field($cc_cvc);
		}

		return array(
			'cc_number' => $cc_number,
			'cc_exp_date' => $cc_exp_date,
			'cc_cvc' => $cc_cvc
		);
	}

	public function create_trans_ref_id() {
		return 'ref'.time();
	}

} // class WPD_WC_Authorizenet_Gateway
}

do_action('wpd_authnet_after_gateway_class');