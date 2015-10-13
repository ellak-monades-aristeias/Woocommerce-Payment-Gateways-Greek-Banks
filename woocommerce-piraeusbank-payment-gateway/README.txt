=== Piraeus Bank Greece Payment Gateway for WooCommerce ===
Contributors: mpbm23, emspacegr
Tags: ecommerce, woocommerce, payment gateway
Tested up to: 4.3.1
Requires at least: 4.0
Stable tag: 1.0.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Adds Piraeus Bank paycenter as a payment Gateway for WooCommerce

== Description ==
Adds Piraeus Bank paycenter as a payment Gateway for WooCommerce. A contract between you and the Bank must be previously signed.
It uses the redirect method, and SSL is not required.
Source code is available at [Github](htps://github.com/ellak-monades-aristeias/Woocommerce-Payment-Gateways-Greek-Banks)

== Features ==
Provides pre-auth transactions and free installments.

== Installation ==

Just follow the standard [WordPress plugin installation procedure](http://codex.wordpress.org/Managing_Plugins).

Provide to Piraeus bank at epayments@piraeusbank.gr the following information, in order to provide you with test account information. 

* Website url :  http(s)://yourdomain.gr/
* Referrer url : http(s)://yourdomain.gr/checkout/
* Success page :  http(s)://yourdomain.gr/wc-api/WC_Piraeusbank_Gateway?peiraeus=success
* Failure page : http(s)://yourdomain.gr/wc-api/WC_Piraeusbank_Gateway?peiraeus=fail
* Cancel page : http(s)://yourdomain.gr/wc-api/WC_Piraeusbank_Gateway?peiraeus=cancel
* Response method : GET
* Your's server IP Address 


== Frequently asked questions ==

= Does it work? =

Yes

== Changelog ==
= 1.0.0 =
Initial Release

