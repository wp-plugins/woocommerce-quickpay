=== WooQuickpay ===
Contributors: PerfectSolution
Tags: gateway, woo commerce, quickpay, quick pay, gateway, integration, woocommerce, woocommerce quickpay, payment, payment gateway
Requires at least: 3.5.0
Tested up to: 3.8.1
Stable tag: 2.1.2
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrates your Quickpay payment gateway into your WooCommerce installation.

== Description ==
With WooQuickpay, you are able to integrate your Quickpay gateway to your WooCommerce install. With a wide list of API features including secure capturing, refunding and cancelling payments directly from your WooCommerce order overview. This is only a part of the many features found in this plugin.

== Installation ==
1. Upload the 'woocommerce-quickpay' folder to /wp-content/plugins/ on your server.
2. Log in to Wordpress administration, click on the 'Plugins' tab.
3. Find WooQuickpay in the plugin overview and activate it.
4. Go to WooCommerce -> Settings -> Payment Gateways -> Quickpay.
5. Fill in all the fields in the "Quickpay account" section and save the settings.
6. You are good to go.

== Changelog ==
= 2.1.2 =
* Fixed a undefined variable notices
* Switched from WC_Subscriptions_Order::get_price_per_period to WC_Subscriptions_Order::get_recurring_total
* Added transaction fee to orders
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
* Implemented a tweak to the "WooCommerce Sequential Order Numbers"-support which should fix any problems with WooQuickpay + Sequential order numbers.

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
