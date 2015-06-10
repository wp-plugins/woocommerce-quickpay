<?php
/**
 * WC_Quickpay_Helper class
 *
 * @class 		WC_Quickpay_Helper
 * @version		1.0.0
 * @package		Woocommerce_Quickpay/Classes
 * @category	Class
 * @author 		PerfectSolution
 */

class WC_Quickpay_Helper {


	/**
	* price_multiply function.
	*
	* Returns the price with no decimals. 10.10 returns as 1010.
	*
	* @access public static
	* @return integer
	*/
	public static function price_multiply( $price ) {
		return number_format($price * 100, 0, '', '');
	}


	/**
	* price_normalize function.
	*
	* Returns the price with decimals. 1010 returns as 10.10.
	*
	* @access public static
	* @return float
	*/
	public static function price_normalize( $price ) {
		return number_format($price / 100, 2, '.', '');
	}


	/**
	* format_msgtype function.
	*
	* Returns the msgtype with the correct formatted ending
	*
	* @access public static
	* @return string
	*/
	public static function format_msgtype( $msgtype ) {
		$lc = substr( $msgtype , -1 );
		if( $lc == 'l' )
			$append = 'led';
		else
			$append = ( $lc == 'e' ) ? 'd' : 'ed';

		return $msgtype . $append;
	}


	/**
	* subscription_is_active function.
	*
	* Checks if Woocommerce Subscriptions is enabled or not
	*
	* @access public static
	* @return string
	*/
	public static function subscription_is_active() {
		if( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
			return TRUE;
		} 

		return FALSE;
	}


	/**
	* enqueue_javascript_backend function.
	*
	* @access public static
	* @return string
	*/
	public static function enqueue_javascript_backend() {
	    wp_enqueue_script( 'quickpay-backend', plugins_url( '/assets/javascript/backend.js', dirname( __FILE__ ) ), array( 'jquery' ) );
	    wp_localize_script( 'quickpay-backend', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}


	/**
	* enqueue_javascript_redirect function.
	*
	* @access public static
	* @return string
	*/
	public static function enqueue_javascript_redirect() {
	    wp_enqueue_script( 'quickpay-redirect', plugins_url( '/assets/javascript/redirect.js', dirname( __FILE__ ) ), array( 'jquery' ) );
	}


	/**
	* enqueue_stylesheet function.
	*
	* @access public static
	* @return string
	*/
	public static function enqueue_stylesheet() {
		wp_enqueue_style( 'style', plugins_url( '/assets/stylesheets/woocommerce-quickpay.css', dirname( __FILE__ ) ) );
	}


	/**
	* load_i18n function.
	*
	* @access public static
	* @return void
	*/
	public static function load_i18n() {
		load_plugin_textdomain( 'woo-quickpay' , FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}


	/**
	* option_is_enabled function.
	*
	* Checks if a setting options is enabled by checking on yes/no data.
	*
	* @access public static
	* @return void
	*/
	public static function option_is_enabled( $value ) {
		return ( $value == 'yes' ) ? 1 : 0;
	}	


	/**
	* prefix_order_number function.
	*
	* Returns a prefixed order number to prevent duplicate order numbers in Quickpay
	*
	* @access public static 
	* @return string
	*/	
	public static function prefix_order_number( $order_number ) {
		return substr( md5( time()), 0, 3 ) . '-QP-' . str_pad( $order_number , 4, 0, STR_PAD_LEFT );
	}
}
?>