<?php

class QuickPay_Exception extends Exception
{
	/**
	 * Contains a log object instance
	 * @access protected
	 */
	protected $log;

  	/**
	 * Contains the curl object instance
	 * @access protected
	 */  
    protected $curl_request_data;
    
    
  	/**
	* __Construct function.
	* 
	* Redefine the exception so message isn't optional
	*
	* @access public
	* @return void
	*/ 
    public function __construct($message, $code = 0, Exception $previous = null, $curl_request_data = '') {
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);

        $this->log = new WC_QuickPay_Log();
        
        $this->curl_request_data = $curl_request_data;
    }


  	/**
	* write_to_logs function.
	* 
	* Stores the exception dump in the WooCommerce system logs
	*
	* @access public
	* @return void
	*/  
	public function write_to_logs() 
	{
		$this->log->separator();
		$this->log->add( 'QuickPay Exception file: ' . $this->getFile() );
		$this->log->add( 'QuickPay Exception line: ' . $this->getLine() );
		$this->log->add( 'QuickPay Exception code: ' . $this->getCode() );
		$this->log->add( 'QuickPay Exception message: ' . $this->getMessage() );
		$this->log->separator();
	}


  	/**
	* write_standard_warning function.
	* 
	* Prints out a standard warning
	*
	* @access public
	* @return void
	*/ 
	public function write_standard_warning()
	{	
		printf( 
			wp_kses( 
				__( "An error occured. For more information check out the <strong>%s</strong> logs inside <strong>WooCommerce -> System Status -> Logs</strong>.", 'woo-quickpay' ), array( 'strong' => array() ) 
			), 
			$this->log->get_domain() 
		);
	}
}


class QuickPay_API_Exception extends QuickPay_Exception 
{
  	
  	/**
	* write_to_logs function.
	* 
	* Stores the exception dump in the WooCommerce system logs
	*
	* @access public
	* @return void
	*/  
	public function write_to_logs() 
	{
		$this->log->separator();
		$this->log->add( 'QuickPay API Exception file: ' . $this->getFile() );
		$this->log->add( 'QuickPay API Exception line: ' . $this->getLine() );
		$this->log->add( 'QuickPay API Exception code: ' . $this->getCode() );
		$this->log->add( 'QuickPay API Exception message: ' . $this->getMessage() );
        
        if( ! empty($this->curl_request_data)) {
            $this->log->add( 'QuickPay API Exception Request: ' . $this->curl_request_data);
        }
        
		$this->log->separator();
        
	}
}
?>