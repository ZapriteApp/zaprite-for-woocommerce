<?php
namespace ZapritePlugin;

class Utils {
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

    public static function getIconImageUrl() {
        $images_url   = WC_PAYMENT_GATEWAY_ZAPRITE_ASSETS . '/images/';
        $icon_file   = 'zaprite-icon@2x.png';
        $icon_style  = 'style="max-height: 44px !important;max-width: none !important;"';
        $icon_url   = $images_url . $icon_file;
        return $icon_url;
    }

    public static function getIconImageHtml() {
        $icon_url = Utils::getIconImageUrl();
        $icon_style  = 'style="max-height: 44px !important;max-width: none !important;"';
        $icon_html  = '<img src="' . $icon_url . '" alt="Zaprite logo" ' . $icon_style . ' />';
        return $icon_html;
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
