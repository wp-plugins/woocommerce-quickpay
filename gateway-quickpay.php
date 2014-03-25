<?php
/*
Plugin Name: WooCommerce Quickpay
Plugin URI: http://wordpress.org/plugins/woocommerce-quickpay/
Description: Integrates your Quickpay payment getway into your WooCommerce installation.
Version: 2.1.4 
Author: Perfect Solution
Text Domain: woo-quickpay
Author URI: http://perfect-solution.dk
*/

add_action('plugins_loaded', 'init_quickpay_gateway', 0);

function init_quickpay_gateway() {

	if ( ! class_exists( 'WC_Payment_Gateway' )) { return; }

	class WC_Quickpay extends WC_Payment_Gateway {
		const PROTOCOL = 7;
		private $allowed_actions = array('capture', 'cancel', 'refund', 'splitcapture','renew','subscribe','recurring'),
				$gateway,
				$order,
				$ch,
				$payment_cancelled = 0;

		public function __construct() {
		    $this->id				= 'quickpay';
		    $this->icon 			= '';
		    $this->has_fields 		= false;	

		    $this->supports = array( 'subscriptions', 'products', 'subscription_cancellation', 'subscription_reactivation', 'subscription_suspension' , 'subscription_amount_changes', 'subscription_date_changes');

			// Load the form fields and settings
			$this->init_form_fields();
			$this->init_settings();

			// Get gateway variables
			$this->title = $this->settings['title'];
			$this->description = $this->settings['description'];
			$this->instructions = isset($this->settings['instructions']) ? $this->settings['instructions'] : '';

			$test_mode = isset($this->settings['quickpay_testmode']) ? $this->settings['quickpay_testmode'] : 'no';

			$this->gateway = (object) array(
				'protocol' => self::PROTOCOL,
				'merchant' => $this->settings['quickpay_merchantid'],
				'apikey' => $this->settings['quickpay_apikey'],
				'secret' => $this->settings['quickpay_md5secret'],
				'language' => $this->settings['quickpay_language'],
				'currency' => $this->settings['quickpay_currency'],
				'testmode' => $test_mode == 'yes' ? 1 : 0,
				'autocapture' => $this->settings['quickpay_autocapture'] == 'yes' ? 1 : 0,
				'cardtypelock' => $this->settings['quickpay_cardtypelock'],
				'splitcapture' => $this->settings['quickpay_splitcapture'],
				'captureoncomplete' => $this->settings['quickpay_captureoncomplete'],
				'ibillOrCreditcard' => $this->settings['quickpay_ibillOrCreditcard']
			);

		    // Actions
		    add_action('init', array( $this, 'load_i18n' ) );
		    add_action('woocommerce_api_wc_quickpay', array($this, 'check_quickpay_response'));    
		    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );	    
		    add_action('woocommerce_receipt_quickpay', array($this, 'receipt_page'));
		    add_action('scheduled_subscription_payment_quickpay', array($this, 'scheduled_subscription_payment'), 10, 3);
		    add_action('shutdown', array($this, 'shutdown'));

			add_filter('manage_shop_order_posts_custom_column',array($this, 'add_custom_order_data'));	
		 	add_action('wp_before_admin_bar_render', array($this, 'api_check_action'));
		    add_action('add_meta_boxes', array($this, 'quickpay_meta_boxes'));
		    add_action('woocommerce_order_status_completed', array($this, 'api_capture_on_order_status_complete'));
		    add_action('admin_menu', array($this, 'js_enqueue'));
		    add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 2 );
		}

		// Load i18n
		public function load_i18n() {
			load_plugin_textdomain( 'woo-quickpay' , FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
		}

		// Show action links on the "Installed plugins" overview
		public static function action_links( $links ) {
			$links = array_merge( array(
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_quickpay' ) . '">' . __( 'Settings', 'woo-quickpay' ) . '</a>',
			), $links );

			array_push( $links, '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JWDJPFHNVS5BG"><img style="vertical-align:middle;" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" alt="PayPal - The safer, easier way to pay online!" /></a>' );
			
			return $links;
		}

		// This check is runned every time a page request is made
		// -> Check if we are requesting a payment capture
		public function api_check_action() {
			if(is_admin()) {
				if(isset($_GET['quickpay_action']) AND in_array($_GET['quickpay_action'], $this->allowed_actions) AND isset($_GET['post'])) {
					global $woocommerce;
					$this->order = new WC_Order( intval( $_GET['post'] ) );

					if( $this->api_is_action_allowed( $_GET['quickpay_action'] ) ) {
						$this->api_action_router($this->order->id , $_GET['quickpay_action']);
					}
				}
			}
		}

		public function api_capture_on_order_status_complete() {
			global $order, $post, $woocommerce;	
			
			$post_ids = array();

			if(isset($_GET['post']) AND is_array($_GET['post'])) {
				$post_ids = $_GET['post'];
			} elseif($post->ID === NULL) {
				if(isset($_GET['order_id'])) {
					$post_ids = array($_GET['order_id']);
				}
			}else {
				$post_ids = array($post->ID);
			}

			if( ! empty($post_ids)) {
				foreach($post_ids as $post_id) {
					$this->order = new WC_Order( $post_id );

					if($this->settings['quickpay_captureoncomplete'] == 'yes') {
						if($this->get_transaction_id() && $this->api_is_action_allowed('capture')) {
							$this->api_action_capture();
						}	
					}	
				}		
			}
		}

		private function api_action_router( $order_id, $action ) {
			if( ! isset($this->order))
				$this->order = new WC_Order( $order_id );

			if(in_array($action, $this->allowed_actions)) {
				call_user_func(array($this, 'api_action_' . $action));
			}	
		}

		private function api_action_capture() {
			$response = WC_Quickpay_API::request($this->set_request_params('capture', array('amount' => $this->format_price($this->order->order_total), 'finalize' => 1)));
			if( ! is_wp_error($response)) {
				$this->note('Payment captured.');
			}
		}

		private function api_action_cancel() {
			$response = WC_Quickpay_API::request($this->set_request_params('cancel'));

			if( ! is_wp_error($response)) {
				if($this->subscr_is_active()) {
					if(WC_Subscriptions_Order::order_contains_subscription( $this->order )) {
						WC_Subscriptions_Manager::cancel_subscriptions_for_order( $this->order->id );
					}
				}

				$this->note('Payment cancelled.');		
			}
		}

		private function api_action_renew() {
			$response = WC_Quickpay_API::request($this->set_request_params('renew'));	
			if( ! is_wp_error($response)) {
				$this->note( __( 'Payment renewed', 'woo-quickpay' ) );
			}
		}

		private function api_action_refund() {
			$response = WC_Quickpay_API::request($this->set_request_params('refund', array('amount' => $this->format_price($this->order->order_total))));
			if( ! is_wp_error($response)) {
				$this->note( __('Payment refunded', 'woo-quickpay' ) );
			}		
		}

		private function api_action_splitcapture() {	
			if(! isset($_GET['amount']) )
				return FALSE;
			
			$finalize = (isset($_GET['quickpay_finalize']) AND $_GET['quickpay_finalize'] == 'yes') ? 1 : 0;
			$response = WC_Quickpay_API::request($this->set_request_params('capture', array('amount' => $this->format_price( $_GET['amount'] ), 'finalize' => $finalize)));

			if( ! is_wp_error($response)) {
				$this->note('Split capture: ' . $this->deformat_price($amount).$this->gateway->currency);
			}
		}

		private function api_action_recurring($amount = NULL) {
			$response = WC_Quickpay_API::request($this->set_request_params('recurring', array(
				'ordernumber' => time().'qp'.$this->get_order_number(), 
				'amount' => $this->format_price( isset($amount) ? $amount : WC_Subscriptions_Order::get_recurring_total( $this->order ) ),
				'currency' => $this->gateway->currency,
				'autocapture' => 1
			)));

			if( ! is_wp_error($response)) {
				$this->note( __('Recurring payment captured', 'woo-quickpay' ) );
				return TRUE;
			} else {
				$this->note( __('Recurring payment failed', 'woo-quickpay' ) );
				return FALSE;
			}
		}

		private function set_request_params($action, $fields = array()) {
			$params = array(
				'protocol' => $this->gateway->protocol,
				'msgtype'  => $action,
				'merchant' => $this->gateway->merchant
			);
			$append = array(
				'transaction' => $this->get_transaction_id(),
				'apikey' => $this->gateway->apikey,
				'secret' => $this->gateway->secret
			);
			if( ! empty($fields)) {
				foreach($fields as $key => $value) {
					$params[$key] = $value;
				}
			}
			return array_merge($params, $append);
		}

		private function api_payment_status( $field ) {
			$response = WC_Quickpay_API::request(
							array(
								'protocol' => $this->gateway->protocol,
								'msgtype' => 'status',
								'merchant' => $this->gateway->merchant,
								'transaction' => $this->get_transaction_id(),
								'apikey' => $this->gateway->apikey,
								'secret' => $this->gateway->secret
							)
						);

			if( ! is_wp_error($response)) {
				if( $field == 'state' )
					return $response->state;		
				else if( $field == 'msgtype' )	
					return $response->history[count($response->history) -1]->msgtype;
				else if( $field == 'balance' )
					return $response->balance;
			} 

			return FALSE;
		}

		private function api_is_action_allowed($action, $state = NULL) {
			if($state === NULL) {
				$state = $this->api_payment_status("state");
			}

			$allowed_states = array(
				'capture' => array(1),
				'cancel' => array(1, 9),
				'refund' => array(3),
				'renew' => array(1),
				'splitcapture' => array(1,3),
				'regular_header' => array(1,9,3),
				'recurring' => array(9)
			);

			if($action === 'splitcapture') {
				$allowed_states['capture'] = array(1,3);
			}

			if(in_array($state, $allowed_states[$action]))
				return TRUE;
			else
				return FALSE;
		}

		private function note($message) {
			if(is_object($this->order)) {
				$this->order->add_order_note('Quickpay: ' . $message);
			}
		}
	
		public function payment_fields() {
			if ($this->description) echo wpautop(wptexturize($this->description));

			if($this->gateway->ibillOrCreditcard === 'yes') {
				$labelViaBill = ( ! empty($this->settings['quickpay_labelViaBill'])) ? $this->settings['quickpay_labelViaBill'] : 'viaBill';
				$labelCreditCard = ( ! empty($this->settings['quickpay_labelCreditCard'])) ? $this->settings['quickpay_labelCreditCard'] : __( 'Credit card', 'woo-quickpay' );
				echo '<ul style="list-style:none;">';
				echo '<li><input style="margin:0;" type="radio" name="quickpay-gwType" value="creditcard" checked/> ' . $labelCreditCard . '</li>';
				echo '<li><input style="margin:0;" type="radio" name="quickpay-gwType" value="viabill" /> '. $labelViaBill .'</li>';
				echo '</ul>';				
			}
		}
		 
		public function receipt_page( $order ) {	
			$this->js_enqueue();		
			echo $this->generate_quickpay_button( $order );
		}
	
		public function process_payment( $order_id ) {
			global $woocommerce;
			$woocommerce->cart->empty_cart();

			if( ! isset($this->order))
				$this->order = new WC_Order( $order_id );

			$gwType = NULL;

			if($this->gateway->ibillOrCreditcard === 'yes') {
				if(isset($_POST['quickpay-gwType']) AND in_array($_POST['quickpay-gwType'], array('viabill', 'creditcard'))) {
					$gwType = $_POST['quickpay-gwType'];
				}
			}	

			return array(
				'result' 	=> 'success',
				'redirect'	=>  add_query_arg( 'gwType', $gwType, $this->order->get_checkout_payment_url( true ))
			);	
		}

		public function scheduled_subscription_payment($amount_to_charge, $order, $product_id) {
			$this->order = $order;
			
			if( $this->api_action_recurring( $amount_to_charge ) ) {
				WC_Subscriptions_Manager::process_subscription_payments_on_order( $this->order->id );
			}
		}

		private function generate_quickpay_button( $order_id ) {
			$this->order = new WC_Order( $order_id );
			$subscription = ($this->subscr_is_active()) ? WC_Subscriptions_Order::order_contains_subscription( $this->order ) : FALSE;
			$ordernumber 	= substr(md5(time()), 0, 3).'-QP-'.str_pad($this->get_order_number() , 4, 0, STR_PAD_LEFT);
			$query_args_cancellation = array('order' => $order_id, 'payment_cancellation' => 'yes');
			$query_args_callback = array('order' => $order_id, 'qp_callback' => 'true');
			$continueurl	= $this->get_continue_url();
			$cancelurl		= $this->get_cancellation_url();
			$callbackurl	= str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Quickpay', home_url( '/' ) ) );

			if($subscription) {
				$msgtype = 'subscribe';
				$amount = $this->format_price(WC_Subscriptions_Order::get_total_initial_payment( $this->order ));
				$description = 'qp_subscriber';
			} else {
				$msgtype = 'authorize';
				$amount = $this->format_price($this->order->order_total);
				$description = '';
			}
			
			if($this->settings['quickpay_ibillOrCreditcard'] === 'yes' AND isset($_GET['gwType'])  AND (strtolower($_GET['gwType']) === 'viabill') ) {
				$cardtypelock = strtolower($_GET['gwType']);
			} else {
				$cardtypelock = $this->gateway->cardtypelock;
			}

			$md5check = md5(
				self::PROTOCOL . $msgtype . $this->settings['quickpay_merchantid'] . $this->settings['quickpay_language'] . $ordernumber
				.$amount . $this->settings['quickpay_currency'] . $continueurl . $cancelurl . $callbackurl . $this->gateway->autocapture
				.$cardtypelock . $description . $this->gateway->testmode . $this->settings['quickpay_md5secret']
			);

			if( array_key_exists('quickpay_redirectText', $this->settings) ) {
				echo $this->settings['quickpay_redirectText'];
			}
			
			echo '
				<form id="quickpay_payment_form" action="https://secure.quickpay.dk/form/" method="post">
					<input type="hidden" name="protocol" value="'.self::PROTOCOL.'" />
					<input type="hidden" name="msgtype" value="'.$msgtype.'" />
					<input type="hidden" name="merchant" value="'.$this->gateway->merchant.'" />
					<input type="hidden" name="language" value="'.$this->gateway->language.'" />
					<input type="hidden" name="ordernumber" value="'.$ordernumber.'" />
					<input type="hidden" name="amount" value="'.$amount.'" />
					<input type="hidden" name="currency" value="'.$this->gateway->currency.'" />
					<input type="hidden" name="continueurl" value="'.$continueurl.'" />
					<input type="hidden" name="cancelurl" value="'.$cancelurl.'" />
					<input type="hidden" name="callbackurl" value="'.$callbackurl.'" />
					<input type="hidden" name="autocapture" value="'.$this->gateway->autocapture.'" />
					<input type="hidden" name="cardtypelock" value="'.$cardtypelock.'" />';
			
			if($subscription) {
				echo'<input type="hidden" name="description" value="'.$description.'" />';
			}

			echo '	<input type="hidden" name="testmode" value="'.$this->gateway->testmode.'" />		
					<input type="hidden" name="md5check" value="'.$md5check.'" />
					<input type="submit" value="'.$this->settings['quickpay_paybuttontext'].'" />
				</form>
			';
		}

		public function on_order_cancellation($order_id) {
			global $woocommerce;

			if( ! isset($this->order))	
				$this->order = new WC_Order( $order_id );

			// Redirect the customer to account page if the current order is failed
			if($this->order->status == 'failed') {
				$payment_failure_text = printf( __( '<p><strong>Payment failure</strong> A problem with your payment on order <strong>#%i</strong> occured. Please try again to complete your order.</p>', 'woo-quickpay' ), $this->order->id );
				$woocommerce->add_error('<p><strong>' . __('Payment failure', 'woo-quickpay') . '</strong>: '. $payment_failure_text . '</p>');
				wp_redirect(get_permalink(get_option('woocommerce_myaccount_page_id')));
			}	

			$this->order->add_order_note( __('Quickpay Payment', 'woo-quickpay') . ': ' . __('Cancelled during process', 'woo-quickpay') );
			$woocommerce->add_error('<p><strong>' . __('Payment cancelled', 'woo-quickpay') . '</strong>: ' . __( 'Due to cancellation of your payment, the order process was not completed. Please fulfill the payment to complete your order.', 'woo-quickpay' ) .'</p>' );
		}

		public function create_md5($p) {

			$cardexpire = isset($p['cardexpire']) ? $p['cardexpire'] : '';

			$md5 =  $p['msgtype'].
				    $p['ordernumber'].
				    $p['amount'].
				    $p['currency'].
				    $p['time'].
				    $p['state'].
				    $p['qpstat'].
				    $p['qpstatmsg'].
				    $p['chstat'].
				    $p['chstatmsg'].
				    $p['merchant'].
				    $p['merchantemail'].
				    $p['transaction'].
				    $p['cardtype'].
				    $p['cardnumber'].
				    $p['cardhash'].
				    $cardexpire.
				    $p['acquirer'].
				    $p['splitpayment'].
				    $p['fraudprobability'].
				    $p['fraudremarks'].
				    $p['fraudreport'].
				    $p['fee'].
				    $this->gateway->secret;
			
			return md5($md5);
		}

		public function check_quickpay_response() {
			$response = isset($_POST['qpstat']) ? (object) $_POST : FALSE;
			if($response) {
				// Fetch order number;
				preg_match('/\d{4,}$/', $response->ordernumber, $order_number);
				$order_number = (int) end($order_number);

				$order_number = $this->find_order_by_order_number($order_number);

				$this->order = new WC_Order($order_number);

				// Update post meta
				update_post_meta( $this->order->id, 'TRANSACTION_ID', esc_attr($response->transaction) );

				// Check if order is a subscription
				$subscription = ($this->subscr_is_active()) ? WC_Subscriptions_Order::order_contains_subscription( $this->order ) : FALSE;

				if(WC_Quickpay_API::validate_response($response, $this->settings['quickpay_md5secret'])) {

					// Add transaction fee
					$this->add_order_transaction_fee( $response->amount );

					if($subscription) {
						// Calculates the total amount to be charged at the outset of the Subscription taking into account sign-up fees, 
						// per period price and trial period, if any.
						$subscription_initial_payment = WC_Subscriptions_Order::get_total_initial_payment( $this->order );

						if($this->order->status !== 'completed') {
							$this->order->update_status('completed');

							// Only make an instant payment if there is an initial payment
							if($subscription_initial_payment > 0 AND $response->msgtype === 'subscribe')				
								$this->api_action_recurring();
						}	
						
						if($response->msgtype === 'subscribe') {
							$this->order->payment_complete();		
						}

					} else {
						$this->order->payment_complete();
						$this->note( __('Payment authorized', 'woo-quickpay') );
					}
				} else {
					$failed_note = printf( __( 'Quickpay payment FAILED. Message from Quickpay: %S', 'woo-quickpay'), $response->qpstatmsg );
					$this->order->add_order_note( $failed_note );
					if($subscription) {
						WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $this->order );
					} else {
						$this->order->update_status('failed');
					}
				}
			}
		}

		private function get_order_number() {
			$order_number = null;
			if(isset($this->order)) {
				$order_number = str_replace('#','', $this->order->get_order_number());
			}

			return $order_number;
		}

		public function find_order_by_order_number( $order_number ) {	
			$order_id = null;	

			// search for the order by custom order number
			$query_args = array(
						'numberposts' => 1,
						'meta_key'    => '_order_number',
						'meta_value'  => $order_number,
						'post_type'   => 'shop_order',
						'post_status' => 'publish',
						'fields'      => 'ids'
					);
			
			$post = get_posts( $query_args );
			if( ! empty( $post ) ) {
				list( $order_id ) = $post;
			}
			
			// order was found
			if ( $order_id !== null ) return $order_id;
			
			// if we didn't find the order, then it may be that this plugin was disabled and an order was placed in the interim
			$order = new WC_Order( $order_number );
			if ( isset( $order->order_custom_fields['_order_number'][0] ) ) {
				// _order_number was set, so this is not an old order, it's a new one that just happened to have post_id that matched the searched-for order_number
				return 0;
			}
			
			return $order->id;
		}

		public function shutdown() {
			if( ! empty($this->ch)) {
				curl_close($this->ch);
			}		
		}

		public function init_form_fields() {
	    	$this->form_fields = array(
				'enabled' => array(
								'title' => __( 'Enable/Disable', 'woo-quickpay' ), 
								'type' => 'checkbox', 
								'label' => __( 'Enable Quickpay Payment', 'woo-quickpay' ), 
								'default' => 'yes'
							), 

				'_Account_setup' => array(
					'type' => 'title',
					'title' => __( 'Quickpay account', 'woo-quickpay' ),
				),
					'quickpay_testmode' => array(
						'title' => __( 'Enable testmode', 'woo-quickpay' ), 
						'type' => 'checkbox', 
						'label' => __( 'Enable testmode', 'woo-quickpay' ), 
						'default' => 'no'
					),
					'quickpay_merchantid' => array(
									'title' => __('Quickpay Merchant ID', 'woo-quickpay'),
									'type' => 'text',
									'description' => __('Type in your merchant ID from Quickpay.', 'woo-quickpay')
					),
					'quickpay_md5secret' => array(
									'title' => __('Secret MD5 string', 'woo-quickpay'),
									'type' => 'text',
									'description' => __('This is the unique MD5 secret key, which the system uses to verify your transactions.')
					),
					'quickpay_apikey' => array(
						'title' => __('Quickpay API key', 'woo-quickpay'),
						'type' => 'text',
						'description' => __( 'The API key is unique and can be requested from within the Quickpay Administrator Tool', 'woo-quickpay' )
					),	

				'_Extra_gateway_settings' => array(
					'type' => 'title',
					'title' => 'Extra gateway settings'
				),
					'quickpay_language' => array(
									'title' => __('Language', 'woo-quickpay'),
									'description' => __('Payment Window Language', 'woo-quickpay'),
									'type' => 'select',
									'options' => array(
													'da' => 'Danish',
													'de'=>'German', 
													'en'=>'English', 
													'fr'=>'French', 
													'it'=>'Italian', 
													'no'=>'Norwegian', 
													'nl'=>'Dutch', 
													'pl'=>'Polish', 
													'se'=>'Swedish'
													)
					),
					'quickpay_currency' => array(
									'title' => __('Currency', 'woo-quickpay'),
									'description' => __('Choose your currency. Please make sure to use the same currency as in your WooCommerce currency settings.', 'woo-quickpay' ),
									'type' => 'select',
									'options' => array(
													'DKK' => 'DKK', 
													'EUR' => 'EUR',
													'GBP' => 'GBP',
													'NOK' => 'NOK',
													'SEK' => 'SEK',
													'USD' => 'USD'
													)
					),
					'quickpay_cardtypelock' => array(
									'title' => __( 'Cardtype lock', 'woo-quickpay' ), 
									'type' => 'text', 
									'description' => __( 'Default: creditcard. Type in the cards you wish to accept (comma separated). See the valid payment types here: <b>http://quickpay.dk/features/cardtypelock/</b>', 'woo-quickpay' ), 
									'default' => 'creditcard'					
					),	
					'quickpay_splitcapture' => array(
									'title' => __( 'Enable split payments', 'woo-quickpay' ), 
									'type' => 'checkbox', 
									'label' => __( 'Accept split payments in your system.', 'woo-quickpay' ), 
									'description' => __( 'Remember to turn this on in your Quickpay Manager. Click for <a target="_blank" href="http://quickpay.dk/features/split-payment/">help</a>', 'woo-quickpay' ), 
									'default' => 'yes'
					),
					'quickpay_autocapture' => array(
									'title' => __( 'Allow autocapture', 'woo-quickpay' ), 
									'type' => 'checkbox', 
									'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
									'description' => __( 'Automatically capture payments.', 'woo-quickpay' ), 
									'default' => 'no'
					),
					'quickpay_captureoncomplete' => array(
									'title' => __( 'Capture on complete', 'woo-quickpay' ), 
									'type' => 'checkbox', 
									'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
									'description' => __( 'When enabled quickpay payments will automatically be captured when order state is set to "Complete".', 'woo-quickpay'), 
									'default' => 'no'
					),
					'quickpay_ibillOrCreditcard' => array(
									'title' => __( 'Choose credit card or viaBill on payment selection', 'woo-quickpay' ), 
									'type' => 'checkbox', 
									'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
									'description' => __( 'Allows your customers to choose between viaBill and credit card when choosing type of payment. <b>(Requires viaBill agreement)</b>', 'woo-quickpay' ), 
									'default' => 'no'
					),

				'_Shop_setup' => array(
					'type' => 'title',
					'title' => __( 'Shop setup', 'woo-quickpay' ),
				),
					'title' => array(
									'title' => __( 'Title', 'woo-quickpay' ), 
									'type' => 'text', 
									'description' => __( 'This controls the title which the user sees during checkout.', 'woo-quickpay' ), 
									'default' => __( 'Quickpay', 'woo-quickpay' )
								),
					'description' => array(
									'title' => __( 'Customer Message', 'woo-quickpay' ), 
									'type' => 'textarea', 
									'description' => __( 'This controls the description which the user sees during checkout.', 'woo-quickpay' ), 
									'default' => 'Pay via Quickpay. Allows you to pay with your credit card via Quickpay.'
								),
					'instructions' => array(
						'title'       => __( 'Email instructions', 'woo-quickpay' ),
						'type'        => 'textarea',
						 'description' => __( 'Instructions that will be added to emails.', 'woo-quickpay' ),
						 'default'     => '',
					 ),
					'quickpay_paybuttontext' => array(
									'title' => __( 'Text on payment button', 'woo-quickpay' ), 
									'type' => 'text', 
									'description' => __( 'This text will show on the button which opens the Quickpay window.', 'woo-quickpay' ), 
									'default' => __( 'Open secure Quickpay window.', 'woo-quickpay' )
								),
					'quickpay_labelCreditCard' => array(
									'title' => __( 'Credit card label', 'woo-quickpay' ), 
									'type' => 'text', 
									'description' => __( 'Label shown when choosing credit card or viaBill.', 'woo-quickpay' ), 
									'default' => __( 'Credit card', 'woo-quickpay' )
								),
					'quickpay_labelViaBill' => array(
									'title' => __( 'viaBill label', 'woo-quickpay' ), 
									'type' => 'text', 
									'description' => __( 'Label shown when choosing credit card or viaBill.', 'woo-quickpay' ), 
									'default' => __( 'viaBill', 'woo-quickpay' )
								),
					'quickpay_redirectText' => array(
									'title' => __( 'Redirect message', 'woo-quickpay' ), 
									'type' => 'textarea', 
									'description' => __( 'This message is shown right before the customer is automatically redirected to the Quickpay payment window.', 'woo-quickpay' ), 
									'default' => __('You will be automatically redirected to the payment window in <strong>5 seconds</strong>. Click on the button below if you are not redirected.', 'woo-quickpay' )
								),
				);	

			if($this->subscr_is_active()) {			
				$this->form_fields['woocommerce-subscriptions'] = array(
					'type' => 'title',
					'title' => 'Subscriptions'
				);
				$this->form_fields['quickpay_autodraw_subscription'] = array(
						'title' => __( 'Automatically capture the first payment of a subscription.', 'woo-quickpay' ), 
						'type' => 'checkbox', 
						'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
						'description' => __( 'Automatically capture the first payment of a subscription when order is complete.', 'woo-quickpay' ), 
						'default' => 'yes'
				);
			}
		}

		public function admin_options() {
			?>
			<h3><?php _e('Quickpay', 'woo-quickpay'); ?></h3>
			<p><?php _e('Allows you to receive payments via Quickpay.', 'woo-quickpay'); ?></p>
			<table class="form-table">
				<?php $this->generate_settings_html(); ?>
			</table>	
			<?php
		}

		public function quickpay_meta_boxes() {
			 add_meta_box('quickpay-payment-actions', __('Quickpay Payment', 'woo-quickpay'), array(&$this, 'quickpay_meta_box_payment'), 'shop_order', 'side', 'high');
		}

		public function quickpay_meta_box_payment() {
			global $post, $woocommerce;	
			$this->order = new WC_Order( $post->ID );	
			
			$transaction_id = $this->get_transaction_id();
			$state = $this->api_payment_status('state');
			$balance = $this->api_payment_status('balance');

			if($transaction_id) { ?>		
					<p><strong><?php _e('Current payment state', 'woo-quickpay'); ?>: <?php echo  $this->format_msgtype($this->api_payment_status("msgtype"))?></strong></p>

					<?php if($this->api_is_action_allowed('regular_header', $state)) : ?>
						<h3><?php _e('Standard actions', 'woo-quickpay'); ?></h3>
						<ul class="order_actions">
							<?php if($this->api_is_action_allowed('capture', $state)) : ?>
								<li>
									<a class="button button-primary" onclick="return notify('<?php _e('You are about to CAPTURE this payment', 'woo-quickpay'); ?>');" href="<?php echo admin_url('post.php?post='.$post->ID.'&action=edit&quickpay_action=capture'); ?>"><?php _e('Capture', 'woo-quickpay'); ?></a>
								</li>
							<?php endif; ?>
							<?php if($this->api_is_action_allowed('cancel', $state)) : ?>
								<li>
									<a class="button" onclick="return notify('<?php _e('You are about to CANCEL this payment', 'woo-quickpay'); ?>')" href="<?php echo admin_url('post.php?post='.$post->ID.'&action=edit&quickpay_action=cancel'); ?>"><?php _e('Cancel', 'woo-quickpay'); ?></a>
								</li>
							<?php endif; ?>
							<?php if($this->api_is_action_allowed('refund', $state)) : ?>
								<li>
									<a class="button" onclick="return notify('<?php _e('You are about to REFUND this payment', 'woo-quickpay'); ?>')" href="<?php echo admin_url('post.php?post='.$post->ID.'&action=edit&quickpay_action=refund'); ?>"><?php _e('Refund', 'woo-quickpay'); ?></a>
								</li>
								<li>&nbsp;</li>
							<?php endif; ?>
						</ul>
					<?php endif; ?>
				
					<br />

					<?php if($this->gateway->splitcapture == 'yes' AND $this->api_is_action_allowed('splitcapture', $state) AND $balance < $this->format_price($this->order->order_total)) : ?>
					<h3><?php _e('Split payment', 'woo-quickpay'); ?></h3>
					<ul class="order_actions">
						<li style="text-align:left;"><?php _e('Balance', 'woo-quickpay'); ?>: <?php echo  $this->deformat_price($balance)?> <?php echo $this->gateway->currency ?></li>
						<li style="text-align:left;">
							<span id="quickpay_balance_container">
								<?php _e('Remaining', 'woo-quickpay'); ?>: 							
								<span id="quickpay_balance"><?php echo  $this->deformat_price($this->format_price($this->order->order_total)-$balance)?></span>
								<?php echo  $this->gateway->currency ?>
							</span> 
						</li>
					</ul>
					<ul>
						<li>
							<p>
								<span><input type="text" onkeyup="quickpay_url_modify()" style="width:87%;text-align:right;" id="quickpay_split_amount" name="quickpay_split_amount" /></span><span> <?php echo $this->gateway->currency?>
							</p>
							<p>
								<span><a id="quickpay_split_button" class="button" onclick="return notify('You are about to SPLIT CAPTURE this payment. This means that you will capture the amount stated in the input field. The payment state will remain open.')" href="<?php echo admin_url('post.php?post='.$post->ID.'&action=edit&quickpay_action=splitcapture'); ?>"><?php _e('Split Capture', 'woo-quickpay'); ?></a></span>
								<span><a id="quickpay_split_finalize_button" class="button" onclick="return notify('You are about to SPLIT CAPTURE and FINALIZE this payment. This means that you will capture the amount stated in the input field and that you can no longer capture money from this transaction.')" href="<?php echo admin_url('post.php?post='.$post->ID.'&action=edit&quickpay_action=splitcapture&quickpay_finalize=yes'); ?>"><?php _e('Split and finalize', 'woo-quickpay'); ?></a></span>
							</p>
						</li>
					<?php endif; ?>


					</ul>			
				<?php
			}
		}
		public function email_instructions( $order, $sent_to_admin ) {
			if ( $sent_to_admin || $order->status !== 'processing' && $order->status !== 'completed' || $order->payment_method !== 'quickpay' )
				return;

			if ( $this->instructions )
				echo wpautop( wptexturize( $this->instructions ) );
		}
		
		public function add_custom_order_data($column) {
			global $post, $woocommerce;
			$this->order = new WC_Order( $post->ID );
			
			if(version_compare($woocommerce->version, '2.1', '<')) {
				// Show transaction ID on the overview
				if($column == 'shipping_address') {	
					// Get the transaction status
					$status = $this->api_payment_status('msgtype');

					// Insert transaction id and payment status if any
					$transaction_id = $this->get_transaction_id();

					if($transaction_id) {
						echo '<small class=\"meta\">Transaction id: '.$transaction_id.'</small><br />';		
						echo '<small style="color:'.$this->colors( $status ).'">Payment state: '.$this->format_msgtype($status).'</small>';
					}
				}
			// Versions BELOW 2.1
			} else {
				// Show transaction ID on the overview
				if($column == 'billing_address') {	
					// Get the transaction status
					$status = $this->api_payment_status('msgtype');

					// Insert transaction id and payment status if any
					$transaction_id = $this->get_transaction_id();

					if($transaction_id) {
						echo '<small class=\"meta\">Transaction id: '.$transaction_id.'</small><br />';		
						echo '<small style="color:'.$this->colors( $status ).'">Payment state: '.$this->format_msgtype($status).'</small>';
					}
				}				
			}

		}

		private function get_transaction_id() {
			if(is_object($this->order)) {
				$transaction_id = get_post_meta( $this->order->id , 'TRANSACTION_ID', true);
				if($transaction_id != '') {
					return $transaction_id;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		private function subscr_is_active() {
			if( ! function_exists('is_plugin_active')) {
 				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
 			}

 			if(is_plugin_active('woocommerce-subscriptions/woocommerce-subscriptions.php')) {
				return true;
			} 

			return false;
		}

		private function colors( $msgtype ) {
			$colors = array('capture' => '#85ad74', 'cancel' => '#ad74a2', 'subscribe' => '#85ad74');
			$msgtype = trim($msgtype);

			if(array_key_exists($msgtype, $colors))
				return $colors[$msgtype];
			else 
				return '#7499ad';	
		}

		private function format_price( $price ) {
			return number_format($price * 100, 0, '', '');
		}

		private function deformat_price( $price ) {
			return number_format($price / 100, 2, '.', '');
		}

		private function format_msgtype( $msgtype ) {
			$lc = substr($msgtype , -1);
			if($lc == 'l')
				$append = 'led';
			else
				$append = ($lc == 'e') ? 'd' : 'ed';

			return $msgtype . $append;
		}

		private function js_confirm( $message ) {
			return "javascript:";
		}

		public function js_enqueue() {
		    wp_enqueue_script('core', plugins_url( '/core.js', __FILE__ ), array('jquery'));
		}

		public function get_continue_url() {
			if( method_exists( $this->order, 'get_checkout_order_received_url' ) ) {
				return $this->order->get_checkout_order_received_url();
			}
			return add_query_arg('key', $this->order->order_key, add_query_arg('order', $this->order->id, get_permalink(get_option('woocommerce_thanks_page_id'))));
		}

		public function get_cancellation_url() {
			if( method_exists( $this->order, 'get_cancel_order_url' ) ) {
				return str_replace('&amp;', '&', $this->order->get_cancel_order_url());
			}
			return add_query_arg('key', $this->order->order_key, add_query_arg( 
				array( 
					'order' => $this->order->id, 
					'payment_cancellation' => 'yes'
				), get_permalink(get_option('woocommerce_cart_page_id')))
			);
		}

		public function add_order_transaction_fee( $transaction_total ) {
			$order_total = $this->order->get_order_total() ;
			$order_total_formated = $this->format_price( $order_total );

			if( $transaction_total > $order_total ) {
				$transaction_fee = $transaction_total - $order_total_formated;

				$order_total_updated = $order_total_formated + $transaction_fee;
				$order_total_updated = $this->deformat_price( $order_total_updated );

				$transaction_fee = $this->deformat_price( $transaction_fee );

				$order_meta_item_id = woocommerce_add_order_item( $this->order->id,  array(
					'order_item_name' => __( 'Payment Fee', 'woo-quickpay' ),
					'order_item_type' => 'fee'
				));

				woocommerce_add_order_item_meta( $order_meta_item_id, '_tax_class', '', TRUE );
				woocommerce_add_order_item_meta( $order_meta_item_id, '_line_total', $transaction_fee, TRUE );
				woocommerce_add_order_item_meta( $order_meta_item_id, '_line_tax', 0, TRUE );
				update_post_meta( $this->order->id, '_order_total', woocommerce_format_total( $order_total_updated ) );

				return TRUE;
			}

			return FALSE;
		}
	}

	class WC_Quickpay_API {
		private static $error = NULL;
		private static $ch = NULL;

		public static function validate_response($response, $secret) {
			if(isset($response->ordernumber) AND isset($response->qpstat)) {
				if(self::response_md5($response, $secret) == $response->md5check AND $response->qpstat === '000') {
					return TRUE;
				}
				return FALSE;
			}	
			return FALSE;		
		}

		public static function response_md5($p, $secret) {
			if(is_object($p)) {
				$cardexpire = isset($p->cardexpire) ? $p->cardexpire : '';

				$md5 =  $p->msgtype.$p->ordernumber.$p->amount.$p->currency.$p->time.$p->state.$p->qpstat.$p->qpstatmsg.
					    $p->chstat.$p->chstatmsg.$p->merchant.$p->merchantemail.$p->transaction.$p->cardtype.$p->cardnumber.
					    $p->cardhash.$cardexpire.$p->acquirer.$p->splitpayment.$p->fraudprobability.$p->fraudremarks.$p->fraudreport.
					    $p->fee.$secret;
				
				return md5($md5);				
			}

			return FALSE;			
		}

		public static function request_md5($settings) {
			return md5( implode('' , $settings) );
		}

		public static function request($params) {
			$params_string = '';
			foreach($params as $key=>$value) {
				$params_string .= $key .'='.$value.'&';
			}
			$params_string = rtrim($params_string,'&');	

			curl_setopt(self::curl(), CURLOPT_POSTFIELDS, $params_string.'&md5check=' . self::request_md5($params) );
			$response = simplexml_load_string(curl_exec(self::curl()));

			if($response->qpstat == '000') {
				return $response;
			}

			return new WP_Error('request-error', $response->qpstatmsg);
		}

		private static function curl() {
			if(self::$ch === NULL) {
				self::$ch = curl_init();
				curl_setopt(self::$ch, CURLOPT_URL, 'https://secure.quickpay.dk/api');
				curl_setopt(self::$ch, CURLOPT_POST, TRUE);
				curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER , FALSE);
			}

			return self::$ch;
		}
	}
	
	if( ! function_exists('is_plugin_active')) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if(is_admin() && ! is_plugin_active('woocommerce-subscriptions/woocommerce-subscriptions.php')) {
		new WC_Quickpay();
	}

	// Add the gateway to WooCommerce
	function add_quickpay_gateway( $methods ) {
		$methods[] = 'WC_Quickpay'; return $methods;
	}
	add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), 'WC_Quickpay::action_links'  );
	add_filter('woocommerce_payment_gateways', 'add_quickpay_gateway' );	
}

?>