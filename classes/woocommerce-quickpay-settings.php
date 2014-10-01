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
					'title' => __( 'Quickpay account', 'woo-quickpay' ),
				),
					'quickpay_debug' => array(
						'title' => __( 'Enable debug mode', 'woo-quickpay' ), 
						'type' => 'checkbox', 
						'label' => __( 'Enable debug mode', 'woo-quickpay' ), 
						'default' => 'no'
					),
					'quickpay_testmode' => array(
						'title' => __( 'Enable test mode', 'woo-quickpay' ), 
						'type' => 'checkbox', 
						'label' => __( 'Enable test mode', 'woo-quickpay' ), 
						'default' => 'no'
					),
					'quickpay_merchantid' => array(
									'title' => __('QuickpayId', 'woo-quickpay'),
									'type' => 'text',
									'description' => __('Type in your merchant ID from Quickpay.', 'woo-quickpay')
					),
					'quickpay_md5secret' => array(
									'title' => __('Secret MD5 string', 'woo-quickpay'),
									'type' => 'text',
									'description' => __('This is the unique MD5 secret key, which the system uses to verify your transactions.')
					),
					'quickpay_apikey' => array(
						'title' => __('Quickpay API key', 'woo-quickpay'),
						'type' => 'text',
						'description' => __( 'The API key is unique and can be requested from within the Quickpay Administrator Tool', 'woo-quickpay' )
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
													'de'=>'German', 
													'en'=>'English', 
													'fr'=>'French', 
													'it'=>'Italian', 
													'no'=>'Norwegian', 
													'nl'=>'Dutch', 
													'pl'=>'Polish', 
													'se'=>'Swedish'
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
									'title' => __( 'Cardtype lock', 'woo-quickpay' ), 
									'type' => 'text', 
									'description' => __( 'Default: creditcard. Type in the cards you wish to accept (comma separated). See the valid payment types here: <b>http://quickpay.dk/features/cardtypelock/</b>', 'woo-quickpay' ), 
									'default' => 'creditcard'					
					),	
					'quickpay_splitcapture' => array(
									'title' => __( 'Enable split payments', 'woo-quickpay' ), 
									'type' => 'checkbox', 
									'label' => __( 'Accept split payments in your system.', 'woo-quickpay' ), 
									'description' => __( 'Remember to turn this on in your Quickpay Manager. Click for <a target="_blank" href="http://quickpay.dk/features/split-payment/">help</a>', 'woo-quickpay' ), 
									'default' => 'yes'
					),
					'quickpay_autocapture' => array(
									'title' => __( 'Allow autocapture', 'woo-quickpay' ), 
									'type' => 'checkbox', 
									'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
									'description' => __( 'Automatically capture payments.', 'woo-quickpay' ), 
									'default' => 'no'
					),
					'quickpay_captureoncomplete' => array(
									'title' => __( 'Capture on complete', 'woo-quickpay' ), 
									'type' => 'checkbox', 
									'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
									'description' => __( 'When enabled quickpay payments will automatically be captured when order state is set to "Complete".', 'woo-quickpay'), 
									'default' => 'no'
					),
					'quickpay_refundonrefunded' => array(
									'title' => __( 'Refund on order state \'refunded\'', 'woo-quickpay' ), 
									'type' => 'checkbox', 
									'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
									'description' => __( 'When enabled quickpay payments will automatically be refunded when order state is set to "Refunded".', 'woo-quickpay'), 
									'default' => 'no'
					),
					'quickpay_ibillOrCreditcard' => array(
									'title' => __( 'Choose credit card or viaBill on payment selection', 'woo-quickpay' ), 
									'type' => 'checkbox', 
									'label' => __( 'Enable/Disable', 'woo-quickpay' ), 
									'description' => __( 'Allows your customers to choose between viaBill and credit card when choosing type of payment. <b>(Requires viaBill agreement)</b>', 'woo-quickpay' ), 
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