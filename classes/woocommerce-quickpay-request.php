<?php
/**
 * WC_Quickpay_Request class
 *
 * @class 		WC_Quickpay_Request
 * @version		1.0.0
 * @package		Woocommerce_Quickpay/Classes
 * @category	Class
 * @author 		PerfectSolution
 */
class WC_Quickpay_Request {

	/* Contains the API protocol number */
	public static $protocol = 7;

	/* Contains the API response */
	public $response;

	/* Contains the current order object */
	private $order;

	/* Contains the cURL ressource object */
	private $ch;

	/* Gateway settings holder */
	private $settings;

	/* Contains an array of fields used to check the API response MD5 check sum */
	protected static $__api_md5Fields = array(
		'msgtype',
        'ordernumber',
        'amount',
        'balance',
        'currency',
        'time',
        'state',
        'qpstat',
        'qpstatmsg',
        'chstat',
        'chstatmsg',
        'merchant',
        'merchantemail',
        'transaction',
        'cardtype',
        'cardnumber',
        'cardhash',
        'cardexpire',
        'acquirer',
        'splitpayment',
        'fraudprobability',
        'fraudremarks',
        'fraudreport',
        'fee'
	);


	/**
	* __construct function.
	*
	* @access public
	* @return void
	*/
	public function __construct( $order = NULL, $settings = NULL ) {
		add_action('shutdown', array($this, 'shutdown'));

		if( is_object( $order ) ) {
			$this->order = $order;
		}

		$this->settings = $settings;
	}


	/**
	* set_order_id function.
	*
	* @access public
	* @return void
	*/
	public function set_order( $order ) {
		if( is_object( $order ) ) {
			$this->order = $order;
		}
	}


	/**
	* remote_instance function.
	*
	* Create a cURL instance if none exists already
	*
	* @access public
	* @return void
	*/
	private function remote_instance() {
		if( $this->ch === NULL ) {
			$this->ch = curl_init();
			curl_setopt( $this->ch, CURLOPT_URL, 'https://secure.quickpay.dk/api' );
			curl_setopt( $this->ch, CURLOPT_POST, TRUE );
			curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, TRUE );
			curl_setopt( $this->ch, CURLOPT_SSL_VERIFYPEER , FALSE );
		}

		return $this->ch;		 	
	}


	/**
	* do_request function.
	*
	* Create a cURL instance if none exists already
	*
	* @access public
	* @return void
	*/
	public function do_request( $params ) {
		$params_string = '';

		foreach( $params as $key => $value ) {
			$params_string .= $key . '=' . $value . '&';
		}

		$params_string = rtrim( $params_string,'&' );	

		$this->remote_instance();

		curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params_string . '&md5check=' . $this->md5_request( $params ) );

		$response = simplexml_load_string( curl_exec( $this->ch ) );

		if( $this->validate_api_response( $response ) ) {
			return $response;
		}

		$this->debug($response);

		return new WP_Error( 'request-error', $response->qpstatmsg );
	}


	/**
	* do_capture function.
	*
	* Does a CAPTURE request to the Quickpay API.
	*
	* @access public
	* @return void
	*/
	public function do_capture() {
		$response = $this->do_request( $this->set_request_params( 'capture', array( 'amount' => WC_Quickpay_Helper::price_multiply( $this->order->order_total ), 'finalize' => 1 ) ) );
		
		if( ! is_wp_error($response)) {
			$this->order->note('Payment captured.');
			return TRUE;
		} else {
			$this->debug( $response );
			return FALSE;
		}
	}


	/**
	* do_cancel function.
	*
	* Does a CANCEL request to the Quickpay API.
	*
	* @access public
	* @return void
	*/
	public function do_cancel() {
		$response = $this->do_request( $this->set_request_params( 'cancel' ) );

		if( ! is_wp_error( $response ) ) {
			if( WC_Quickpay_Helper::subscription_is_active() ) {
				if( WC_Subscriptions_Order::order_contains_subscription( $this->order ) ) {
					WC_Subscriptions_Manager::cancel_subscriptions_for_order( $this->order->id );
				}
			}
			$this->order->note('Payment cancelled.');
			return TRUE;		
		} else {
			$this->debug( $response );
			return FALSE;
		}
	}


	/**
	* do_renew function.
	*
	* Does a RENEW request to the Quickpay API.
	*
	* @access public
	* @return void
	*/
	public function do_renew() {
		$response = $this->do_request( $this->set_request_params( 'renew' ) );	
		if( ! is_wp_error( $response ) ) {
			$this->order->note( __( 'Payment renewed', 'woo-quickpay' ) );
			return TRUE;
		} else {
			$this->debug( $response );
			return FALSE;
		}
	}


	/**
	* do_refund function.
	*
	* Does a REFUND request to the Quickpay API.
	*
	* @access public
	* @return void
	*/
	public function do_refund() {
		$response = $this->do_request( $this->set_request_params( 'refund', array( 'amount' => WC_Quickpay_Helper::price_multiply( $this->order->order_total ) ) ) );
		if( ! is_wp_error( $response ) ) {
			$this->order->note( __( 'Payment refunded', 'woo-quickpay' ) );
			return TRUE;
		} else {
			$this->debug( $response );
			return FALSE;
		}
	}


	/**
	* do_recurring function.
	*
	* Does a RECURRING request to the Quickpay API.
	*
	* @access public
	* @return void
	*/
	public function do_recurring( $amount = NULL ) {
		$response = $this->do_request( $this->set_request_params( 'recurring', array(
			'ordernumber' => time() . 'qp' . $this->order->get_clean_order_number(), 
			'amount' => WC_Quickpay_Helper::price_multiply( isset( $amount ) ? $amount : WC_Subscriptions_Order::get_recurring_total( $this->order ) ),
			'currency' => $this->settings['quickpay_currency'],
			'autocapture' => 1
		) ) );

		if( ! is_wp_error( $response ) ) {
			$this->order->note( __('Recurring payment captured', 'woo-quickpay' ) );
			return TRUE;
		} else {
			$this->order->note( __('Recurring payment failed', 'woo-quickpay' ) );
			$this->debug( $response );
			return FALSE;
		}
	}


	/**
	* do_splitcapture function.
	*
	* Does a SPLITCAPTURE request to the Quickpay API.
	*
	* @access public
	* @param $amount - the amount to capture from the transaction
	* @param $finalize - if this is the last capture on the order, set the finalize to 1 to close it
	* @param $currency - the currency used in the gateway
	* @return void
	*/
	public function do_splitcapture( $amount, $finalize = 0) {		
		$response = $this->do_request( $this->set_request_params( 'capture', array('amount' => WC_Quickpay_Helper::price_multiply( $amount ), 'finalize' => $finalize ) ) );

		if( ! is_wp_error( $response ) ) {
			$this->order->note( 'Split capture: ' . WC_Quickpay_Helper::price_normalize( $amount ) . $this->settings['quickpay_currency'] );
		} else {
			$this->debug( $response );
		}
	}


	/**
	* set_request_params function.
	*
	* Set the request parameters neccessarry for the upcoming API request
	*
	* @access public
	* @return array
	*/
	public function set_request_params($action, $fields = array()) {
		$params = array(
			'protocol' => self::$protocol,
			'msgtype'  => $action,
			'merchant' => $this->settings['quickpay_merchantid']
		);

		$append = array(
			'transaction' => $this->order->get_transaction_id(),
			'apikey' => $this->settings['quickpay_apikey'],
			'secret' => $this->settings['quickpay_md5secret']
		);

		if( ! empty( $fields ) ) {
			foreach( $fields as $key => $value ) {
				$params[$key] = $value;
			}
		}

		return array_merge( $params, $append );
	}


	/**
	* get_payment_status function.
	*
	* Does a STATUS request to the Quickpay API. If the request was successful, we will return either the state, msgtype or the balance depending on the $field parameter.
	*
	* @access public
	* @param $field - the name of the object field we wish to retrieve information about.
	* @return string / boolean
	*/
	public function get_payment_status( $field ) {
		$response = $this->do_request(
						array(
							'protocol' => self::$protocol,
							'msgtype' => 'status',
							'merchant' => $this->settings['quickpay_merchantid'],
							'transaction' => $this->order->get_transaction_id(),
							'apikey' => $this->settings['quickpay_apikey'],
							'secret' => $this->settings['quickpay_md5secret']
						)
					);

		if( ! is_wp_error( $response ) ) {
			if( $field == 'state' ) {
				return $response->state;
			}		
			else if( $field == 'msgtype' ) {
				return $response->history[count( $response->history ) -1]->msgtype;
			}		
			else if( $field == 'balance' ) {
				return $response->balance;
			}			
		} else {
			$this->debug( $response );
		}

		return FALSE;
	}


	/**
	* is_action_allowed function.
	*
	* Check if the action we are about to perform is allowed according to the current transaction state.
	*
	* @access public
	* @return boolean
	*/
	public function is_action_allowed( $action, $state = NULL ) {
		if( $state === NULL ) {
			$state = $this->get_payment_status( "state" );
		}

		$allowed_states = array(
			'capture' => array( 1 ),
			'cancel' => array( 1, 9 ),
			'refund' => array( 3 ),
			'renew' => array( 1 ),
			'splitcapture' => array( 1, 3 ),
			'regular_header' => array( 1, 9, 3 ),
			'recurring' => array( 9 )
		);

		if( $action == 'splitcapture' ) {
			$allowed_states['capture'] = array( 1,3 );
		}

		return in_array( $state, $allowed_states[$action] );
	}


	/**
	* action_router function.
	*
	* Routes an API request to the correct method
	*
	* @access public
	* @return boolean
	*/
	public function action_router( $order_id, $action, $extras = array() ) {
		if( ! isset( $this->order ) ) {
			$this->order = new WC_Order( $order_id );
		}

		$allowed_actions = $this->allowed_actions();

		if( in_array( $action, $allowed_actions ) ) {
			if( ! empty( $extras ) ) {
				return call_user_func_array( array( $this, 'do_' . $action ), $extras );
			} else {
				return call_user_func( array( $this, 'do_' . $action ) );
			}
		}	
	}


	/**
	* validate_api_response function.
	*
	* Validates the response from the Quickpay API based on the returned object
	*
	* @access public
	* @param $response - the Quickpay response object
	* @return string
	*/
	public function validate_api_response( $response ) {
		if( isset( $response->qpstat ) ) {
			if( $this->md5_response( $response ) == $response->md5check AND $response->qpstat == '000' ) {
				return TRUE;
			}
		}	
		return FALSE;	
	}


	/**
	* md5_request function.
	*
	* Prepares an MD5 hashed string used in the API request calls
	*
	* @access public
	* @param $settings - array of gateway settings
	* @return string
	*/
	public function md5_request( $settings ) {
		return md5( implode('' , $settings) );
	}


	/**
	* md5_api_response function.
	*
	* Creates and MD5 hashed string based on the API response params
	*
	* @access public
	* @param $r - Quickpay API response object
	* @return string
	*/
	public function md5_response( $r ) {
		if( is_object( $r ) ) {

			$md5_string = '';

			foreach( static::$__api_md5Fields as $field ) {
				if( isset( $r->$field ) ) {
					$md5_string .= (string) $r->$field;
				}
			}

			return md5( $md5_string . $this->settings['quickpay_md5secret'] );			
		}

		return FALSE;
	}


	/**
	* allowed_actions function.
	*
	* Returns an array of possible request types
	*
	* @access public
	* @return array
	*/
	public function allowed_actions() {
		return array( 'capture', 'cancel', 'refund', 'splitcapture', 'renew', 'subscribe', 'recurring' );
	}


	/**
	* debug function.
	*
	* Either writes to the error log or prints the error depending on the settings.
	*
	* @access public
	* @return array
	*/
	public function debug( $response ) {
		if( isset( $this->settings['quickpay_debug'] ) ) {
			if( $this->settings['quickpay_debug'] ) {
				if(WP_DEBUG_LOG) {
					error_log( print_r( $response, TRUE ) );
				} else {
					error_log( print_r( $response, TRUE ) );
					var_dump( $response );
				}
			}			
		}
	}


	/**
	* shutdown function.
	*
	* Closes the current cURL connection
	*
	* @access public
	* @return void
	*/
	public function shutdown() {
		if( ! empty( $this->ch ) ) {
			curl_close( $this->ch );
		}		
	}
}
?>