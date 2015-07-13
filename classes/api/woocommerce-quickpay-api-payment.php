<?php
/**
 * WC_QuickPay_API_Payment class
 *
 * @class 		WC_QuickPay_API_Payment
 * @since		4.0.0
 * @package		Woocommerce_QuickPay/Classes
 * @category	Class
 * @author 		PerfectSolution
 * @docs        http://tech.quickpay.net/api/services/?scope=merchant
 */

class WC_QuickPay_API_Payment extends WC_QuickPay_API_Transaction
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
        $this->api_url = $this->api_url . 'payments/';
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
        return parent::create( $order );
    }           


   	/**
	* capture function.
	* 
	* Sends a 'capture' request to the QuickPay API
	*
	* @access public
	* @param  int $transaction_id
	* @param  int $amount
	* @return void
	* @throws QuickPay_API_Exception
	*/   
    public function capture( $transaction_id, $order, $amount = NULL ) 
    {
        // Check if a custom amount ha been set
        if( $amount === NULL ) 
        {
            // No custom amount set. Default to the order total
            $amount = $order->get_total();   
        }
        
    	$request = $this->post( sprintf( '%d/%s', $transaction_id, "capture" ), array( 'amount' => WC_QuickPay_Helper::price_multiply( $amount ) ) );
    }


  	/**
	* cancel function.
	* 
	* Sends a 'cancel' request to the QuickPay API
	*
	* @access public
	* @param  int $transaction_id
	* @return void
	* @throws QuickPay_API_Exception
	*/   
    public function cancel( $transaction_id ) 
    {
    	$request = $this->post( sprintf( '%d/%s', $transaction_id, "cancel" ) );
    }

    
   	/**
	* refund function.
	* 
	* Sends a 'refund' request to the QuickPay API
	*
	* @access public
	* @param  int $transaction_id
	* @param  int $amount
	* @return void
	* @throws QuickPay_API_Exception
	*/   
    public function refund( $transaction_id, $order, $amount = NULL ) 
    {
        // Check if a custom amount ha been set
        if( $amount === NULL ) 
        {
            // No custom amount set. Default to the order total
            $amount = $order->get_total();   
        }
    
        $request = $this->post( sprintf( '%d/%s', $transaction_id, "refund" ), array( 'amount' =>  WC_QuickPay_Helper::price_multiply( $amount ) ) );
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
			'capture' => array( 'authorize' ),
			'cancel' => array( 'authorize' ),
			'refund' => array( 'capture', 'refund' ),
			'renew' => array( 'authorize' ),
			'splitcapture' => array( 'authorize', 'capture' ),
			'recurring' => array( 'subscribe' ),
            'standard_actions' => array( 'authorize' )
		);

		if( $action == 'splitcapture' ) 
		{
			$allowed_states['capture'] = array( 'authorize', 'capture' );
		}

		return in_array( $state, $allowed_states[$action] );
	}
}