<?php
/**
 * WC_Quickpay_Settings class
 *
 * @class 		WC_Quickpay_Settings
 * @version		1.0.0
 * @package		Woocommerce_Quickpay/Classes
 * @category	Class
 * @author 		PerfectSolution
 */
class WC_Quickpay_Settings {

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
								'label' => __( 'Enable Quickpay Payment', 'woo-quickpay' ), 
								'default' => 'yes'
							), 

				'_Account_setup' => array(
					'type' => 'title',
					'title' => __( 'Payment Window - Integration', 'woo-quickpay' ),
				),
					'quickpay_debug' => array(
						'title' => __( 'Enable debug mode', 'woo-quickpay' ), 
						'type' => 'checkbox', 
						'label' => __( 'Enable debug mode', 'woo-quickpay' ), 
						'default' => 'no'
					),
					'quickpay_merchantid' => array(
									'title' => __('Merchant id', 'woo-quickpay'),
									'type' => 'text',
									'description' => __('Your Payment Window agreement merchant id. Found in the "Integration" tab inside the Quickpay manager.', 'woo-quickpay')
					),
					'quickpay_agreement_id' => array(
									'title' => __('Agreement id', 'woo-quickpay'),
									'type' => 'text',
									'description' => __('Your Payment Window agreement id. Found in the "Integration" tab inside the Quickpay manager.', 'woo-quickpay' )
					),
					'quickpay_agreement_apikey' => array(
						'title' => __('Api key', 'woo-quickpay'),
						'type' => 'text',
						'description' => __( 'Your Payment Window agreement API key. Found in the "Integration" tab inside the Quickpay manager.', 'woo-quickpay' )
					),
					'quickpay_privatekey' => array(
						'title' => __('Private key', 'woo-quickpay'),
						'type' => 'text',
						'description' => __( 'Your Payment Window agreement private key. Found in the "Integration" tab inside the Quickpay manager.', 'woo-quickpay' )
					),
				'_API_setup' => array(
					'type' => 'title',
					'title' => __( 'API - Integration', 'woo-quickpay' ),
				),
					'quickpay_apikey' => array(
						'title' => __('Api User key', 'woo-quickpay'),
						'type' => 'text',
						'description' => __( 'Your API User\'s key. Create a separate API user in the "Users" tab inside the Quickpay manager.' , 'woo-quickpay' )
					),
				'_Extra_gateway_settings' => array(
					'type' => 'title',
					'title' => 'Extra gateway settings'
				),
					'quickpay_language' => array(
									'title' => __('Language', 'woo-quickpay'),
									'description' => __('Payment Window Language', 'woo-quickpay'),
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
									'default' => 'creditcard'					
					),
					'quickpay_branding_id' => array(
									'title' => __( 'Branding ID', 'woo-quickpay' ), 
									'type' => 'text', 
									'description' => __( 'Leave empty if you have no custom branding options', 'woo-quickpay' ), 
									'default' => ''					
					),	
					'quickpay_autocapture' => array(
									'title' => __( 'Allow autocapture', 'woo-quickpay' ), 
									'type' => 'checkbox', 
									'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
									'description' => __( 'Automatically capture payments.', 'woo-quickpay' ), 
									'default' => 'no'
					),
					/*
					'quickpay_autofee' => array(
									'title' => __( 'Enable autofee', 'woo-quickpay' ), 
									'type' => 'checkbox', 
									'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
									'description' => __( 'If enabled, the fee charged by the acquirer will be calculated and added to the transaction amount.', 'woo-quickpay' ), 
									'default' => 'no'
					),   */         
					'quickpay_captureoncomplete' => array(
									'title' => __( 'Capture on complete', 'woo-quickpay' ), 
									'type' => 'checkbox', 
									'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
									'description' => __( 'When enabled quickpay payments will automatically be captured when order state is set to "Complete".', 'woo-quickpay'), 
									'default' => 'no'
					),
                    /* NOTE: viabill not supported in the new manager yet
                    
					'quickpay_viabill' => array(
									'title' => __( 'Enable viaBill as a child option on checkout.', 'woo-quickpay' ), 
									'type' => 'checkbox', 
									'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
									'description' => __( 'Use this option if you offer both credit card and viaBill through Quickpay. If you offer only viaBill, leave this option and insert "viabill" into the "payment methods" field.', 'woo-quickpay' ), 
									'default' => 'no'
					),
                    */
            
					'quickpay_mobilepay' => array(
									'title' => __( 'Enable Mobilepay as a child option on checkout.', 'woo-quickpay' ), 
									'type' => 'checkbox', 
									'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
									'description' => __( 'Use this option if you offer both credit card and mobilepay through Quickpay. If you offer only Mobilepay, leave this option and insert "mobilepay" into the "payment methods" field.', 'woo-quickpay' ), 
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
									'default' => __( 'Quickpay', 'woo-quickpay' )
								),
					'description' => array(
									'title' => __( 'Customer Message', 'woo-quickpay' ), 
									'type' => 'textarea', 
									'description' => __( 'This controls the description which the user sees during checkout.', 'woo-quickpay' ), 
									'default' => 'Pay via Quickpay. Allows you to pay with your credit card via Quickpay.'
								),
					'instructions' => array(
						'title'       => __( 'Email instructions', 'woo-quickpay' ),
						'type'        => 'textarea',
						 'description' => __( 'Instructions that will be added to emails.', 'woo-quickpay' ),
						 'default'     => '',
					 ),
					'quickpay_paybuttontext' => array(
									'title' => __( 'Text on payment button', 'woo-quickpay' ), 
									'type' => 'text', 
									'description' => __( 'This text will show on the button which opens the Quickpay window.', 'woo-quickpay' ), 
									'default' => __( 'Open secure Quickpay window.', 'woo-quickpay' )
								),
					'quickpay_labelCreditCard' => array(
									'title' => __( 'Credit card label', 'woo-quickpay' ), 
									'type' => 'text', 
									'description' => __( 'Label shown when choosing credit card or viaBill.', 'woo-quickpay' ), 
									'default' => __( 'Credit card', 'woo-quickpay' )
								),
					'quickpay_labelViaBill' => array(
									'title' => __( 'viaBill label', 'woo-quickpay' ), 
									'type' => 'text', 
									'description' => __( 'Label shown when choosing credit card or viaBill.', 'woo-quickpay' ), 
									'default' => __( 'viaBill', 'woo-quickpay' )
								),
					'quickpay_icons' => array(
									'title' => __( 'Credit card icons', 'woo-quickpay' ),
									'type' => 'multiselect',
									'description' => __( 'Choose the card icons you wish to show next to the Quickpay payment option in your shop.', 'woo-quickpay' ),
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
									)
					),
					'quickpay_icons_maxheight' => array(
						'title' => __( 'Credit card icons maximum height', 'woo-quickpay' ),
						'type'  => 'number',
						'description' => __( 'Set the maximum pixel height of the credit card icons shown on the frontend.', 'woo-quickpay' ),
						'default' => 20
					),
					'quickpay_redirectText' => array(
									'title' => __( 'Redirect message', 'woo-quickpay' ), 
									'type' => 'textarea', 
									'description' => __( 'This message is shown right before the customer is automatically redirected to the Quickpay payment window.', 'woo-quickpay' ), 
									'default' => __('You will be automatically redirected to the payment window in <strong>5 seconds</strong>. Click on the button below if you are not redirected.', 'woo-quickpay' )
								),
				);	

			if( WC_Quickpay_Helper::subscription_is_active() )
			{			
				$fields['woocommerce-subscriptions'] = array(
					'type' => 'title',
					'title' => 'Subscriptions'
				);
				$fields['quickpay_autodraw_subscription'] = array(
						'title' => __( 'Automatically capture the first payment of a subscription.', 'woo-quickpay' ), 
						'type' => 'checkbox', 
						'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
						'description' => __( 'Automatically capture the first payment of a subscription when order is complete.', 'woo-quickpay' ), 
						'default' => 'yes'
				);
			}

		return $fields;
	}
}
?>