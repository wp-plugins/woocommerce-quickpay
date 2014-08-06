<?php
/**
 * WC_Quickpay_Order class
 *
 * @class 		WC_Quickpay_Order
 * @version		1.0.0
 * @package		Woocommerce_Quickpay/Classes
 * @category	Class
 * @author 		PerfectSolution
 */

class WC_Quickpay_Order extends WC_Order {

	/**
	* get_continue_url function
	*
	* Returns the order's continue callback url
	*
	* @access public
	* @return string
	*/	
	public function get_continue_url() {
		if( method_exists( $this, 'get_checkout_order_received_url' ) ) {
			return $this->get_checkout_order_received_url();
		}

		return add_query_arg( 'key', $this->order_key, add_query_arg(
				'order', $this->id, 
				get_permalink( get_option( 'woocommerce_thanks_page_id' ) )
			)
		);
	}


	/**
	* get_cancellation_url function
	*
	* Returns the order's cancellation callback url
	*
	* @access public
	* @return string
	*/	
	public function get_cancellation_url() {
		if( method_exists( $this->order, 'get_cancel_order_url' ) ) {
			return str_replace( '&amp;', '&', $this->get_cancel_order_url() );
		}

		return add_query_arg('key', $this->order_key, add_query_arg( 
			array( 
				'order' => $this->id, 
				'payment_cancellation' => 'yes'
			),
			get_permalink( get_option('woocommerce_cart_page_id') ) )
		);
	}


	/**
	* get_callback_url function
	*
	* Returns the order's main callback url
	*
	* @access public
	* @return string
	*/	
	public function get_callback_url() {
		return str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Quickpay', home_url( '/' ) ) );
	}


	/**
	* get_cancellation_url function
	*
	* If the order has a transaction ID, we will return it. If no transaction ID is set we return FALSE.
	*
	* @access public
	* @return string
	*/	
	public function get_transaction_id() {
		return get_post_meta( $this->id , 'TRANSACTION_ID', TRUE );
	}


	/**
	* set_cancellation_url function
	*
	* Set the transaction ID on an order
	*
	* @access public
	* @return void
	*/	
	public function set_transaction_id( $transaction_id ) {
		update_post_meta( $this->id , 'TRANSACTION_ID', $transaction_id );
	}


	/**
	* get_clean_order_number function
	*
	* Returns the order number without leading #
	*
	* @access public
	* @return integer
	*/		
	public function get_clean_order_number() {
		return str_replace( '#', '', $this->get_order_number() );
	}


	/**
	* contains_subscription function
	*
	* Checks if an order contains a subscription product
	*
	* @access public
	* @return boolean
	*/
	public function contains_subscription() {
		$has_subscription = FALSE;

		if( WC_Quickpay_Helper::subscription_is_active() ) {
			$has_subscription = WC_Subscriptions_Order::order_contains_subscription( $this->order );
		}	

		return $has_subscription;	
	}


	/**
	* add_transaction_fee function.
	*
	* Adds order transaction fee to the order before sending out the order confirmation
	*
	* @access public
	* @return boolean
	*/	

	public function add_transaction_fee( $fee ) {
		$order_total = $this->get_total() ;
		$order_total_formated = WC_Quickpay_Helper::price_multiply( $order_total );

		if( $fee > 0) {
			$order_total_updated = $order_total_formated + $fee;
			$order_total_updated = WC_Quickpay_Helper::price_normalize( $order_total_updated );

			$transaction_fee = WC_Quickpay_Helper::price_normalize( $fee );

			$order_meta_item_id = woocommerce_add_order_item( $this->id,  array(
				'order_item_name' => __( 'Payment Fee', 'woo-quickpay' ),
				'order_item_type' => 'fee'
			));

			woocommerce_add_order_item_meta( $order_meta_item_id, '_tax_class', '', TRUE );
			woocommerce_add_order_item_meta( $order_meta_item_id, '_line_total', $transaction_fee, TRUE );
			woocommerce_add_order_item_meta( $order_meta_item_id, '_line_tax', 0, TRUE );
			update_post_meta( $this->id, '_order_total', wc_format_decimal( $order_total_updated ) );

			return TRUE;
		}

		return FALSE;
	}
	/**
	* note function.
	*
	* Adds a custom order note
	*
	* @access public
	* @return void
	*/	
	public function note( $message ) {
		if( isset( $message ) ) {
			$this->add_order_note( 'Quickpay: ' . $message );
		}
	}
}

?>