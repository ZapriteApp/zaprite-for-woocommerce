<?php
namespace ZapritePlugin;


class Utils {
    public static function convert_to_satoshis($amount, $currency) {
        if(strtolower($currency) !== 'btc') {
            error_log($amount . " " . $currency);
            $c    = new CurlWrapper();
            $resp = $c->get('https://blockchain.info/tobtc', array(
                'currency' => $currency,
                'value'    => $amount
            ), array());

            if ($resp['status'] != 200) {
                throw new \Exception('Blockchain.info request for currency conversion failed. Got status ' . $resp['status']);
            }

            return (int) round($resp['response'] * 100000000);
        }
        else {
            return intval($amount * 100000000);
        }
    }
    public static function convert_to_smallest_unit($amount) {
        // Get the number of decimals for pricing from WooCommerce settings
        $decimals = get_option('woocommerce_price_num_decimals');
        // Convert the total to the smallest unit
        return $amount * pow(10, $decimals);
    }
    public static function convert_zaprite_order_status_to_woo_status($status) {
        $zapriteStatus = strtolower($status);
        $wooStatus = "";

        // convert zaprite to woo status
        switch ($zapriteStatus) {
            case 'pending':
                $wooStatus = "pending"; // do nothing
                break;
            case 'processing':
                $wooStatus = "wc-btc-pending";
                break;
            case 'paid':
                $wooStatus = "processing"; // its paid on zaprite but still processing to be shipped on woo
                break;
            case 'overpaid':
                $wooStatus = "wc-overpaid";
                break;
            case 'underpaid':
                $wooStatus = "wc-underpaid";
                break;
            case 'complete':
                $wooStatus = "processing"; // its completed on zaprite but still processing to be shipped on woo
                break;
            default:

        }
        return $wooStatus;
    }
}

class CurlWrapper {

    private function request($method, $url, $params, $headers, $data) {
        error_log("ZAPRITE: curl $url");
        $url = add_query_arg($params, $url);
        $r = wp_remote_request($url, array(
            'method' => $method,
            'headers' => $headers,
            'body' => $data // ? json_encode($data) : ''
        ));

        if (is_wp_error($r)) {
            error_log('WP_Error: '.$r->get_error_message());
            return array(
                'status' => 500,
                'response' => $r->get_error_message()
            );
        }

        return array(
            'status' => $r['response']['code'],
            'response' => json_decode($r['body'], true)
        );
    }

    public function get($url, $params, $headers) {
        return $this->request('GET', $url, $params, $headers, null);
    }

    public function post($url, $params, $data, $headers) {
        return $this->request('POST', $url, $params, $headers, $data);
    }
}
