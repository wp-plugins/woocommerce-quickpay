<?php

class WC_Quickpay_MobilePay extends WC_Quickpay_Instance {
    
    public $main_settings = NULL;
    
    public function __construct() {
        parent::__construct();
        
        // Get gateway variables
        $this->id = 'MobilePay';
        
        $this->setup();
        
        $this->title = $this->s('title');
        $this->description = $this->s('description');
        
        add_filter( 'woocommerce_quickpay_cardtypelock_mobilepay', array( $this, 'filter_cardtypelock' ) );
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
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'woo-quickpay' ), 
                'type' => 'checkbox', 
                'label' => __( 'Enable MobilePay payment', 'woo-quickpay' ), 
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
                    'default' => __('MobilePay', 'woo-quickpay')
                ),
                'description' => array(
                    'title' => __( 'Customer Message', 'woo-quickpay' ), 
                    'type' => 'textarea', 
                    'description' => __( 'This controls the description which the user sees during checkout.', 'woo-quickpay' ), 
                    'default' => __('Pay with your mobile phone', 'woo-quickpay')
                ),
                'quickpay_paybuttontext' => array(
                    'title' => __( 'Text on payment button', 'woo-quickpay' ), 
                    'type' => 'text', 
                    'description' => __( 'This text will show on the button which opens the Quickpay window.', 'woo-quickpay' ), 
                    'default' => __( 'Open secure Quickpay window.', 'woo-quickpay' )
                ),
        );
    }
   
    
    /**
    * filter_cardtypelock function.
    *
    * Sets the cardtypelock
    *
    * @access public
    * @return string
    */
    public function filter_cardtypelock( )
    {
        return 'mobilepay';
    }
}
