<?php
/**
 * WC_QuickPay_Settings class
 *
 * @class 		WC_QuickPay_Settings
 * @version		1.0.0
 * @package		Woocommerce_QuickPay/Classes
 * @category	Class
 * @author 		PerfectSolution
 */
class WC_QuickPay_Settings {

	/**
	* get_fields function.
	*
	* Returns an array of available admin settings fields
	*
	* @access public static
	* @return array
	*/
	public static function get_fields()
	{
		$fields = 
			array(
				'enabled' => array(
                    'title' => __( 'Enable/Disable', 'woo-quickpay' ), 
                    'type' => 'checkbox', 
                    'label' => __( 'Enable QuickPay Payment', 'woo-quickpay' ), 
                    'default' => 'yes'
                ), 

				'_Account_setup' => array(
					'type' => 'title',
					'title' => __( 'Payment Window - Integration', 'woo-quickpay' ),
				),
					'quickpay_merchantid' => array(
                        'title' => __('Merchant id', 'woo-quickpay'),
                        'type' => 'text',
                        'description' => __('Your Payment Window agreement merchant id. Found in the "Integration" tab inside the QuickPay manager.', 'woo-quickpay'),
                        'desc_tip' => true
                    ),
					'quickpay_agreement_id' => array(
                        'title' => __('Agreement id', 'woo-quickpay'),
                        'type' => 'text',
                        'description' => __('Your Payment Window agreement id. Found in the "Integration" tab inside the QuickPay manager.', 'woo-quickpay' ),
                        'desc_tip' => true,
					),
					'quickpay_agreement_apikey' => array(
						'title' => __('Api key', 'woo-quickpay'),
						'type' => 'text',
						'description' => __( 'Your Payment Window agreement API key. Found in the "Integration" tab inside the QuickPay manager.', 'woo-quickpay' ),
					    'desc_tip' => true,
                    ),
					'quickpay_privatekey' => array(
						'title' => __('Private key', 'woo-quickpay'),
						'type' => 'text',
						'description' => __( 'Your Payment Window agreement private key. Found in the "Integration" tab inside the QuickPay manager.', 'woo-quickpay' ),
                        'desc_tip' => true,
					),
				'_API_setup' => array(
					'type' => 'title',
					'title' => __( 'API - Integration', 'woo-quickpay' ),
				),
					'quickpay_apikey' => array(
						'title' => __('Api User key', 'woo-quickpay'),
						'type' => 'text',
						'description' => __( 'Your API User\'s key. Create a separate API user in the "Users" tab inside the QuickPay manager.' , 'woo-quickpay' ),
                        'desc_tip' => true,
					),
				'_Extra_gateway_settings' => array(
					'type' => 'title',
					'title' => 'Extra gateway settings'
				),
					'quickpay_language' => array(
                        'title' => __('Language', 'woo-quickpay'),
                        'description' => __('Payment Window Language', 'woo-quickpay'),
                        'desc_tip' => true,
                        'type' => 'select',
                        'options' => array(
                            'da' => 'Danish',
                            'de' =>'German', 
                            'en' =>'English', 
                            'fr' =>'French', 
                            'it' =>'Italian', 
                            'no' =>'Norwegian', 
                            'nl' =>'Dutch', 
                            'pl' =>'Polish', 
                            'se' =>'Swedish'
                        )
					),
					'quickpay_currency' => array(
                        'title' => __('Currency', 'woo-quickpay'),
                        'description' => __('Choose your currency. Please make sure to use the same currency as in your WooCommerce currency settings.', 'woo-quickpay' ),
                        'desc_tip' => true,
                        'type' => 'select',
                        'options' => array(
                            'DKK' => 'DKK', 
                            'EUR' => 'EUR',
                            'GBP' => 'GBP',
                            'NOK' => 'NOK',
                            'SEK' => 'SEK',
                            'USD' => 'USD'
                        )
					),
					'quickpay_cardtypelock' => array(
                        'title' => __( 'Payment methods', 'woo-quickpay' ), 
                        'type' => 'text', 
                        'description' => __( 'Default: creditcard. Type in the cards you wish to accept (comma separated). See the valid payment types here: <b>http://tech.quickpay.net/appendixes/payment-methods/</b>', 'woo-quickpay' ), 
                        'default' => 'creditcard',
					),
					'quickpay_branding_id' => array(
                        'title' => __( 'Branding ID', 'woo-quickpay' ), 
                        'type' => 'text', 
                        'description' => __( 'Leave empty if you have no custom branding options', 'woo-quickpay' ), 
                        'default' => '',
                        'desc_tip' => true,
					),	
					'quickpay_autocapture' => array(
                        'title' => __( 'Allow autocapture', 'woo-quickpay' ), 
                        'type' => 'checkbox', 
                        'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
                        'description' => __( 'Automatically capture payments.', 'woo-quickpay' ), 
                        'default' => 'no',
                        'desc_tip' => true,
					),

					'quickpay_autofee' => array(
                        'title' => __( 'Enable autofee', 'woo-quickpay' ), 
                        'type' => 'checkbox', 
                        'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
                        'description' => __( 'If enabled, the fee charged by the acquirer will be calculated and added to the transaction amount.', 'woo-quickpay' ), 
                        'default' => 'no',
                        'desc_tip' => true,
					),        
					'quickpay_captureoncomplete' => array(
                        'title' => __( 'Capture on complete', 'woo-quickpay' ), 
                        'type' => 'checkbox', 
                        'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
                        'description' => __( 'When enabled quickpay payments will automatically be captured when order state is set to "Complete".', 'woo-quickpay'), 
                        'default' => 'no',
                        'desc_tip' => true,
					),

        
				'_Shop_setup' => array(
					'type' => 'title',
					'title' => __( 'Shop setup', 'woo-quickpay' ),
				),
					'title' => array(
                        'title' => __( 'Title', 'woo-quickpay' ), 
                        'type' => 'text', 
                        'description' => __( 'This controls the title which the user sees during checkout.', 'woo-quickpay' ), 
                        'default' => __( 'QuickPay', 'woo-quickpay' ),
                        'desc_tip' => true,
                    ),
					'description' => array(
                        'title' => __( 'Customer Message', 'woo-quickpay' ), 
                        'type' => 'textarea', 
                        'description' => __( 'This controls the description which the user sees during checkout.', 'woo-quickpay' ), 
                        'default' => 'Pay via QuickPay. Allows you to pay with your credit card via QuickPay.',
                        'desc_tip' => true,
                    ),
					'instructions' => array(
                        'title'       => __( 'Email instructions', 'woo-quickpay' ),
                        'type'        => 'textarea',
                        'description' => __( 'Instructions that will be added to emails.', 'woo-quickpay' ),
                        'default'     => '',
                        'desc_tip' => true,
					 ),
					'quickpay_icons' => array(
                        'title' => __( 'Credit card icons', 'woo-quickpay' ),
                        'type' => 'multiselect',
                        'description' => __( 'Choose the card icons you wish to show next to the QuickPay payment option in your shop.', 'woo-quickpay' ),
                        'desc_tip' => true,
                        'class'             => 'wc-enhanced-select',
                        'css'               => 'width: 450px;',
                        'custom_attributes' => array(
                            'data-placeholder' => __( 'Select icons', 'woo-quickpay' )
                        ),
                        'default' => '',
                        'options' => array(
                            'dankort' => 'Dankort',
                            'edankort' => 'eDankort',
                            'visa'	=> 'Visa',
                            'visaelectron' => 'Visa Electron',
                            'mastercard' => 'Mastercard',
                            'maestro' => 'Maestro',
                            'jcb' => 'JCB',
                            'americanexpress' => 'American Express',
                            'diners' => 'Diner\'s Club',
                            'discovercard' => 'Discover Card',
                            'viabill' => 'ViaBill',
                            'paypal' => 'Paypall',
                            'danskebank' => 'Danske Bank',
                            'nordea' => 'Nordea',
                            'paii' => 'Paii',
                            'mobilepay' => 'MobilePay',
                            'forbrugsforeningen' => 'Forbrugsforeningen'
                        ),
					),
					'quickpay_icons_maxheight' => array(
						'title' => __( 'Credit card icons maximum height', 'woo-quickpay' ),
						'type'  => 'number',
						'description' => __( 'Set the maximum pixel height of the credit card icons shown on the frontend.', 'woo-quickpay' ),
						'default' => 20,
                        'desc_tip' => true,
					),      
                'Google Analytics' => array(
					'type' => 'title',
					'title' => __( 'Google Analytics', 'woo-quickpay' ),
				),
					'quickpay_google_analytics_tracking_id' => array(
                        'title' => __( 'Tracking ID', 'woo-quickpay' ), 
                        'type' => 'text', 
                        'description' => __( 'Your Google Analytics tracking ID. Digits only.', 'woo-quickpay' ), 
                        'default' => '',
                        'desc_tip' => true,
                    ),
					'quickpay_google_analytics_client_id' => array(
                        'title' => __( 'Client ID', 'woo-quickpay' ), 
                        'type' => 'text', 
                        'description' => __( 'Your Google Analytics client ID. Digits only.', 'woo-quickpay' ), 
                        'default' => '',
                        'desc_tip' => true,
                    ),
            
                'CustomVariables' => array(
					'type' => 'title',
					'title' => __( 'Custom Variables', 'woo-quickpay' ),
				),
                    'quickpay_custom_variables' => array(
                        'title'             => __( 'Select Information', 'woo-quickpay' ),
                        'type'              => 'multiselect',
                        'class'             => 'wc-enhanced-select',
                        'css'               => 'width: 450px;',
                        'default'           => '',
                        'description'       => __( 'Selected options will store the specific data on your transaction inside your QuickPay Manager.', 'woo-quickpay' ),
                        'options'           => self::custom_variable_options(),
                        'desc_tip'          => true,
                        'custom_attributes' => array(
                            'data-placeholder' => __( 'Select order data', 'woo-quickpay' )
                        )
                    ),
				);	

			if( WC_QuickPay_Helper::subscription_is_active() )
			{			
				$fields['woocommerce-subscriptions'] = array(
					'type' => 'title',
					'title' => 'Subscriptions'
				);
				$fields['quickpay_autodraw_subscription'] = array(
						'title' => __( 'Autocapture first payment', 'woo-quickpay' ), 
						'type' => 'checkbox', 
						'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
						'description' => __( 'Automatically capture the first payment of a subscription when order is complete.', 'woo-quickpay' ), 
						'default' => 'yes',
                        'desc_tip' => true,
				);
			}

		return $fields;
	}
    
    
	/**
	* custom_variable_options function.
	*
	* Provides a list of custom variable options used in the settings
	*
	* @access private
	* @return array
	*/    
    private static function custom_variable_options()
    {
        $options = array(
            'billing_all_data'      => __( 'Billing: Complete Customer Details', 'woo-quickpay' ), 
            'browser_useragent'     => __( 'Browser: User Agent', 'woo-quickpay' ),
            'customer_email'        => __( 'Customer: Email Address', 'woo-quickpay' ),
            'customer_phone'        => __( 'Customer: Phone Number', 'woo-quickpay' ),
            'shipping_all_data'     => __( 'Shipping: Complete Customer Details', 'woo-quickpay' ),
            'shipping_method'       => __( 'Shipping: Shipping Method', 'woo-quickpay' ),
        );
        
        asort($options);
        
        return $options;
    }
}
?>