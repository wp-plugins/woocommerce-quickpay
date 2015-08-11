<?php
/**
 * WC_QuickPay_API class
 *
 * @class 		WC_QuickPay_API
 * @since		4.0.0
 * @package		Woocommerce_QuickPay/Classes
 * @category	Class
 * @author 		PerfectSolution
 * @docs        http://tech.quickpay.net/api/services/?scope=merchant
 */

class WC_QuickPay_API 
{

    /**
     * Contains cURL instance
     * @access protected
     */    
    protected $ch;


    /**
     * Contains the API url
     * @access protected
     */
    protected $api_url = 'https://api.quickpay.net/';


	/**
	 * Contains a resource data object
	 * @access private
	 */
	protected $resource_data;
    

  	/**
	* __construct function.
	*
	* @access public
	* @return void
	*/      
    public function __construct() 
    {
        add_action('shutdown', array($this, 'shutdown'));

        // Instantiate an empty object ready for population
        $this->resource_data = new stdClass();
    }
    

  	/**
	* is_authorized_callback function.
	*
	* Performs a check on payment callbacks to see if it is legal or spoofed
	*
	* @access public
	* @return boolean
	*/  
    public function is_authorized_callback( $response_body ) 
    {
        if( ! isset( $_SERVER["HTTP_QUICKPAY_CHECKSUM_SHA256"] ) ) 
        {
            return FALSE;
        }
            
        return hash_hmac( 'sha256', $response_body, WC_QP()->s( 'quickpay_privatekey' ) ) == $_SERVER["HTTP_QUICKPAY_CHECKSUM_SHA256"];
    }
   

  	/**
	* get function.
	*
	* Performs an API GET request
	*
	* @access public
	* @return object
	*/    
    public function get( $path ) 
    {
    	// Instantiate a new instance
        $this->remote_instance();

        // Set the request params
        $this->set_url( $path );

        // Start the request and return the response
        return $this->execute('GET');
    }
 

   	/**
	* post function.
	*
	* Performs an API POST request
	*
	* @access public
	* @return object
	*/    
    public function post( $path, $form = array() ) 
    {
    	// Instantiate a new instance
        $this->remote_instance();

        // Set the request params
        $this->set_url( $path );

        // Start the request and return the response
        return $this->execute('POST', $form);
    }	


   	/**
	* put function.
	*
	* Performs an API PUT request
	*
	* @access public
	* @return object
	*/    
    public function put( $path, $form = array() ) 
    {
    	// Instantiate a new instance
        $this->remote_instance();

        // Set the request params
        $this->set_url( $path );

        // Start the request and return the response
        return $this->execute('PUT', $form);
    }	


   	/**
	* execute function.
	*
	* Executes the API request
	*
	* @access public
	* @param  string $request_type
	* @param  array  $form
	* @return object
	* @throws QuickPay_API_Exception
	*/   	
 	public function execute( $request_type, $form = array() ) 
 	{
 		// Set the HTTP request type
 		curl_setopt( $this->ch, CURLOPT_CUSTOMREQUEST, $request_type );
        
        // Prepare empty variable passed to any exception thrown
        $request_form_data = '';
        
 		// If additional data is delivered, we will send it along with the API request
 		if( is_array( $form ) && ! empty( $form ) )
 		{
            // Build a string query based on the form array values
            $request_form_data = http_build_query( $form, '', '&' );
            
            // Prepare to post the data string
 			curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $request_form_data );
 		}

 		// Execute the request and decode the response to JSON
 		$this->resource_data = json_decode( curl_exec( $this->ch ) );

 		// Retrieve the HTTP response code
 		$response_code = (int) curl_getinfo( $this->ch, CURLINFO_HTTP_CODE );

 		// If the HTTP response code is higher than 299, the request failed.
 		// Throw an exception to handle the error
 		if( $response_code > 299 ) 
 		{
            if( isset($this->resource_data->errors) ) 
            {
                $error_messages = "";
                foreach( $this->resource_data->errors as $error_field => $error_descriptions ) 
                {
                    $error_messages .= "{$error_field}: ";

                    foreach( $error_descriptions as $error_description )
                    {
                        $error_messages .= "{$error_description}<br />\n";
                    }
                }
                
                throw new QuickPay_API_Exception( $error_messages, $response_code, NULL, $request_form_data );
            }
            else if( isset( $this->resource_data->message) ) 
            {
                throw new QuickPay_API_Exception( $this->resource_data->message, $response_code, NULL, $request_form_data ); 
            }
            else 
            {
                throw new QuickPay_API_Exception( (string) json_encode($this->resource_data), $response_code, NULL, $request_form_data );
            }
 			
 		}
        
 		// Everything went well, return the resource data object.
 		return $this->resource_data;
 	}


  	/**
	* set_url function.
	*
	* Takes an API request string and appends it to the API url
	*
	* @access public
	* @return void
	*/   
    public function set_url( $params ) 
    {
        curl_setopt( $this->ch, CURLOPT_URL, $this->api_url . trim( $params, '/' ) );
    }
    
 
 	/**
	* remote_instance function.
	*
	* Create a cURL instance if none exists already
	*
	* @access public
	* @return cURL object
	*/
	protected function remote_instance() 
	{
		if( $this->ch === NULL ) 
		{
			$this->ch = curl_init();
			curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, TRUE );
			curl_setopt( $this->ch, CURLOPT_SSL_VERIFYPEER , FALSE );
			curl_setopt( $this->ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
            curl_setopt( $this->ch, CURLOPT_HTTPHEADER, array(
            	'Authorization: Basic ' . base64_encode(':' . WC_QP()->s( 'quickpay_apikey' ) ),
                'Accept-Version: v10',
                'Accept: application/json',
                'QuickPay-Callback-Url: ' . WC_QuickPay_Helper::get_callback_url(),
            ));
		}

		return $this->ch;		 	
	}

    
	/**
	* shutdown function.
	*
	* Closes the current cURL connection
	*
	* @access public
	* @return void
	*/
	public function shutdown() 
	{
		if( ! empty( $this->ch ) ) 
		{
			curl_close( $this->ch );
		}		
	}
}