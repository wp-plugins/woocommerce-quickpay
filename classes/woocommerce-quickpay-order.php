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
		if( method_exists( $this, 'get_cancel_order_url' ) ) {
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
	public static function get_callback_url() {
        trigger_error('WC_Quickpay_Order::get_callback_url() is deprecated since 4.2.0. Use WC_Quickpay_Helper::get_callback_url() instead.');
		return WC_Quickpay_Helper::get_callback_url();
	}


	/**
	* get_transaction_id function
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
	* set_transaction_id function
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
	* get_payment_id function
	*
	* If the order has a payment ID, we will return it. If no ID is set we return FALSE.
	*
	* @access public
	* @return string
	*/	
	public function get_payment_id() {
		return get_post_meta( $this->id , 'QUICKPAY_PAYMENT_ID', TRUE );
	}
    
    
	/**
	* set_payment_id function
	*
	* Set the payment ID on an order
	*
	* @access public
	* @return void
	*/	
	public function set_payment_id( $payment_link ) {
		update_post_meta( $this->id , 'QUICKPAY_PAYMENT_ID', $payment_link );
	}


	/**
	* delete_payment_id function
	*
	* Delete the payment ID on an order
	*
	* @access public
	* @return void
	*/	
	public function delete_payment_id() {
		delete_post_meta( $this->id , 'QUICKPAY_PAYMENT_ID' );
	}


	/**
	* get_payment_link function
	*
	* If the order has a payment link, we will return it. If no link is set we return FALSE.
	*
	* @access public
	* @return string
	*/	
	public function get_payment_link() {
		return get_post_meta( $this->id , 'QUICKPAY_PAYMENT_LINK', TRUE );
	}
    
    
	/**
	* set_payment_link function
	*
	* Set the payment link on an order
	*
	* @access public
	* @return void
	*/	
	public function set_payment_link( $payment_link ) {
		update_post_meta( $this->id , 'QUICKPAY_PAYMENT_LINK', $payment_link );
	}
    
    
	/**
	* delete_payment_link function
	*
	* Delete the payment link on an order
	*
	* @access public
	* @return void
	*/	
	public function delete_payment_link() {
		delete_post_meta( $this->id , 'QUICKPAY_PAYMENT_LINK' );
	}
    
    
	/**   
	* get_transaction_order_id function
	*
	* If the order has a transaction order reference, we will return it. If no transaction order reference is set we return FALSE.
	*
	* @access public
	* @return string
	*/	
	public function get_transaction_order_id() {
		return get_post_meta( $this->id , 'TRANSACTION_ORDER_ID', TRUE );
	}
    
    
	/**
	* set_transaction_order_id function
	*
	* Set the transaction order ID on an order
	*
	* @access public
	* @return void
	*/	
	public function set_transaction_order_id( $transaction_order_id ) {
		update_post_meta( $this->id , 'TRANSACTION_ORDER_ID', $transaction_order_id );
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
			$has_subscription = WC_Subscriptions_Order::order_contains_subscription( $this );
		}	

		return $has_subscription;	
	}


	/**
	* add_transaction_fee function.
	*
	* Adds order transaction fee to the order before sending out the order confirmation
	*
	* @access public
	* @param int $total_amount_with_fee
	* @return boolean
	*/	
    
    public function add_transaction_fee( $total_amount_with_fee ) {
		$order_total = $this->get_total() ;
		$order_total_formatted = WC_Quickpay_Helper::price_multiply( $order_total );
        
        $fee = $total_amount_with_fee - $order_total_formatted;

        if( $fee > 0) {
			$order_total_updated = $order_total_formatted + $fee;
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
	* subscription_is_renewal_failure function.
	*
	* Checks if the order is currently in a failed renewal
	*
	* @access public
	* @return boolean
	*/	   
    public function subscription_is_renewal_failure()
    {
        $renewal_failure = FALSE; 
        
        if( WC_Quickpay_Helper::subscription_is_active() )
        {
            $renewal_failure = (WC_Subscriptions_Renewal_Order::is_renewal( $this ) AND $this->status == 'failed');
        }
        
        return $renewal_failure;
    }
    
    
	/**
	* note function.
	*
	* Adds a custom order note
	*
	* @access public
	* @return void
	*/	
	public function note( $message ) 
    {
		if( isset( $message ) ) {
			$this->add_order_note( 'Quickpay: ' . $message );
		}
	}

    
	/**
	* get_transaction_params function.
	*
	* Returns the necessary basic params to send to QuickPay when creating a payment
	*
	* @access public
	* @return void
	*/	
	public function get_transaction_params() 
    {
        $is_subscription = $this->contains_subscription();
        
        $params_subscription = array();
        
        if( $is_subscription )
        {
            $params_subscription = array(
                'description' => 'woocommerce-subscription'  
            );
        }
        
        $params = array_merge(array(
            'order_id'      => WC_Quickpay_Helper::prefix_order_number( $this->get_clean_order_number() ),
        ), $this->get_custom_variables());
        
        return array_merge( $params, $params_subscription );
	}
    
    
	/**
	* get_transaction_link_params function.
	*
	* Returns the necessary basic params to send to QuickPay when creating a payment link
	*
	* @access public
	* @return void
	*/	
	public function get_transaction_link_params() 
    {
        $is_subscription = $this->contains_subscription();
        $amount = $this->get_total();
           
        if( $is_subscription )
        {
            $amount = WC_Subscriptions_Order::get_total_initial_payment( $this );
        }
        
        return array(
            'order_id'      => WC_Quickpay_Helper::prefix_order_number( $this->get_clean_order_number() ),
            'continueurl'   => $this->get_continue_url(),
            'cancelurl'     => $this->get_cancellation_url(),
            'amount'        => WC_Quickpay_Helper::price_multiply( $amount ),
        );
	}
    
    
	/**
	* get_custom_variables function.
	*
	* Returns custom variables chosen in the gateway settings. This information will 
	* be sent to QuickPay and stored with the transaction.
	*
	* @access public
	* @return void
	*/	
	public function get_custom_variables() 
    {
        $custom_vars_settings = WC_QP()->s('quickpay_custom_variables');
        $custom_vars = array();

        // Complete Billing Address Details
        if( in_array('billing_all_data', $custom_vars_settings) ) 
        {
            $custom_vars['Billing Details'] = array(
                __('Name', 'woo-quickpay' ) => $this->billing_first_name . ' ' . $this->billing_last_name,
                __('Company', 'woo-quickpay' ) => $this->billing_company,
                __('Address 1', 'woo-quickpay' ) => $this->billing_address_1,
                __('Address 2', 'woo-quickpay' ) => $this->billing_address_2,
                __('City', 'woo-quickpay' ) => $this->billing_city,
                __('State', 'woo-quickpay' ) => $this->billing_state,
                __('Postcode', 'woo-quickpay' ) => $this->billing_postcode,
                __('Country', 'woo-quickpay' ) => $this->billing_country,
                __('Phone', 'woo-quickpay' ) => $this->billing_phone,
                __('Email', 'woo-quickpay' ) => $this->billing_email,
            );
        }
        
        // Complete Shipping Address Details
        if( in_array('shipping_all_data', $custom_vars_settings) ) 
        {
            $custom_vars['Shipping Details'] = array(
                __('Name', 'woo-quickpay' ) => $this->shipping_first_name . ' ' . $this->shipping_last_name,
                __('Company', 'woo-quickpay' ) => $this->shipping_company,
                __('Address 1', 'woo-quickpay' ) => $this->shipping_address_1,
                __('Address 2', 'woo-quickpay' ) => $this->shipping_address_2,
                __('City', 'woo-quickpay' ) => $this->shipping_city,
                __('State', 'woo-quickpay' ) => $this->shipping_state,
                __('Postcode', 'woo-quickpay' ) => $this->shipping_postcode,
                __('Country', 'woo-quickpay' ) => $this->shipping_country,
            );
        }
        
        // Single: Order Email
        if( in_array('customer_email', $custom_vars_settings) ) 
        {
            $custom_vars[__('Customer Email', 'woo-quickpay' )] = $this->billing_email;
        }
        
        // Single: Order Phone
        if( in_array('customer_phone', $custom_vars_settings) ) 
        {
            $custom_vars[__('Customer Phone', 'woo-quickpay' )] = $this->billing_phone;
        }
        
        // Single: Browser User Agent
        if( in_array('browser_useragent', $custom_vars_settings) ) 
        {
            $custom_vars[__('User Agent', 'woo-quickpay' )] = $this->customer_user_agent;
        }
        
        // Single: Shipping Method
        if( in_array('shipping_method', $custom_vars_settings) ) 
        {
            $custom_vars[__('Shipping Method', 'woo-quickpay' )] = $this->get_shipping_method();   
        }
        
        ksort($custom_vars);
        
        return array( 'variables' => $custom_vars );
	}    
}

?>