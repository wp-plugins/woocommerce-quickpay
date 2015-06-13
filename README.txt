=== WooCommerce Quickpay ===
Contributors: PerfectSolution
Donate link: http://perfect-solution.dk/donation
Tags: gateway, woo commerce, quickpay, quick pay, gateway, integration, woocommerce, woocommerce quickpay, payment, payment gateway
Requires at least: 4.0.0
Tested up to: 4.2.2
Stable tag: 4.1.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrates your Quickpay payment gateway into your WooCommerce installation.

== Description ==
With WooCommerce Quickpay, you are able to integrate your Quickpay gateway to your WooCommerce install. With a wide list of API features including secure capturing, refunding and cancelling payments directly from your WooCommerce order overview. This is only a part of the many features found in this plugin.

== Installation ==
1. Upload the 'woocommerce-quickpay' folder to /wp-content/plugins/ on your server.
2. Log in to Wordpress administration, click on the 'Plugins' tab.
3. Find WooCommerce Quickpay in the plugin overview and activate it.
4. Go to WooCommerce -> Settings -> Payment Gateways -> Quickpay.
5. Fill in all the fields in the "Quickpay account" section and save the settings.
6. You are good to go.

== Changelog ==

= 4.1.0 =
* Add Google Analytics support
* Performance optimization: The order view is now making async requests to retrieve the transaction state.
* Add complete order reference in order overview
* Add version number to the plugin settings page
* Add support for multiple instances. Now it is possible to add MobilePay, Paii and viaBill as separate payment methods. Each instance is based on the core module settings to ensure a minimum amount of configuration.
* Add setting: quickpay_redirect - allows the shop owner to enable/disable the auto redirection in the checkout process.
* Remove setting: quickpay_mobilepay
* Remove setting: quickpay_viabill
* Remove setting: quickpay_labelCreditCard
* Remove setting: quickpay_labelViaBill
* Remove setting: quickpay_debug
* Fix problem with attempt of payment capture when setting order status to complete on a subscription order. 
* Updated translations

= 4.0.7 =
* Add upgrade notifce for 4.0.0

= 4.0.6 = 
* Activate autofee settings
* Implement upgrade notices inside the plugins section
* Update incorrect autofee key in recurring requests
* Update success response HTTP codes
* Typecasting response to string if no message object is available

= 4.0.5 = 
* Add the possibility to set a custom branding ID

= 4.0.4 =
* Stop forcing HTTP on callbacks.

= 4.0.3 =
* Add WC_Quickpay_API_Subscription::is_action_allowed
* Manual AJAX actions handled for subscriptions

= 4.0.2 = 
* Add mobilepay option
* Disabled viabill since the Quickpay API is not ready to support it yet.

= 4.0.1 =
* Add version parameter to the payment request

= 4.0.0 =
* Now only supports the new Quickpay gateway platform
* Introduce exception class Quickpay_Exception
* Introduce exception class Quickpay_API_Exception
* Introduce WC_Quickpay::process_refund to support "auto" gateway refunds
* Introduce WC_Quickpay_API
* Introduce WC_Quickpay_API_Payment
* Introduce WC_Quickpay_API_Subscription
* Introduce WC_Quickpay_Log - Debugging information is now added to WooCommerce system logs.
* Remove WC_Quickpay_Request
* Remove donation link

= 3.0.9 = 
* Add support for important update notifications fetched from the README.txt file.

= 3.0.8 = 
* Switched to WC_Order::get_total() instead of WC_Order::order_total to fix issues with WPML currencies.

= 3.0.6 = 
* Added proper support for both Sequential Order Numbers FREE and Sequential Order Numbers PRO.

= 3.0.5 =
* Bugfix: 502 on checkout on shops hosted with wpengine.com. 

= 3.0.4 =
* Add filter 'woocommerce_quickpay_currency' which can be used to dynamically edit the gateway currency
* Add filter 'woocommerce_quickpay_language' which can be used to dynamically edit the gateway language

= 3.0.3 = 
* Added support for credit card icons in the settings.
* Re-implented auto redirect on checkout page

= 3.0.2 =
* Fixed MD5 hash problem when not in test mode

= 3.0.1 =
* Added refund support
* Update Danish i18n

= 3.0.0 =
* Completely refactored the plugin. The logic has been splitted into multiple classes, and a lot of bugs should've been eliminated with this version.
* Added ajax calls when using the API

= 2.1.6 = 
* Optimized fee handling

= 2.1.5 =
* Added support for Paii

= 2.1.4 =
* Added action links to "Installed plugins" overview
* Fixed md5 checksum error caused by testmode
* Fixed problem with coupons not working properly on subscriptions
* Fixed problem with lagging the use of payment_complete() on successful payments

= 2.1.3 =
* Added i18n support, current supported languages: en_UK, da_DK
* Added possibility to add email instructions on the order confirmation. Thanks to Emil Eriksen for idea and contribution.
* Added possibility to change test mode directly in WooCommerce. Thanks to Emil Eriksen for idea and contribution.
* Added eye candy in form of SVN header banner
* Added donation link to all of you lovely fellows who might wanna donate a coin for our work.

= 2.1.2 =
* Fixed an undefined variable notices
* Switched from WC_Subscriptions_Order::get_price_per_period to WC_Subscriptions_Order::get_recurring_total
* Added payment transaction fee to orders
* Changed name to WooCommerce Quickpay

= 2.1.1 =
* Fixes FATAL ERROR bug on checkout introduced in 2.1.0
* Plugin URI in gateway-quickpay.php

= 2.1.0 =
* Bugfix: Static call to a non-static method caused strict errors.
* Added support for WooCommerce 2.1.

= 2.0.9 =
* Bug where custom meta boxes were not instantiated should be fixed in this version
* More currencies added (SEK, NOK, GBP)

= 2.0.8 =
* Fixed viabill cardtypelock

= 2.0.7 =
* Fixed bug where server complains about Quickpay SSL certificate.
* Changed iBill labels to viaBill
* Added the possibility to set a custom text on the checkout page right before the customer is redirected to the Quickpay payment window.
* Added the possibility to set a custom label to credit card and viaBill.

= 2.0.6 =
* Fixed bug where recurring payments were not being captured properly.
* Fixed undefined variable notice "params_string".

= 2.0.4 =
* Implemented a tweak to the "WooCommerce Sequential Order Numbers"-support which should fix any problems with WooCommerce Quickpay + Sequential order numbers.

= 2.0.3 =
* Fixing issues with cardtypelocks

= 2.0.2 =
* Enabling auto redirect on receipt page which accidently got disabled in 2.0.1

= 2.0.1 =
* Updated a hook causing problems with saving gateway settings.

= 2.0.0 =
* Build to work with WooCommerce 2.0.x or higher
* Refactoring the majority of existing methods to save a lot of code and implementing better API error handling.

= 1.4.0 =
* Implement WC_Quickpay::create_md5() which manually sets the order of the md5 checkpoints. 
* Should fix payment integration and missing mails sent out to customers after implementation of protocol v7.

= 1.3.11 =
* Plugin now uses Quickpay version 7

= 1.3.10 =
* Feature: Allow customers to select between credit card and iBill when choosing Quickpay as pay method. Credit card is ticket as default option. 		NB: You are required to have an agreement with iBill in order to use this feature properly. 

= 1.3.9 =
* 'Capture on complete' now also works on bulk actions.
	
= 1.3.8 =
* Short install guide added to README.txt

= 1.3.7 =
* 'Capture on complete' is implemented as an option in the gateway settings. It can be turned on/off. Default: Off
* This is a faster way to process your orders. When the order state is set to "completed", the payment will automatically be capture. This works in both the order overview and in the single order view.

= 1.3.6 =
* Bugfix: Implemented missing check for WC Subscriptions resulting in fatal error on api_action_router().


= 1.3.5 =
* Bugfix: Problem with transaction ID not being connected to an order [FIXED].

= 1.3.4 =
* Added better support for "WooCommerce Sequential Order Numbers".
* Automatically redirects after 5 seconds on "Checkout -> Pay"-page.

= 1.3.3 =
* Bugfix: Corrected bug not showing price corectly on subscriptions in payment window.

= 1.3.1 =
* Bugfix: Systems not having WooCommerce Subscriptions enabled got a fatal error on payment site.

= 1.3.0 =
* Added support for WooCommerce subscription.
* Now reduces stock when a payment is completed.
	
= 1.2.2 =
* Bugfix: Capturing payments from WooCommerce backend caused problems due to missing order_total param in cURL request.
	
= 1.2.1 =
* More minor changes to the payment cancellations from Quickpay form.

= 1.2.0 =
* Major rewriting of payments cancelled by customer.

= 1.1.3 =
* Implemented payment auto capturing.

= 1.1.2 =
* Link back to payment page after payment cancellation added.

= 1.1.1 =	
* If a payment is cancelled by user, a $woocommerce->add_error will now be shown, notifying the customer about this. We also post a note to the order about cancellation.

= 1.1.0 =
* Changed plugin structure.
* core.js added to the plugin to avoid inline javascript.
* Implemented payment state and transaction id in order overview.
* Implemented payment handling in single order view.
* Added support for split payments
* If turned on in Quickpay Manager, shop owners may now split up the transactions.
* Rewritten and added a lot of the class methods.

= 1.0.1 =
*  Bugfix: Corrected a few unchecked variables that caused php notices in error logs.

== Upgrade Notice ==
= 4.0.0 =
4.0.0 is a major update. 4.0.0 will only work with the new Quickpay v10 manager, so it is advised to consult QuickPay before upgrading. Also, the plugin will require additional setup before working! It is advised to test this version out before upgrading in production.
