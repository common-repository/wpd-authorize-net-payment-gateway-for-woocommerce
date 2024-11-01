=== WPD Authorize.net Payment Gateway for WooCommerce ===

Contributors:      songbirdwebdev
Plugin Name:       WPD Authorize.net Payment Gateway for WooCommerce
Plugin URI:        wpplugindesign.com
Tags:              ecommerce, e-commerce, store, sales, sell, shop, cart, checkout, storefront, woo commerce, woocommerce, payment gateway, authorize.net, credit card, secure payment     
Author:            Carmen Borrelli
Donate link:       wpplugindesign.com
Requires at least: 4.4
Tested up to:      4.9.1
Stable tag:        0.7
Version:           0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WPD Authorize.net Payment Gateway for WooCommerce allows your website customers to securely make payments using the Authorize.net payment gateway.

== Description ==

WPD Authorize.net Payment Gateway for WooCommerce is a WooCommerce addon which implements a secure payment gateway for Credit Card Payments via Authorize.Net.

Once a customer checks out on your website, the transaction details will appear in a table on the single order screen in the Wordpress admin. This table shows successful transactions, as well as those held for review due to the triggering of an Authorize.net security filter. The current status of the Authorize.net transaction is displayed and refreshed every time the single order page is loaded.

Settings included in WooCommerce->Settings->Checkout:

* Payment gateway title customer sees during checkout
* Payment gateway description customer sees during checkout
* Authorize.net API Login Id
* Authorize.net Transaction Key
* Sandbox or Live mode
* Order Status the order will be set to when payment is successfully captured
* Order Status the order will be set to when payment is held for review
* Message displayed to customer on checkout success screen when transaction has been held for review
* Whether or not to show above message on checkout success screen

You can set the gateway to run in the Authorize.net sandbox for testing. 
You can set the order status an order gets set to after checkout based on the transaction result.
You can enter a custom Held for Review message to be displayed on the checkout thank you page, and a choice whether or not to show that message.

A premium version of the plugin will soon be available at wpplugindesign.com which provides full AIM (Advanced Integration Module) features in your Wordpress admin. These include authorization on checkout, capturing authorized payments, capturing a lesser amount than authorized, voids, refunds, and approval of held for review transactions.

== Installation ==

= Minimum Requirements =

* PHP version 5.4.0+
* MySQL version 5.0+ (5.6+ recommended)
* WordPress 4.4+
* cURL PHP Extension
* JSON PHP Extension

= Manual or Automatic Installation =

1. Upload the plugin to the '/wp-content/plugins/' directory, or install the plugin zip file through the WordPress plugins screen directly.
2. Activate the plugin on the 'Plugins' screen in the WordPress admin.
3. Use WooCommerce->Settings->Checkout in the WordPress admin to enable and configure the WPD Authorize.Net payment gateway.
4. View transaction data on the WooCommerce single order page in the WordPress admin, as well as the current Authorize.net order status.

== Screenshots ==

1. Plugin settings within WooCommerce.
2. Authorize.net transaction table on admin single order page.

== Changelog ==

= 0.7 - 2017-12-16 =
* replaced [PHP SDK for Authorize.Net API](https://github.com/AuthorizeNet/sdk-php) with [Library that abstracts Authorize.Net's JSON APIs](https://github.com/stymiee/authnetjson)

= 0.5 - 2017-12-13 =
* original numbered version approved by WordPress Plugin Directory

== Upgrade Notice ==

= 0.7 =
Replaced authorize.net api php sdk with smaller and less dependency-heavy alternative.
