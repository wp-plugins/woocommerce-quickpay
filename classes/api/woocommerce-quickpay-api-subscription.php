<?php
/**
 * WC_Quickpay_API_Subscription class
 *
 * @class 		WC_Quickpay_API_Subscription
 * @since		4.0.0
 * @package		Woocommerce_Quickpay/Classes
 * @category	Class
 * @author 		PerfectSolution
 * @docs        http://tech.quickpay.net/api/services/?scope=merchant
 */

class WC_Quickpay_API_Subscription extends WC_Quickpay_API_Transaction
{
  	/**
	* __construct function.
	*
	* @access public
	* @return void
	*/      
    public function __construct( $resource_data = NULL ) 
    {
    	// Run the parent construct
    	parent::__construct();

    	// Set the resource data to an object passed in on object instantiation.
    	// Usually done when we want to perform actions on an object returned from 
    	// the API sent to the plugin callback handler.
  		if( is_object( $resource_data ) ) 
  		{
  			$this->resource_data = $resource_data;
  		}

    	// Append the main API url
        $this->api_url = $this->api_url . 'subscriptions/';
    }


   	/**
	* capture function.
	* 
	* Sends a 'recurring' request to the Quickpay API
	*
	* @access public
	* @param  int $transaction_id
	* @param  int $amount
	* @return void
	* @throws Quickpay_API_Exception
	*/   
    public function recurring( $subscription_id, $order, $amount = NULL ) 
    {
        // Check if a custom amount ha been set
        if( $amount === NULL ) 
        {
            // No custom amount set. Default to the order total
            $amount = WC_Subscriptions_Order::get_recurring_total( $order );
        }
        
        if( ! $order instanceof WC_Quickpay_Order ) {
            $order = new WC_Quickpay_Order( $order->id );
        }
        
        $order_number = WC_Quickpay_Helper::prefix_order_number( $order->get_clean_order_number() );
                
    	$request = $this->post( sprintf( '%d/%s', $subscription_id, "recurring" ), array( 
            'amount' => WC_Quickpay_Helper::price_multiply( $amount ),
            'order_id' => $order_number,
            'currency' => WC_QP()->get_gateway_currency(),
            'auto_capture' => TRUE,
            'auto_fee' => WC_Quickpay_Helper::option_is_enabled( WC_QP()->s( 'quickpay_autofee' ) )
        ) );
    }
    

  	/**
	* cancel function.
	* 
	* Sends a 'cancel' request to the Quickpay API
	*
	* @access public
	* @param  int $subscription_id
	* @return void
	* @throws Quickpay_API_Exception
	*/   
    public function cancel( $subscription_id ) 
    {
    	$request = $this->post( sprintf( '%d/%s', $subscription_id, "cancel" ) );
    }
    

    /**
    * is_action_allowed function.
    *
    * Check if the action we are about to perform is allowed according to the current transaction state.
    *
    * @access public
    * @return boolean
    */
    public function is_action_allowed( $action ) 
    {
        $state = $this->get_current_type();

        $allowed_states = array(
            'cancel' => array( 'authorize' ),
            'standard_actions' => array( 'authorize' )
        );

        return array_key_exists( $action, $allowed_states ) AND in_array( $state, $allowed_states[$action] );
    }    
}