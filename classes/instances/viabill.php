<?php

class WC_QuickPay_ViaBill extends WC_QuickPay_Instance {
    
    public $main_settings = NULL;
    
    public function __construct() {
        parent::__construct();
        
        // Get gateway variables
        $this->id = 'viabill';
        
        $this->method_title = 'QuickPay - ViaBill';
        
        $this->setup();
        
        $this->title = $this->s('title');
        $this->description = $this->s('description');
        
        add_filter( 'woocommerce_quickpay_cardtypelock_viabill', array( $this, 'filter_cardtypelock' ) );
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
                'label' => __( 'Enable ViaBill payment', 'woo-quickpay' ), 
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
                    'default' => __('ViaBill', 'woo-quickpay')
                ),
                'description' => array(
                    'title' => __( 'Customer Message', 'woo-quickpay' ), 
                    'type' => 'textarea', 
                    'description' => __( 'This controls the description which the user sees during checkout.', 'woo-quickpay' ), 
                    'default' => __('Pay with ViaBill', 'woo-quickpay')
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
        return 'viabill';
    }
}
