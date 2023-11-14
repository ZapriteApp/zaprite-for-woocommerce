<?php

/*
Plugin Name: Zaprite - Bitcoin Onchain and Lightning Payment Gateway
Plugin URI: https://github.com/ZapriteApp/zaprite-for-woocommerce
Description: Accept Bitcoin from your Woo store both on-chain and with Lightning with Zaprite
Version: 1.0.0
Author: Zaprite
Author URI: https://zaprite.com
*/

add_action('plugins_loaded', 'zaprite_server_init');

define('ZAPRITE_ENV', 'prod'); // change this to 'prod' for production, or 'dev' for development
define('ZAPRITE_WOOCOMMERCE_VERSION', '1.0.0');
define('ZAPRITE_PATH', 'https://zaprite.com' ); // 'https://zaprite-v2-1mpth5l9h-zaprite.vercel.app'
define('ZAPRITE_DEV_PATH', 'http://localhost:3000');

require_once(__DIR__ . '/includes/init.php');

use ZapritePlugin\Utils;
use ZapritePlugin\API;

// This is the entry point of the plugin, where everything is registered/hooked up into WordPress.
function zaprite_server_init()
{

    if ( ! class_exists('WC_Payment_Gateway')) {
        return;
    };

    // Set the cURL timeout to 15 seconds. When requesting a lightning invoice
    // If using Tor, a short timeout can result in failures.
    add_filter('http_request_args', 'zaprite_server_http_request_args', 100, 1);
    function zaprite_server_http_request_args($r) //called on line 237
    {
        $r['timeout'] = 15;
        return $r;
    }

    add_action('http_api_curl', 'zaprite_server_http_api_curl', 100, 1);
    function zaprite_server_http_api_curl($handle)
    {
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($handle, CURLOPT_TIMEOUT, 15);
    }

    // Register the gateway, essentially a controller that handles all requests.
    function add_zaprite_server_gateway($methods)
    {
        $methods[] = 'WC_Gateway_Zaprite_Server';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'add_zaprite_server_gateway');

    function zaprite_server_add_payment_complete_callback($data)
    {
        error_log("ZAPRITE: webhook zaprite_server_add_payment_complete_callback");
        $order_id = $data["id"];
        $order    = wc_get_order($order_id);
        $keyToCheck = $data->get_param('key');
        error_log("ZAPRITE: keyToCheck $keyToCheck");
        $key = $order->get_order_key();
        error_log("ZAPRITE: correct key $key");

        if ($key == $keyToCheck) {
            $order->add_order_note('Payment is settled and has been credited to your Zaprite account. Purchased goods/services can be securely delivered to the customer.');
            $order->payment_complete();
            $order->save();
            error_log("PAID");
            echo(json_encode(array(
                'result'   => 'success',
                'redirect' => $order->get_checkout_order_received_url(),
                'paid'     => true
            )));
            if (empty($order_id)) {
                return null;
            }
        } else {
            return new WP_Error(
                'unauthorized_access',
                'Unauthorized access - keys do not match',
                array( 'status' => 401 )
            );
        }
    }

    add_action("rest_api_init", function () {
        error_log("ZAPRITE: rest_api_init zaprite");
        register_rest_route("zaprite_server/zaprite/v2", "/payment_complete/(?P<id>\d+)", array(
            "methods"  => "GET",
            "callback" => "zaprite_server_add_payment_complete_callback",
            "permission_callback" => "__return_true",
        ));
    });

    // Defined here, because it needs to be defined after WC_Payment_Gateway is already loaded.
    class WC_Gateway_Zaprite_Server extends WC_Payment_Gateway {
        public function __construct()
        {
            global $woocommerce;

            $this->id                 = 'zaprite';
            $this->icon = $this->get_option('payment_image');
            $this->has_fields         = false;
            $this->method_title       = 'Zaprite';
            $this->method_description = 'Take payments in Bitcoin Onchain and with Lightning using Zaprite.';

            $this->init_form_fields();
            $this->init_settings();

            $this->title       = $this->get_option('title');
            $this->description = $this->get_option('description');

            $url       = $this->get_option('zaprite_server_url');
            $api_key   = $this->get_option('zaprite_api_key');

            $this->api = new API($url, $api_key);

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'process_admin_options'
            ));
            add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'check_payment'));

            // This action allows us to set the order to complete even if in a local dev environment
            add_action('woocommerce_thankyou', array(
                $this,
                'check_payment'
            ));
        }

        /**
         * Render admin options/settings.
         */
        public function admin_options()
        {
            ?>
            <h3><?php _e('Zaprite', 'woothemes'); ?></h3>
            <p><?php _e('Accept Bitcoin instantly through the Zaprite extension. First enable the Woocommerce extension on Zaprite and choose your default wallet. Then copy the api key in the settings below.', 'woothemes'); ?></p>
            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table>
            <?php
        }

        /**
         * Generate config form fields, shown in admin->WooCommerce->Settings.
         */
        public function init_form_fields()
        {
            // echo("init_form_fields");
            $this->form_fields = array(
                'enabled'                             => array(
                    'title'       => __('Enable Zaprite payment', 'woocommerce'),
                    'label'       => __('Enable Bitcoin payments via Zaprite', 'woocommerce'),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no',
                ),
                'title'                               => array(
                    'title'       => __('Title', 'woocommerce'),
                    'type'        => 'text',
                    'description' => __('The payment method title which a customer sees at the checkout of your store.', 'woocommerce'),
                    'default'     => __('Pay with Bitcoin: on-chain or with Lightning', 'woocommerce'),
                ),
                'description'                         => array(
                    'title'       => __('Description', 'woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('The payment method description which a customer sees at the checkout of your store.', 'woocommerce'),
                    'default'     => __('Powered by Zaprite.'),
                ),
                'payment_image'                         => array(
                    'title'       => __('Image', 'woocommerce'),
                    'type'        => 'text',
                    'description' => __('The url of an image displayed by the payment method', 'woocommerce'),
                    'default'     => __('https://app.zaprite.com/_next/image?url=%2Ffavicon.png&w=48&q=75'),
                ),
                'zaprite_api_key'           => array(
                    'title'       => __('Api Key', 'woocommerce'),
                    'description' => __('Enter your Zaprite api key', 'woocommerce'),
                    'type'        => 'text',
                    'default'     => '',
                )
            );
        }


        /**
         * ? Output for thank you page.
         */
        public function thankyou()
        {
            error_log("thankyou called");
            if ($description = $this->get_description()) {
                echo esc_html(wpautop(wptexturize($description)));
            }
        }

        /**
         * Called from checkout page, when "Place order" hit, through AJAX.
         *
         * Call Zaprite API to create an invoice, and store the invoice in the order metadata.
         */
        public function process_payment($order_id)
        {
            $zaprite_url =  (ZAPRITE_ENV == 'dev') ? ZAPRITE_DEV_PATH : ZAPRITE_PATH;

            error_log("ZAPRITE: process_payment");
            $order = wc_get_order($order_id);

            // This will be stored in the invoice (ie. can be used to match orders in Zaprite)
            $memo = get_bloginfo('name') . " Order #" . $order->get_id() . " Total=" . $order->get_total() . get_woocommerce_currency();

            // $amount = Utils::convert_to_satoshis($order->get_total(), get_woocommerce_currency());
            $amount =$order->get_total();
            $currency = $order->get_currency();
            error_log("ZAPRITE: currency $amount $currency");

            $invoice_expiry_time = 1440; //$this->get_option('lnbits_satspay_expiry_time');
            // Call Zaprite server to create invoice
            $r = $this->api->createCharge($amount, $currency, $memo, $order_id, $invoice_expiry_time);

            if ($r['status'] === 200) {
                $resp = $r['response'];
                $status = $r['status'];
                error_log("ZAPRITE: process_payment status $status");

                // Access the orderId field
                $order_id =  $r['response']['id'];
                error_log("orderId => $order_id");

                // save zaprite metadata in woocommerce
                $order->add_meta_data('zaprite_order_id', $order_id, true);
                $order->add_meta_data('zaprite_order_link', "$zaprite_url/_domains/pay/order/$order_id", true);
                $order->save();
                $callback = base64_encode($order->get_checkout_order_received_url());
                $redirect_url = "$zaprite_url/_domains/pay/order/$order_id";

                return array(
                    "result"   => "success",
                    "redirect" => $redirect_url
                );
            } else {
                error_log("ZAPRITE: API failure. Status=" . $r['status']);
                return array(
                    "result"   => "failure",
                    "messages" => array("Failed to create Zaprite invoice.")
                );
            }
        }

        function set_order_status_pending( $order_id ) {
            $order = wc_get_order( $order_id );
            if ( $order instanceof WC_Order ) {
                $order->update_status( 'pending', 'Order status updated to pending by Zaprite.' );
                $order->save();
            }
        }

    }

}
