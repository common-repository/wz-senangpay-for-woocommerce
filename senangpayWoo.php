<?php

/**
 * Plugin Name: WZ SenangPay for WooCommerce
 * Plugin URI: https://wordpress.org/plugins-wp/wz-senangpay-for-woocommerce/
 * Description: senangPay | Accept Payment using all participating FPX Banking Channels, Visa & MasterCard. <a href="http://www.senangpay.my" target="_blank">Sign up Now</a>.
 * Author: Wan Zulkarnain
 * Author URI: http://www.wanzul.net/donate
 * Version: 1.03
 * License: GPLv3
 * Text Domain: wcsenangpay
 * Domain Path: /languages/
 */
// How to use
// Set Return URL : <WordPress URL>/?wc-api=wc_senangpay_gateway
// Parameter : &status_id=[TXN_STATUS]&order_id=[ORDER_ID]&transaction_id=[TXN_REF]&amount=[AMOUNT]&hash=[HASH]
// Callback : http://wanzul.sv2.wanzul-hosting.com/wp/?wc-api=wc_senangpay_gateway
// Add settings link on plugin page
function senangpay_for_woocommerce_plugin_settings_link($links) {
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=senangpay">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'senangpay_for_woocommerce_plugin_settings_link');

function wcsenangpay_woocommerce_fallback_notice() {
    $message = '<div class="error">';
    $message .= '<p>' . __('WooCommerce senangPay Gateway depends on the last version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work!', 'wcsenangpay') . '</p>';
    $message .= '</div>';
    echo $message;
}

//Load the function
add_action('plugins_loaded', 'wcsenangpay_gateway_load', 0);

/**
 * Load senangPay gateway plugin function
 * 
 * @return mixed
 */
function wcsenangpay_gateway_load() {
    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'wcsenangpay_woocommerce_fallback_notice');
        return;
    }
    //Load language
    load_plugin_textdomain('wcsenangpay', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    add_filter('woocommerce_payment_gateways', 'wcsenangpay_add_gateway');

    /**
     * Add senangPay gateway to ensure WooCommerce can load it
     * 
     * @param array $methods
     * @return array
     */
    function wcsenangpay_add_gateway($methods) {
        $methods[] = 'WC_senangPay_Gateway';
        return $methods;
    }

    /**
     * Define the senangPay gateway
     * 
     */
    class WC_senangPay_Gateway extends WC_Payment_Gateway {

        /** @var bool Whether or not logging is enabled */
        public static $log_enabled = false;

        /** @var WC_Logger Logger instance */
        public static $log = false;

        /**
         * Construct the senangPay gateway class
         * 
         * @global mixed $woocommerce
         */
        public function __construct() {
            global $woocommerce;
            $this->id = 'senangpay';
            $this->has_fields = false;
            $this->method_title = __('senangPay', 'wcsenangpay');
            $this->icon = plugins_url('assets/senangpay.png', __FILE__);
            $this->debug = 'yes' === $this->get_option('debug', 'no');
            // Load the form fields.
            $this->init_form_fields();
            // Load the settings.
            $this->init_settings();
            // Define user setting variables.
            $this->title = $this->settings['title'];
            $this->description = $this->settings['description'];
            $this->merchant_id = $this->settings['merchant_id'];
            $this->clearcart = $this->settings['clearcart'];
            $this->secret_key = $this->settings['secret_key'];
            $this->paymentverification = $this->settings['paymentverification'];
            $this->custom_error = $this->settings['custom_error'];

            self::$log_enabled = $this->debug;

            add_action('woocommerce_receipt_senangpay', array(
                &$this,
                'receipt_page'
            ));
            //save setting configuration
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'process_admin_options'
            ));
            // Payment listener/API hook
            add_action('woocommerce_api_wc_senangpay_gateway', array(
                $this,
                'check_ipn_response'
            ));
            // Checking if merchant_id is not empty.
            $this->merchant_id == '' ? add_action('admin_notices', array(
                                &$this,
                                'merchant_id_missing_message'
                            )) : '';
            // Checking if secret_key is not empty.
            $this->secret_key == '' ? add_action('admin_notices', array(
                                &$this,
                                'secret_key_missing_message'
                            )) : '';
        }

        /**
         * Checking if this gateway is enabled and available in the user's country.
         *
         * @return bool
         */
        public function is_valid_for_use() {
            if (!in_array(get_woocommerce_currency(), array(
                        'MYR'
                    ))) {
                return false;
            }
            return true;
        }

        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis.
         *
         */
        public function admin_options() {
            ?>
            <h3><?php
                _e('senangPay Payment Gateway', 'wcsenangpay');
                ?></h3>
            <p><?php
                _e('senangPay Payment Gateway works by sending the user to senangPay for payment. ', 'wcsenangpay');
                ?></p>
            <p><?php
                _e('To immediately reduce stock on add to cart, we strongly recommend you to use this plugin. ', 'wcsenangpay');
                ?><a href="http://bit.ly/1UDOQKi" target="_blank"><?php
                    _e('WooCommerce Cart Stock Reducer', 'wcsenangpay');
                    ?></a></p>
            <table class="form-table">
                <?php
                $this->generate_settings_html();
                ?>
            </table><!--/.form-table-->
            <?php
        }

        /**
         * Gateway Settings Form Fields.
         * 
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'wcsenangpay'),
                    'type' => 'checkbox',
                    'label' => __('Enable senangPay', 'wcsenangpay'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title', 'wcsenangpay'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'wcsenangpay'),
                    'default' => __('senangPay Payment Gateway', 'wcsenangpay')
                ),
                'description' => array(
                    'title' => __('Description', 'wcsenangpay'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'wcsenangpay'),
                    'default' => __('Pay with <strong>Credit/Debit Card & FPX</strong>. <p style="background-color:#fff;padding:1rem;text-align:center;border-radius:3px;"><img src="https://app.senangpay.my/public/img/logo-senangpay-wc.png" width="100%" style="max-width:247px;"></p>', 'wcsenangpay')
                ),
                'merchant_id' => array(
                    'title' => __('Merchant ID', 'wcsenangpay'),
                    'type' => 'text',
                    'placeholder' => 'Example : 123456789123456',
                    'description' => __('Please enter your senangPay Merchant ID.', 'wcsenangpay') . ' ' . sprintf(__('Get Your Merchant ID: %ssenangPay%s.', 'wcsenangpay'), '<a href="https://app.senangpay.my/setting/profile" target="_blank">', '</a>'),
                    'default' => ''
                ),
                'secret_key' => array(
                    'title' => __('Secret Key', 'wcsenangpay'),
                    'type' => 'text',
                    'placeholder' => 'Example : 1234-567',
                    'description' => __('Please enter your senangPay Secret Key. ', 'wcsenangpay') . ' ' . sprintf(__('Get Your Secret Key. %ssenangPay%s.', 'wcsenangpay'), '<a href="https://app.senangpay.my/setting/profile" target="_blank">', '</a>'),
                    'default' => ''
                ),
                'paymentverification' => array(
                    'title' => __('Verification Type', 'wcsenangpay'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Leave it as Both unless you are having problem, then change to Callback/Return.', 'wcsenangpay'),
                    'default' => 'Callback',
                    'desc_tip' => true,
                    'options' => array(
                        'Both' => __('Both', 'wcsenangpay'),
                        'Callback' => __('Callback', 'wcsenangpay'),
                        'Return' => __('Return', 'wcsenangpay')
                    )
                ),
                'clearcart' => array(
                    'title' => __('Clear Cart Session', 'wcsenangpay'),
                    'type' => 'checkbox',
                    'label' => __('Tick to clear cart session on checkout', 'wcsenangpay'),
                    'default' => 'no'
                ),
                'debug' => array(
                    'title' => __('Debug Log', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable logging', 'woocommerce'),
                    'default' => 'no',
                    'description' => sprintf(__('Log senangPay events, such as IPN requests, inside <code>%s</code>', 'woocommerce'), wc_get_log_file_path('senangpay'))
                ),
                'custom_error' => array(
                    'title' => __('Error Message', 'wcsenangpay'),
                    'type' => 'text',
                    'placeholder' => 'Example : You have cancelled the payment. Please make a payment!',
                    'description' => __('Error message that will appear when customer cancel the payment.', 'wcsenangpay'),
                    'default' => 'You have cancelled the payment. Please make a payment!'
                )
            );
        }

        /**
         * Generate the form.
         *
         * @param mixed $order_id
         * @return string
         */
        public function generate_form($order_id) {
            $order = new WC_Order($order_id);
            //----------------------------------------------------------------//
            if (sizeof($order->get_items()) > 0)
                foreach ($order->get_items() as $item)
                    if ($item['qty'])
                        $item_names[] = $item['name'] . ' x ' . $item['qty'];
            $desc = sprintf(__('Order %s', 'woocommerce'), $order->get_order_number()) . " - " . implode(', ', $item_names);

            // SenangPay Secret Key
            $secretkey = $this->secret_key;
            $amount = number_format($order->get_total(), 2);

            // Calculate Hash
            $hash_value = md5($secretkey . $desc . $amount . $order_id);

            $senangpay_args = array(
                'detail' => $desc,
                'amount' => $amount,
                'order_id' => $order_id,
                'hash' => $hash_value,
                'name' => $order->get_billing_first_name() . " " . $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'country' => $order->get_billing_country(),
                'cur' => get_woocommerce_currency(),
            );

            $senangpay_args_array = array();
            foreach ($senangpay_args as $key => $value) {
                $senangpay_args_array[] = "<input type='hidden' name='" . $key . "' value='" . $value . "' />";
            }

            $pay_url = 'https://app.senangpay.my/payment/' . $this->merchant_id;

            // Log
            self::log('Customer Name: ' . $senangpay_args['name'] . ' with email: ' . $order->get_billing_email() . ' has go to senangPay for payment, #' . $order_id);

            $ready = "<form action='" . $pay_url . "/' method='post' id='senangpay_payment_form' name='senangpay_payment_form'>"
                    . implode('', $senangpay_args_array)
                    . "<input type='submit' class='button-alt' id='submit_senangpay_payment_form' value='" . __('Pay via senangPay', 'woothemes') . "' /> "
                    . "<a class='button cancel' href='" . $order->get_cancel_order_url() . "'>" . __('Cancel order &amp; restore cart', 'woothemes') . "</a>"
                    . "<script>document.senangpay_payment_form.submit();</script>"
                    . "</form>";

            return $ready;
        }

        /**
         * Logging method.
         * @param string $message
         */
        public static function log($message) {
            if (self::$log_enabled) {
                if (empty(self::$log)) {
                    self::$log = new WC_Logger();
                }
                self::$log->add('senangpay', $message);
            }
        }

        /**
         * Return the gateway's title.
         *
         * @return string
         */
        public function get_title() {
            return apply_filters('woocommerce_gateway_title', $this->title, $this->id);
        }

        /**
         * Get gateway icon.
         * @return string
         */
        public function get_icon() {
            $icon = $this->icon ? '<img src="' . WC_HTTPS::force_https_url($this->icon) . '" alt="' . esc_attr($this->get_title()) . '" />' : '';

            return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
        }

        /**
         * Order error button.
         *
         * @param  object $order Order data.
         * @return string Error message and cancel button.
         */
        protected function senangpay_order_error($order) {
            $html = '<p>' . __('An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'wcsenangpay') . '</p>';
            $html .= '<a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Click to try again', 'wcsenangpay') . '</a>';
            return $html;
        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment($order_id) {
            $order = new WC_Order($order_id);

            if ($this->clearcart === 'yes')
                WC()->cart->empty_cart();

            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }

        /**
         * Output for the order received page.
         * WooCommerce send to this method and CURL it!
         * 
         */
        public function receipt_page($order) {
            echo $this->generate_form($order);
        }

        /**
         * Check for senangPay Response
         *
         * @access public
         * @return void
         */
        function check_ipn_response() {
            @ob_clean();
            global $woocommerce;
            require_once(__DIR__ . '/includes/senangPay.php');
            $senangPay = new senangPay($this->merchant_id, $this->secret_key);

            // Exit if not true
            $senangPay->verify_hash();

            // Get Order ID
            $order_id = $senangPay->array['order_id'];

            // Create order object
            $order = new WC_Order($order_id);

            // Identify this is Callback or Return
            if (isset($_GET['status_id']))
                $signal = 'Return';
            else if (isset($_POST['status_id']))
                $signal = 'Callback';

            // Log return type
            self::log('This response is: ' . $signal);

            // Check if we want to save to db or not
            if ($senangPay->verify_payment()) {
                $this->save_payment($order, $senangPay, $signal);
                $redirectpath = $order->get_checkout_order_received_url();
            } else {
                wc_add_notice(__('ERROR: ', 'woothemes') . $this->custom_error, 'error');
                $redirectpath = $order->get_cancel_order_url();
            }

            // Handle Return or Callback
            // If return, make redirect
            // if callback, show 'OK'

            if ($signal === 'Return')
                wp_redirect($redirectpath);
            else if ($signal === 'Callback')
                echo 'OK';

            exit;
        }

        /**
         * Save payment status to DB for successful return/callback
         */
        private function save_payment($order, $senangPay, $signal) {
            $referer = "<br>Transaction ID: " . $senangPay->array['transaction_id'];
            $referer .= "<br>Order ID: " . $senangPay->array['order_id'];
            $referer .= "<br>Amount: " . $senangPay->array['amount'];
            $referer .= "<br>Hash: " . $senangPay->array['hashvalue'];
            $referer .= "<br>Type: " . $signal;
            if ($order->get_status() == 'pending' || $order->get_status() == 'failed' || $order->get_status() == 'cancelled') {
                if ($senangPay->array['status_id'] == 1 || $senangPay->array['status_id'] == '1') {
                    if ($this->paymentverification == $signal || $this->paymentverification == 'Both') {
                        $order->add_order_note('Payment Status: SUCCESSFUL' . $referer);
                        $order->payment_complete();
                    }
                }
            }
            return $this;
        }

        /**
         * Adds error message when not configured the app_key.
         * 
         */
        public function merchant_id_missing_message() {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf(__('<strong>Gateway Disabled</strong> You should inform your Merchant ID in senangPay. %sClick here to configure!%s', 'wcsenangpay'), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=senangpay">', '</a>') . '</p>';
            $message .= '</div>';
            echo $message;
        }

        /**
         * Adds error message when not configured the app_secret.
         * 
         */
        public function secret_key_missing_message() {
            $message = '<div class="error">';
            $message .= '<p>' . sprintf(__('<strong>Gateway Disabled</strong> You should inform your Secret Key in senangPay. %sClick here to configure!%s', 'wcsenangpay'), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=senangpay">', '</a>') . '</p>';
            $message .= '</div>';
            echo $message;
        }

    }

}
