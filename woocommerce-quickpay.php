<?php
/*
Plugin Name: WooCommerce Quickpay
Plugin URI: http://wordpress.org/plugins/woocommerce-quickpay/
Description: Integrates your Quickpay payment getway into your WooCommerce installation.
Version: 3.0.6
Author: Perfect Solution
Text Domain: woo-quickpay
Author URI: http://perfect-solution.dk
*/

add_action('plugins_loaded', 'init_quickpay_gateway', 0);

function init_quickpay_gateway() {

	if ( ! class_exists( 'WC_Payment_Gateway' )) { return; }

	// Import helper classes
	require_once( 'classes/woocommerce-quickpay-helper.php' );
	require_once( 'classes/woocommerce-quickpay-settings.php' );
	require_once( 'classes/woocommerce-quickpay-order.php' );
	require_once( 'classes/woocommerce-quickpay-request.php' );

	

	// Main class
	class WC_Quickpay extends WC_Payment_Gateway
	{

	    /**
	    * $_instance
	    * @var mixed
	    * @access public
	    * @static
	    */
		public static $_instance = NULL;	
			
	    /**
	    * get_instance
	    * 
	    * Returns a new instance of self, if it does not already exist.
	    * 
	    * @access public
	    * @static
	    * @return object WC_Quickpay
	    */
		public static function get_instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}


		/**
		* __construct function.
		*
		* The class construct
		*
		* @access public
		* @return void
		*/
		public function __construct() 
		{
		    $this->id				= 'quickpay';
		    $this->icon 			= '';
		    $this->has_fields 		= false;	

		    $this->supports = array( 
		    	'subscriptions', 
		    	'products', 
		    	'subscription_cancellation', 
		    	'subscription_reactivation', 
		    	'subscription_suspension' , 
		    	'subscription_amount_changes', 
		    	'subscription_date_changes'
		    );

			// Load the form fields and settings
			$this->init_form_fields();
			$this->init_settings();

			// Get gateway variables
			$this->title = $this->s('title');
			$this->description = $this->s( 'description' );
			$this->instructions = $this->s( 'instructions' );
		}


		/**
		* hooks_and_filters function.
		*
		* Applies plugin hooks and filters
		*
		* @access public
		* @return string
		*/
		public function hooks_and_filters() 
		{
		    add_action( 'init', 'WC_Quickpay_Helper::load_i18n' );
		    add_action( 'scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 3 );
		    add_action( 'woocommerce_api_wc_' . $this->id, array( $this, 'callback_handler' ) );    
		    add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_receipt_' . $this->id, 'WC_Quickpay_Helper::enqueue_javascript_redirect' );
		    add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 2 );
		    add_action( 'cancelled_subscription_' . $this->id, array( $this, 'subscription_cancellation') )	;

			if( is_admin() ) {
			    add_action( 'admin_menu', 'WC_Quickpay_Helper::enqueue_stylesheet' );
			    add_action( 'admin_menu', 'WC_Quickpay_Helper::enqueue_javascript_backend' );
		    	add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		    	add_action( 'woocommerce_order_status_completed', array( $this, 'woocommerce_order_status_completed' ) );  
		    	add_action( 'woocommerce_order_status_refunded', array( $this, 'woocommerce_order_status_refunded' ) );  
		    	add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );	    
				add_action( 'wp_ajax_quickpay_manual_transaction_actions', array( $this, 'quickpay_manual_transaction_actions' ) );	

		    	add_filter( 'manage_shop_order_posts_custom_column', array( $this, 'apply_custom_order_data' ) );
		    	add_filter( 'woocommerce_gateway_icon', array( $this, 'apply_gateway_icons' ), 2, 3 );
			}	
		}


		/**
		* s function.
		*
		* Returns a setting if set. Introduced to prevent undefined key when introducing new settings.
		*
		* @access public
		* @return string
		*/
		public function s( $key ) 
		{
			if( isset( $this->settings[$key] ) ) {
				return $this->settings[$key];
			}

			return '';
		}


		/**
		* add_action_links function.
		*
		* Adds action links inside the plugin overview
		*
		* @access public static
		* @return array
		*/
		public static function add_action_links( $links ) 
		{
			$links = array_merge( array(
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_quickpay' ) . '">' . __( 'Settings', 'woo-quickpay' ) . '</a>',
			), $links );

			array_push( $links, '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=JWDJPFHNVS5BG"><img style="vertical-align:middle;" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" alt="PayPal - The safer, easier way to pay online!" /></a>' );
			
			return $links;
		}


		/**
		* quickpay_manual_transaction_actions function.
		*
		* Ajax method taking manual transaction requests from wp-admin.
		*
		* @access public
		* @return void
		*/
		public function quickpay_manual_transaction_actions() 
		{
			if( isset( $_POST['quickpay_action'] ) AND isset( $_POST['post'] ) ) 
			{
				$param_action 	= $_POST['quickpay_action'];
				$param_post		= $_POST['post'];

				$order = new WC_Quickpay_Order( intval( $param_post ) );
				$api = new WC_Quickpay_Request ( $order, $this->settings );		
				
				$allowed_actions = $api->allowed_actions();		
				
				if( in_array( $param_action, $allowed_actions ) ) 
				{
					if( $api->is_action_allowed( $param_action ) ) 
					{
						$extras = $this->prepare_extras( $param_action, $_POST );

						if( $api->action_router( $order->id , $param_action, $extras ) ) {
							if( $param_action == 'refund' ) {
								$order->update_status( 'refunded' );
							}
						}
					}
				}

			}
		}


		/**
		* prepare_extras function.
		*
		* Prepares extra data used to parse into the action router
		*
		* @access public
		* @param $action - the api action
		* @param $request - the POST request object
		* @return array
		*/
		public function prepare_extras( $action, $request ) {
			$extras = array();

			if( $action == 'splitcapture' ) {
				$extras['amount'] = $request['amount'];
				$extras['finalize'] = $request['finalize'];
			}

			return $extras;
		}


		/**
		* woocommerce_order_status_completed function.
		*
		* Captures one or several transactions when order state changes to complete.
		*
		* @access public
		* @return void
		*/
		public function woocommerce_order_status_completed() 
		{
			global $order, $post, $woocommerce;	
			
			$post_ids = array();

			if( isset($_GET['post'] ) AND is_array( $_GET['post'] ) ) {
				$post_ids = $_GET['post'];
			} 
			elseif( $post->ID === NULL ) 
			{
				if( isset( $_GET['order_id'] ) ) {
					$post_ids = array( $_GET['order_id'] );
				}
			}
			else {
				$post_ids = array( $post->ID );
			}

			if( ! empty( $post_ids ) ) 
			{
				foreach( $post_ids as $post_id ) 
				{
					$order = new WC_Quickpay_Order( $post_id );
					$api = new WC_Quickpay_Request( $order, $this->settings );

					if( WC_Quickpay_Helper::option_is_enabled( $this->s('quickpay_captureoncomplete') ) ) 
					{
						if( $order->get_transaction_id() && $api->is_action_allowed( 'capture' ) ) {
							$api->do_capture();
						}	
					}	
				}		
			}
		}


		/**
		* woocommerce_order_status_refunded function.
		*
		* Refund one or several transactions when order state changes to refunded.
		*
		* @access public
		* @return void
		*/
		public function woocommerce_order_status_refunded() {
			global $order, $post, $woocommerce;	
			
			$post_ids = array();

			if( isset($_GET['post'] ) AND is_array( $_GET['post'] ) ) {
				$post_ids = $_GET['post'];
			} 
			elseif( $post->ID === NULL ) 
			{
				if( isset( $_GET['order_id'] ) ) {
					$post_ids = array( $_GET['order_id'] );
				}
			}
			else {
				$post_ids = array( $post->ID );
			}

			if( ! empty( $post_ids ) ) 
			{
				foreach( $post_ids as $post_id ) 
				{
					$order = new WC_Quickpay_Order( $post_id );
					$api = new WC_Quickpay_Request( $order, $this->settings );

					if( WC_Quickpay_Helper::option_is_enabled( $this->s('quickpay_refundonrefunded') ) ) 
					{
						if( $order->get_transaction_id() && $api->is_action_allowed( 'refund' ) ) {
							$api->do_refund();
						}	
					}	
				}		
			}
		}

		/**
		* payment_fields function.
		*
		* Prints out the description of the gateway. Also adds two checkboxes for viaBill/creditcard for customers to choose how to pay.
		*
		* @access public
		* @return void
		*/
		public function payment_fields() 
		{
			if ( $this->description) echo wpautop( wptexturize( $this->description ) );

			if( WC_Quickpay_Helper::option_is_enabled( $this->s( 'quickpay_ibillOrCreditcard' ) ) ) 
			{
				$labelViaBill = ( $this->s( 'quickpay_labelViaBill' ) !== '' ) ? $this->s( 'quickpay_labelViaBill' ) : 'viaBill';
				$labelCreditCard = ( $this->s( 'quickpay_labelCreditCard' ) !== '' ) ? $this->s( 'quickpay_labelCreditCard' ) : __( 'Credit card', 'woo-quickpay' );
				echo '<ul style="list-style:none;">';
				echo '<li><input style="margin:0;" type="radio" name="quickpay-gwType" value="creditcard" checked/> ' . $labelCreditCard . '</li>';
				echo '<li><input style="margin:0;" type="radio" name="quickpay-gwType" value="viabill" /> '. $labelViaBill .'</li>';
				echo '</ul>';				
			}
		}


		/**
		* receipt_page function.
		*
		* Shows the recipt. This is the very last step before opening the payment window.
		*
		* @access public 
		* @return void
		*/	 
		public function receipt_page( $order ) 
		{	
			$this->generate_quickpay_form( $order );
		}
	
		public function process_payment( $order_id ) 
		{
			global $woocommerce;

			//$woocommerce->cart->empty_cart();

			$order = new WC_Quickpay_Order( $order_id );

			$gwType = NULL;

			if( WC_Quickpay_Helper::option_is_enabled( $this->s( 'quickpay_ibillOrCreditcard' ) ) ) {
				if( isset( $_POST['quickpay-gwType'] ) AND in_array( $_POST['quickpay-gwType'], array( 'viabill', 'creditcard' ) ) ) {
					$gwType = $_POST['quickpay-gwType'];
				}
			}	

			return array(
				'result' 	=> 'success',
				'redirect'	=>  apply_filters( 'woocommerce_quickpay_process_payment', add_query_arg( 'gwType', $gwType, $order->get_checkout_payment_url( TRUE ) ), $order, $this->settings, $_POST )
			);	
		}


		/**
		* scheduled_subscription_payment function.
		*
		* Runs every time a scheduled renewal of a subscription is required
		*
		* @access public 
		* @return void
		*/	
		public function scheduled_subscription_payment( $amount_to_charge, $order, $product_id ) 
		{	
			$order = new WC_Quickpay_Order( $order->id );
			$api = new WC_Quickpay_Request( $order, $this->settings );

			if( $api->do_recurring( $amount_to_charge ) ) {
				WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
			} else {
				WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );
			}
		}


		/**
		* subscription_cancellation function.
		*
		* Cancels a transaction when the subscription is cancelled
		*
		* @access public 
		* @param $order - WC_Order object
		* @return void
		*/	
		public function subscription_cancellation( $order ) 
		{
			$api = new WC_Quickpay_Request( new WC_Quickpay_Order( $order->id ), $this->settings );
			$api->do_cancel();
		}


		/**
		* generate_quickpay_form function.
		*
		* Generates the form with data we are going to submit to Quickpay.
		*
		* @access private 
		* @return void
		*/	
		private function generate_quickpay_form( $order_id ) 
		{
			$order 				= new WC_Quickpay_Order( $order_id );
			$is_subscription	= FALSE;

			if( WC_Quickpay_Helper::subscription_is_active() ) {
				$is_subscription = WC_Subscriptions_Order::order_contains_subscription( $order );
			}

			$ordernumber 		= WC_Quickpay_Helper::prefix_order_number( $order->get_clean_order_number() );
			$continueurl		= $order->get_continue_url();
			$cancelurl			= $order->get_cancellation_url();
			$callbackurl		= $order->get_callback_url();
			$merchant_id 		= $this->s( 'quickpay_merchantid' );
			$test_mode 			= WC_Quickpay_Helper::option_is_enabled( $this->s( 'quickpay_testmode') );
			$autocapture 		= WC_Quickpay_Helper::option_is_enabled( $this->s( 'quickpay_autocapture') );
			$splitcapture 		= $is_subscription ? '' : WC_Quickpay_Helper::option_is_enabled( $this->s( 'quickpay_splitcapture' ) );
			$currency 			= $this->get_gateway_currency();
			$language 			= $this->get_gateway_language();
			$msgtype			= $is_subscription ? 'subscribe' : 'authorize';
			$amount				= WC_Quickpay_Helper::price_multiply( $is_subscription ? WC_Subscriptions_Order::get_total_initial_payment( $order ) : $order->order_total );
			$description		= $is_subscription ? 'qp_subscriber' : '';

			if( WC_Quickpay_Helper::option_is_enabled( $this->s( 'quickpay_ibillOrCreditcard' ) ) AND isset( $_GET['gwType'] )  AND ( strtolower( $_GET['gwType'] ) === 'viabill' ) ) {
				$cardtypelock = strtolower( $_GET['gwType'] );
			} else {
				$cardtypelock = $this->s( 'quickpay_cardtypelock' );
			}

			$md5check = md5(
				WC_Quickpay_Request::$protocol . $msgtype . $merchant_id . $language . $ordernumber	. $amount . $currency . $continueurl . $cancelurl 
				. $callbackurl . $autocapture . $cardtypelock . $description . $test_mode . $splitcapture . $this->s( 'quickpay_md5secret' )
			);

			echo $this->s( 'quickpay_redirectText' );

			echo "
				<form id=\"quickpay_payment_form\" action=\"https://secure.quickpay.dk/form/\" method=\"post\">
					<input type=\"hidden\" name=\"protocol\" value=\"" . WC_Quickpay_Request::$protocol . "\" />
					<input type=\"hidden\" name=\"msgtype\" value=\"{$msgtype}\" />
					<input type=\"hidden\" name=\"merchant\" value=\"{$merchant_id}\" />
					<input type=\"hidden\" name=\"language\" value=\"{$language}\" />
					<input type=\"hidden\" name=\"ordernumber\" value=\"{$ordernumber}\" />
					<input type=\"hidden\" name=\"amount\" value=\"{$amount}\" />
					<input type=\"hidden\" name=\"currency\" value=\"{$currency}\" />
					<input type=\"hidden\" name=\"continueurl\" value=\"{$continueurl}\" />
					<input type=\"hidden\" name=\"cancelurl\" value=\"{$cancelurl}\" />
					<input type=\"hidden\" name=\"callbackurl\" value=\"{$callbackurl}\" />
					<input type=\"hidden\" name=\"autocapture\" value=\"{$autocapture}\" />
					<input type=\"hidden\" name=\"cardtypelock\" value=\"{$cardtypelock}\" />\n";


			
			if( $is_subscription ) {
				echo"<input type=\"hidden\" name=\"description\" value=\"{$description}\" />\n";
			}

			echo "<input type=\"hidden\" name=\"testmode\" value=\"{$test_mode}\" />\n";

			if( ! $is_subscription ) {
				echo "<input type=\"hidden\" name=\"splitpayment\" value=\"{$splitcapture}\" />\n";
			}

			echo "			
					<input type=\"hidden\" name=\"md5check\" value=\"{$md5check}\" />\n
					<input type=\"submit\" value=\"". $this->s('quickpay_paybuttontext') . "\" />
				</form>";
		}


		/**
		* on_order_cancellation function.
		*
		* Is called when a customer cancels the payment process from the Quickpay payment window.
		*
		* @access public 
		* @return void
		*/	
		public function on_order_cancellation( $order_id )
		{
			global $woocommerce;

			$order = new WC_Order( $order_id );

			// Redirect the customer to account page if the current order is failed
			if($order->status == 'failed') 
			{
				$payment_failure_text = printf( __( '<p><strong>Payment failure</strong> A problem with your payment on order <strong>#%i</strong> occured. Please try again to complete your order.</p>', 'woo-quickpay' ), $order->id );
				$woocommerce->add_error( '<p><strong>' . __( 'Payment failure', 'woo-quickpay' ) . '</strong>: '. $payment_failure_text . '</p>' );
				wp_redirect( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );
			}	

			$order->add_order_note( __( 'Quickpay Payment', 'woo-quickpay' ) . ': ' . __( 'Cancelled during process', 'woo-quickpay' ) );
			$woocommerce->add_error( '<p><strong>' . __( 'Payment cancelled', 'woo-quickpay' ) . '</strong>: ' . __( 'Due to cancellation of your payment, the order process was not completed. Please fulfill the payment to complete your order.', 'woo-quickpay' ) .'</p>' );
		}


		/**
		* callback_handler function.
		*
		* Is called after a payment has been submitted in the Quickpay payment window.
		*
		* @access public 
		* @return void
		*/	
		public function callback_handler()
		{
			$response = isset( $_POST['qpstat'] ) ? (object) $_POST : FALSE;

			if( $response ) 
			{
				// Fetch order number;
				preg_match( '/\d{4,}$/', $response->ordernumber, $order_number );
				$order_number = (int) end( $order_number );

				$order_number = $this->find_order_by_order_number( $order_number );

				// Create a new order instance and update it
				$order = new WC_Quickpay_Order( $order_number );
				$order->set_transaction_id( esc_attr( $response->transaction ) );

				// Create a new API request instance
				$api = new WC_Quickpay_Request( $order, $this->settings );

				// Check if the response is valid
				if( $api->validate_api_response( $response ) ) 
				{
					// Add order transaction fee
					$order->add_transaction_fee( $response->fee );

					// Customer subscribed to a product
					if( $response->msgtype == 'subscribe' )
					{
						// If 'capture first payment on subscription' is enabled
						if( WC_Quickpay_Helper::option_is_enabled( $this->s( 'quickpay_autodraw_subscription' ) ) )
						{
							$subscription_initial_payment = WC_Subscriptions_Order::get_total_initial_payment( $order );

							// Only make an instant payment if there is an initial payment
							if( $subscription_initial_payment > 0 AND $response->msgtype === 'subscribe' ) {
								$api->do_recurring();
							}							
						}

						$order->payment_complete();
					}

					// Captured a recurring payment
					else if( $response->msgtype == 'recurring' ) {
						WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );					
					} 

					// Regular payment authorization
					else
					{
						$order->note( sprintf( __( 'Payment authorized. Transaction ID: %s', 'woo-quickpay' ), $response->transaction ) );
						$order->payment_complete();			
					}

				// The response was either invalid or the payment failed for some reason.
				} else
				{
					$order->note( printf( __( 'Payment FAILED. Message from Quickpay: %S', 'woo-quickpay'), $response->qpstatmsg ) );

					if( $response->msgtype == 'subscribe' OR $response->msgtype == 'recurring' ) {
						WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );
					} else {
						$order->update_status( 'failed' );
					}
				}
			}
		}


		public function find_order_by_order_number( $order_number )
		{	
			$order_id = null;	
			$post_status = array_keys( wc_get_order_statuses() );

			// search for the order by custom order number
			$query_args = array(
				'numberposts' => 1,
				'meta_key'    => '_order_number',
				'meta_value'  => $order_number,
				'post_type'   => 'shop_order',
				'post_status' => $post_status,
				'fields'      => 'ids'
			);
			
			$post = get_posts( $query_args );
			if( ! empty( $post ) ) {
				list( $order_id ) = $post;
			} else {
				// search for the order by custom order number formatted. Used in Sequential Order Numbers Pro
				$query_args = array(
					'numberposts' => 1,
					'meta_key'    => '_order_number_formatted',
					'meta_value'  => $order_number,
					'post_type'   => 'shop_order',
					'post_status' => $post_status,
					'fields'      => 'ids'
				);		

				$post = get_posts( $query_args );
				if( ! empty( $post ) ) {
					list( $order_id ) = $post;
				}	
			}
			
			// order was found
			if ( $order_id !== null ) return $order_id;
			
			// if we didn't find the order, then it may be that this plugin was disabled and an order was placed in the interim
			$order = new WC_Order( $order_number );
			if ( isset( $order->order_custom_fields['_order_number'][0] ) || isset( $order->order_custom_fields['_order_number_formatted'][0] ) ) {
				// _order_number OR _order_number_formatted was set, so this is not an old order, it's a new one that just happened to have post_id that matched the searched-for order_number
				return 0;
			}
			
			return $order->id;
		}


		/**
		* init_form_fields function.
		*
		* Initiates the plugin settings form fields
		*
		* @access public
		* @return array
		*/
		public function init_form_fields()
		{
			$this->form_fields = WC_Quickpay_Settings::get_fields();
		}


		/**
		* admin_options function.
		*
		* Prints the admin settings form
		*
		* @access public
		* @return string
		*/
		public function admin_options()
		{
			echo "<h3>" . __('Quickpay', 'woo-quickpay') . "</h3>";
			echo "<p>" . __('Allows you to receive payments via Quickpay.', 'woo-quickpay') . "</p>";
			echo "<table class=\"form-table\">";
						$this->generate_settings_html();
			echo "</table";
		}


		/**
		* add_meta_boxes function.
		*
		* Adds the action meta box inside the single order view.
		*
		* @access public
		* @return void
		*/
		public function add_meta_boxes()
		{
			 add_meta_box( 'quickpay-payment-actions', __( 'Quickpay Payment', 'woo-quickpay' ), array( &$this, 'meta_box_payment' ), 'shop_order', 'side', 'high' );
		}


		/**
		* meta_box_payment function.
		*
		* Inserts the content of the API actions meta box
		*
		* @access public
		* @return void
		*/
		public function meta_box_payment()
		{
			global $post, $woocommerce;	
			$order = new WC_Quickpay_Order( $post->ID );	
			$api = new WC_Quickpay_Request( $order, $this->settings );
			
			$transaction_id	= $order->get_transaction_id();

			if( $transaction_id )
			{
				$state 			= $api->get_payment_status( 'state' );
				$balance 		= $api->get_payment_status( 'balance' );
				$msgtype 		= $api->get_payment_status( 'msgtype' );

				echo "<p class=\"woocommerce-quickpay-{$msgtype}\"><strong>" . __( 'Current payment state', 'woo-quickpay' ) . ": " . WC_Quickpay_Helper::format_msgtype( $msgtype ) . "</strong></p>";

				if( $api->is_action_allowed( 'regular_header', $state ) )
				{
					echo "<h4><strong>" . __( 'Standard actions', 'woo-quickpay' ) . "</strong></h4>";
					echo "<ul class=\"order_action\">";

					if( $api->is_action_allowed( 'capture', $state ) ) {
						echo "<li class=\"left\"><a class=\"button\" data-action=\"capture\" data-confirm=\"". __( 'You are about to CAPTURE this payment', 'woo-quickpay' ) . "\">" . __( 'Capture', 'woo-quickpay' ) . "</a></li>";
					}

					if( $api->is_action_allowed( 'cancel', $state ) ) {
						echo "<li class=\"right\"><a class=\"button\" data-action=\"cancel\" data-confirm=\"". __( 'You are about to CANCEL this payment', 'woo-quickpay' ) . "\">" . __( 'Cancel', 'woo-quickpay' ) . "</a></li>";					
					}

					if( $api->is_action_allowed( 'refund', $state ) ) {
						echo "<li class=\"left\"><a class=\"button\" data-action=\"refund\" data-confirm=\"". __( 'You are about to REFUND this payment', 'woo-quickpay' ) . "\">" . __( 'Refund', 'woo-quickpay' ) . "</a></li>";					
					}
					echo	"<li>&nbsp;</li>";
					echo "</ul>";
				}				

				echo "<br />";

				if( WC_Quickpay_Helper::option_is_enabled( $this->s( 'quickpay_splitcapture' ) ) )
				{
					$currency = $this->get_gateway_currency();

					if( $api->is_action_allowed( 'splitcapture', $state ) AND $balance < WC_Quickpay_Helper::price_multiply( $order->order_total ) )
					{
						echo "<div class=\"quickpay-split-container\">";
							echo "<h4><strong>" . __( 'Split payment', 'woo-quickpay' ) . "</strong></h4>";
							echo "<div class=\"totals_groups\">";
								echo "<h4><span class=\"inline_total\">{$currency}</span>" . __( 'Currency', 'woo-quickpay' ) . "</h4>";
								echo "<h4><span class=\"quickpay-balance inline_total\">" . WC_Quickpay_Helper::price_normalize( $balance ) ."</span>" .  __( 'Balance', 'woo-quickpay' ) . "</h4>";
								echo "<h4><span class=\"quickpay-remaining inline_total\">" . WC_Quickpay_Helper::price_normalize( WC_Quickpay_Helper::price_multiply( $order->order_total )  - $balance ) ."</span>" .  __( 'Remaining', 'woo-quickpay' ) . "</h4>";
								echo "<h4><span class=\"quickpay-remaining inline_total\"><input type=\"text\" style=\"width:50px;text-align:right;\" id=\"quickpay_split_amount\" name=\"quickpay_split_amount\" /></span><strong>" .  __( 'Amount to capture', 'woo-quickpay' ) . "</strong></h4>";
							echo "</div>";

							echo "<ul>
									<li>
										<p>
											<span><a id=\"quickpay_split_button\" data-action=\"split_capture\" style=\"display:none;\" class=\"button\" data-notify=\"", __( 'You are about to SPLIT CAPTURE this payment. This means that you will capture the amount stated in the input field. The payment state will remain open.', 'woo-quickpay' ), "\" href=\"" . admin_url( 'post.php?post={$post->ID}&action=edit&quickpay_action=splitcapture' ) . "\">" . __( 'Split Capture', 'woo-quickpay' ) . "</a></span>
											<span><a id=\"quickpay_split_finalize_button\" data-action=\"split_finalize\" style=\"display:none;\" class=\"button\" data-notify=\"", __( 'You are about to SPLIT CAPTURE and FINALIZE this payment. This means that you will capture the amount stated in the input field and that you can no longer capture money from this transaction.', 'woo-quickpay' ), "\" href=\"" . admin_url( 'post.php?post={$post->ID}&action=edit&quickpay_action=splitcapture&quickpay_finalize=yes' ) . "\">" . __( 'Split and finalize', 'woo-quickpay' ) . "</a></span>
										</p>
									</li>
								  </ul>
								";
						echo "</div>";
					}
				}
			}
		}


		/**
		* email_instructions function.
		*
		* Adds custom text to the order confirmation email.
		*
		* @access public
		* @return boolean/string/void
		*/		
		public function email_instructions( $order, $sent_to_admin )
		{
			if ( $sent_to_admin || $order->status !== 'processing' && $order->status !== 'completed' || $order->payment_method !== 'quickpay' ) {
				return;
			}
				
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}		
		}
	

		/**
		* apply_custom_order_data function.
		*
		* Applies transaction ID and state to the order data overview
		*
		* @access public
		* @return void
		*/	
		public function apply_custom_order_data( $column )
		{
			global $post, $woocommerce;

			$order = new WC_Quickpay_Order( $post->ID );
			$api = new WC_Quickpay_Request( $order, $this->settings );
			
			// ? ABOVE 2.1 : BELOW 2.1
			$check_column = version_compare( $woocommerce->version, '2.1', '>' ) ? 'shipping_address' : 'billing_address';

			// Show transaction ID on the overview
			if( $column == $check_column )
			{	
				// Insert transaction id and payment status if any
				$transaction_id = $order->get_transaction_id();

				if( $transaction_id )
				{
					// Get the transaction status
					$status = $api->get_payment_status( 'msgtype' );

					echo "<small class=\"meta\">Transaction id: {$transaction_id}</small>";		
					echo "<small class=\"meta woocommerce-quickpay-{$status}\">Payment state: " . WC_Quickpay_Helper::format_msgtype( $status ) . "</small>";
				}
			}		
		}

		/**
		 * 
		* FILTER: apply_gateway_icons function.
		*
		* Sets gateway icons on frontend
		*
		* @access public
		* @return void
		*/	
		public function apply_gateway_icons( $icon, $id ) {

			if($id == $this->id) {
				$icon = '';
				$icons = $this->s('quickpay_icons');

				if( ! empty( $icons ) ) {
					$settings_icons_maxheight = $this->s( 'quickpay_icons_maxheight' );
					$icons_maxheight = ! empty( $settings_icons_maxheight ) ? $settings_icons_maxheight . 'px' : '20px';

					foreach( $icons as $key => $item ) {
						$icon_url = WC_HTTPS::force_https_url( plugin_dir_url( __FILE__ ) . 'assets/images/cards/' . $item . '.png' );
						$icon .= '<img src="' . $icon_url . '" alt="' . esc_attr( $this->get_title() ) . '" style="max-height:' . $icons_maxheight . '"/>';
					}
				}
			}

			return $icon;
		}


		/**
		* 
		* get_gateway_currency
		*
		* Returns the gateway currency
		*
		* @access public
		* @return void
		*/	
		public function get_gateway_currency() {
			$currency = apply_filters( 'woocommerce_quickpay_currency', $this->s( 'quickpay_currency' ) );
			return $currency;
		}


		/**
		* 
		* get_gateway_language
		*
		* Returns the gateway language
		*
		* @access public
		* @return void
		*/	
		public function get_gateway_language() {
			$language = apply_filters( 'woocommerce_quickpay_language', $this->s( 'quickpay_language' ) );
			return $language;
		}
	}

	// Make the object available for later use
	function WC_QP() {
		return WC_Quickpay::get_instance();
	}
	
	// Instantiate
	WC_QP();
	WC_QP()->hooks_and_filters();

	// Add the gateway to WooCommerce
	function add_quickpay_gateway( $methods )
	{
		$methods[] = 'WC_Quickpay'; return $methods;
	}
	add_filter('woocommerce_payment_gateways', 'add_quickpay_gateway' );	
	
	add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), 'WC_Quickpay::add_action_links'  );
}

?>