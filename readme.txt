=== WZ SenangPay for WooCommerce ===
Contributors: wanzulnet
Tags: senangpay,paymentgateway,fpx,visa,mastercard,malaysia
Tested up to: 4.8
Stable tag: 1.03
Donate link: https://www.billplz.com/hpojtffm3
Requires at least: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept Internet Banking Payment, Visa & MasterCard by using SenangPay. 

== Description ==
Install this plugin to accept payment using SenangPay (FPX, Visa & MasterCard). 

Features:

1. Support for Callback URL

2. Requery status for payment validation

3. Dual mode Instant Payment Notification

How to Install:

1. Install this plugin
2. At senangPay Shopping Integration Link, Setup:

    2.1 Return URL: http://www.yourdomain.com/?wc-api=wc_senangpay_gateway

    2.2 Parameters: &status_id=[TXN_STATUS]&order_id=[ORDER_ID]&transaction_id=[TXN_REF]&amount=[AMOUNT]&hash=[HASH]
    
    2.3 Callback URL: http://www.yourdomain.com/?wc-api=wc_senangpay_gateway
3. At your WordPress Admin Dashboard, Setup Merchant ID and Secret Key
4. Leave it Verification Type as Both
5. Save Changes

== Upgrade Notice == 
* None

== Installation ==

1. Install this plugin
2. At senangPay Shopping Integration Link, Setup:

    2.1 Return URL: http://www.yourdomain.com/?wc-api=wc_senangpay_gateway

    2.2 Parameters: &status_id=[TXN_STATUS]&order_id=[ORDER_ID]&transaction_id=[TXN_REF]&amount=[AMOUNT]&hash=[HASH]
    
    2.3 Callback URL: http://www.yourdomain.com/?wc-api=wc_senangpay_gateway
3. At your WordPress Admin Dashboard, Setup Merchant ID and Secret Key
4. Leave it Verification Type as Both
5. Save Changes

== Screenshots ==
* Will available soon

== Changelog ==

= 1.03 =
1. IMPROVED: Increased wp_safe_remote_get for callback verification from default 5 seconds to 20 seconds.

= 1.02 =
1. IMPROVED: Fix for WooCommerce 3.0 API Issue (do_it_wrong: wc_order)
2. REMOVED: Support for WooCommerce 2.x is removed.

= 1.01 =
1. Callback will print OK message according to senangPay API

= 1.0 =
1. Initial Release

== Frequently Asked Questions ==

= Where can I get Merchant ID and Secret Key? =

You can get the information at your SenangPay website. Login to [SenangPay](https://app.senangpay.my/setting/profile)

= Why the order is not automatically after customer make payment? =

This is because the customer closed the page immediately after payment and bug from senangPay

= Troubleshooting =

* Cannot receive payment?

    Check your Merchant ID and SecretKey

* Order status does not updated immediately after payment?

    Make sure verification type is set to Both

== Links ==
[Wanzul Hosting](http://wanzul-hosting.com/) is the most reliable, cheap, recommended by the most web master around the world.

== Thanks ==
Special thanks to Faiz Edzahar for support on this project
