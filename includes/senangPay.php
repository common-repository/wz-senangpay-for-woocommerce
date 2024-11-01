<?php
if (!class_exists('senangPay')) {

    class senangPay
    {

        var $array, $merchantid, $secretkey;

        function __construct($merchantid, $secretkey)
        {

            // Collect all data
            $this->array = array(
                'order_id' => filter_var($_REQUEST['order_id'], FILTER_SANITIZE_STRING),
                'status_id' => filter_var($_REQUEST['status_id'], FILTER_SANITIZE_STRING),
                'transaction_id' => filter_var($_REQUEST['transaction_id'], FILTER_SANITIZE_STRING),
                'amount' => filter_var($_REQUEST['amount'], FILTER_SANITIZE_STRING),
                'hashvalue' => $_REQUEST['hash'],
            );

            // Log Response
            WC_senangPay_Gateway::log('Response received.' . print_r($this->array, true));

            $this->merchantid = $merchantid;
            $this->secretkey = $secretkey;

            //Note: transaction_id == transaction_reference
            // Format it to 2 decimal place
            $this->array['amount'] = number_format($this->array['amount'], 2);
        }

        function verify_hash()
        {
            $hash_value = md5($this->secretkey . '&status_id=' . $_REQUEST['status_id'] . '&order_id=' . $_REQUEST['order_id'] . '&transaction_id=' . $_REQUEST['transaction_id'] . '&amount=' . $_REQUEST['amount'] . '&hash=[HASH]');
            if ($hash_value != $this->array['hashvalue']) {
                // Log response validation failed
                WC_senangPay_Gateway::log('Hash Validation FAILED for Order ID: #' . $this->array['order_id']);
                die('Invalid Hash');
            }
            // Log response validation successful
            WC_senangPay_Gateway::log('Hash Validation PASSED for Order ID: #' . $this->array['order_id']);
        }

        private function url($array)
        {

            $url = 'https://app.senangpay.my/apiv1/query_order_status';
            $url .= '?merchant_id=' . $array['merchant_id'];
            $url .= '&order_id=' . $array['order_id'];
            $url .= '&hash=' . $array['hash'];
            return $url;
        }

        function verify_payment()
        {
            $hash = md5($this->merchantid . $this->secretkey . $this->array['order_id']);
            $array = array(
                'merchant_id' => $this->merchantid,
                'order_id' => $this->array['order_id'],
                'hash' => $hash,
            );

            $args = array(
                'timeout' => 20,
            );

            $response = wp_safe_remote_get($this->url($array), $args);

            // Check if response is available or not
            if (is_array($response))
                $body = $response['body'];
            else
                die('Cannot verify payment');

            // Check if has response or not
            if (!empty($body))
                $body = json_decode($body, true);

            // Check if the response is array or not
            if (is_array($body['data'])) {
                $data = $this->findReference($body['data']);
                $status = $data['payment_info']['status'];
            } else
                die('Invalid Request');

            if ($status != 'paid')
                return false;
            elseif ($status == 'paid')
                return true;
        }

        private function findReference($data)
        {
            foreach ($data as $key => $value) {
                if ($value['payment_info']['transaction_reference'] == $this->array['transaction_id']) {
                    //echo $value['payment_info']['transaction_reference'] . '<br>';
                    //echo $this->array['transaction_id'] . '<br>';
                    return $value;
                }
            }
            die('No valid transaction id');
        }
    }

}
