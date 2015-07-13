<?php
/**
 * WC_QuickPay_API_Transaction class
 * 
 * Used for common methods shared between payments and subscriptions
 *
 * @class 		WC_QuickPay_API_Payment
 * @since		4.0.0
 * @package		Woocommerce_QuickPay/Classes
 * @category	Class
 * @author 		PerfectSolution
 * @docs        http://tech.quickpay.net/api/services/?scope=merchant
 */

class WC_QuickPay_API_Transaction extends WC_QuickPay_API
{
    /**
	* get_current_type function.
	* 
	* Returns the current payment type
	*
	* @access public
	* @return void
	*/ 
    public function get_current_type() 
    {
    	$last_operation = $this->get_last_operation();
        
        if( ! is_object( $last_operation ) ) 
        {
            throw new QuickPay_API_Exception( "Malformed operation response", 0 ); 
        }
        
        return $last_operation->type;
    }


  	/**
	* get_last_operation function.
	* 
	* Returns the last successful transaction operation
	*
	* @access public
	* @return void
	* @throws QuickPay_API_Exception
	*/ 
	public function get_last_operation() 
	{
		if( ! is_object( $this->resource_data ) ) 
		{
			throw new QuickPay_API_Exception( 'No API payment resource data available.', 0 );
		}

		// Loop through all the operations and return only the operations that were successful (based on the qp_status_code and pending mode).
		$successful_operations = array_filter($this->resource_data->operations, function( $operation ) {
			return $operation->qp_status_code == 20000 || $operation->pending == TRUE;
		} );
        
        $last_operation = end( $successful_operations );
        
        if( $last_operation->pending == TRUE ) {
            $last_operation->type = __( 'Pending - check your QuickPay manager', 'woo-quickpay' );   
        }
        
		return $last_operation;
	}

    
    /**
	* is_test function.
	* 
	* Tests if a payment was made in test mode.
	*
	* @access public
	* @return boolean
	* @throws QuickPay_API_Exception
	*/     
    public function is_test() 
    {
		if( ! is_object( $this->resource_data ) ) {
			throw new QuickPay_API_Exception( 'No API payment resource data available.', 0 );
		}

    	return $this->resource_data->test_mode;
    }
    
   	/**
	* create function.
	* 
	* Creates a new payment via the API
	*
	* @access public
	* @param  WC_QuickPay_Order $order
	* @return object
	* @throws QuickPay_API_Exception
	*/   
    public function create( WC_QuickPay_Order $order ) 
    {     
        $base_params = array(
            'currency' => WC_QP()->get_gateway_currency(),
        );
        
        $order_params = $order->get_transaction_params();
        
        $params = array_merge( $base_params, $order_params );
        
    	$payment = $this->post( '/', $params);
        
        return $payment;
    }  
    
    
    /**
	* create_link function.
	* 
	* Creates a new payment link via the API
	*
	* @access public
	* @param  int $transaction_id
	* @param  WC_QuickPay_Order $order
	* @return object
	* @throws QuickPay_API_Exception
	*/   
    public function create_link( $transaction_id, WC_QuickPay_Order $order ) 
    {         
        $cardtypelock = WC_QP()->s( 'quickpay_cardtypelock' );

        $payment_method = strtolower($order->payment_method);

        $base_params = array(
            'language'                      => WC_QP()->get_gateway_language(),
            'currency'                      => WC_QP()->get_gateway_currency(),
            'callbackurl'                   => WC_QuickPay_Helper::get_callback_url(),
            'autocapture'                   => WC_QuickPay_Helper::option_is_enabled( WC_QP()->s( 'quickpay_autocapture') ),
            'autofee'                       => WC_QuickPay_Helper::option_is_enabled( WC_QP()->s( 'quickpay_autofee' ) ),
            'payment_methods'               => apply_filters('woocommerce_quickpay_cardtypelock_' . $payment_method, $cardtypelock, $payment_method),
            'branding_id'                   => WC_QP()->s( 'quickpay_branding_id' ),
            'google_analytics_tracking_id'  => WC_QP()->s( 'quickpay_google_analytics_tracking_id' ),
            'google_analytics_client_id'    => WC_QP()->s('quickpay_google_analytics_client_id')
        );
        
        $order_params = $order->get_transaction_link_params();
        
        $params = array_merge( $base_params, $order_params );

    	$payment_link = $this->put( sprintf( '%d/link', $transaction_id ), $params);

        return $payment_link;
    } 
}