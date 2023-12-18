<?php
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

/*
Plugin Name: Zaprite Payment Gateway for WooCommerce
Plugin URI: https://github.com/ZapriteApp/zaprite-for-woocommerce
Description: Accept bitcoin (on-chain and lightning) and fiat payments in one unified Zaprite Checkout.
Version: 1.0.0
Author: Zaprite
Author URI: https://zaprite.com
Text Domain: zaprite-for-woocommerce
*/

include_once(__DIR__ . '/includes/blocks-checkout.php');

add_action('plugins_loaded', 'zaprite_server_init');

define('ZAPRITE_APP_URL', getenv('ZAPRITE_APP_URL') ?: 'https://app.zaprite.com' );
define('ZAPRITE_API_URL', getenv('ZAPRITE_API_URL') ?: ZAPRITE_APP_URL );

define('ZAPRITE_WOOCOMMERCE_VERSION', '1.0.0');

define('WC_PAYMENT_GATEWAY_ZAPRITE_FILE', __FILE__);
define('WC_PAYMENT_GATEWAY_ZAPRITE_URL', plugins_url('', WC_PAYMENT_GATEWAY_ZAPRITE_FILE));
define('WC_PAYMENT_GATEWAY_ZAPRITE_ASSETS', WC_PAYMENT_GATEWAY_ZAPRITE_URL . '/assets');

include_once(__DIR__ . '/includes/hooks.php');
register_activation_hook(__FILE__, 'message_on_plugin_activate');
add_action('admin_notices', 'zaprite_admin_notices');

require_once(__DIR__ . '/includes/init.php');

use ZapritePlugin\Utils;
use ZapritePlugin\API;

// This is the entry point of the plugin, where everything is registered/hooked up into WordPress.
function zaprite_server_init()
{

    if ( ! class_exists('WC_Payment_Gateway')) {
        return;
    };

    // Register the gateway, essentially a controller that handles all requests.
    add_filter('woocommerce_payment_gateways', 'add_zaprite_server_gateway');
    function add_zaprite_server_gateway($methods)
    {
        $methods[] = 'WC_Gateway_Zaprite_Server';
        return $methods;
    }

    // Defined here, because it needs to be defined after WC_Payment_Gateway is already loaded.
    class WC_Gateway_Zaprite_Server extends WC_Payment_Gateway {
        public $api;

        public function __construct()
        {
            global $woocommerce;

            $this->id                 = 'zaprite';
            $this->has_fields         = false;
            $this->method_title       = 'Zaprite';
            $this->method_description = __('Bitcoin payments made easy. Accept on-chain, lightning and fiat payments in one unified Zaprite Checkout experience.', 'zaprite-for-woocommerce');

            $this->init_form_fields();
            $this->init_settings();

            $this->title       = $this->get_option('title');
            $this->description =  "Powered by Zaprite"; //$this->get_option('description'); // hard code for now, disbled in form setting does not work as you would think, see https://chat.openai.com/share/308744d4-a771-41e0-879e-306c112ec0c4

            $url       = $this->get_option('zaprite_server_url');
            $api_key   = $this->get_option('zaprite_api_key');

            $this->api = new API($api_key);

            if ($this->get_option('payment_image') == 'yes') {
                $this->icon = Utils::get_icon_image_url();
            }

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
            <h3><?php _e('Zaprite', 'zaprite-for-woocommerce'); ?></h3>
            <p><?php _e("
                <p>
                    Accept bitcoin (on-chain and lightning) and fiat payments instantly through your
                    hosted Zaprite Checkout. First, enable the WooCommerce extension on Zaprite Connections
                    page and configure your preferred Checkout. Finally, copy the API Key shown into the field below.
                </p>
                <p>
                    <a href='https://blog.zaprite.com/integrating-zaprite-with-woocommerce' target='_blank' rel='noreferrer'>
                        Setup Guide
                    </a>
                </p>
                ", 'zaprite-for-woocommerce'); ?></p>
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

            $this->form_fields = array(
                'enabled'                       => array(
                    'title'       => __('Enable Zaprite Payments', 'zaprite-for-woocommerce'),
                    'label'       => __('Enable payments via Zaprite Checkout.', 'zaprite-for-woocommerce'),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no',
                ),
                'title'                         => array(
                    'title'       => __('Payment Method Name', 'zaprite-for-woocommerce'),
                    'type'        => 'text',
                    'description' => __('The payment method title which a customer sees at the checkout of your store.', 'zaprite-for-woocommerce'),
                    'default'     => __('Checkout with Zaprite', 'zaprite-for-woocommerce'),
                ),
                'description'                   => array(
                    'title'       => __('Description', 'zaprite-for-woocommerce'),
                    'type'        => 'text',
                    'description' => __('The payment method description which a customer sees at the checkout of your store.', 'zaprite-for-woocommerce'),
                    'placeholder' => __('Powered by Zaprite', 'zaprite-for-woocommerce'),
                    'default'     => __('Powered by Zaprite', 'zaprite-for-woocommerce'),
                    'disabled'    => __(true, 'zaprite-for-woocommerce'),
                ),
                'payment_image'                 => array(
                    'title'       => __('Show checkout Image', 'zaprite-for-woocommerce'),
                    'label'       => __('Show Zaprite image on checkout', 'zaprite-for-woocommerce'),
                    'type'        => 'checkbox',
                    'description' => Utils::get_icon_image_html(),
                    'default'     =>  __('yes', 'zaprite-for-woocommerce'),
                ),
                'zaprite_api_key'               => array(
                    'title'       => __('Zaprite API Key', 'zaprite-for-woocommerce'),
                    'description' => sprintf(
                        __("Enter the Zaprite API Key from your <a href='%s' target='_blank' rel='noopener noreferrer'>WooCommerce plugin settings</a> page.", "zaprite-for-woocommerce"), 
                        htmlentities(ZAPRITE_APP_URL . '/org/default/connections')
                    ),
                    'type'        => 'text',
                    'default'     => '',
                ),
            );
        }


        /**
         * Output for thank you page.
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
            $zaprite_url = ZAPRITE_APP_URL;

            error_log("ZAPRITE: process_payment");
            $order = wc_get_order($order_id);
            $amount =$order->get_total();
            $currency = $order->get_currency();
            // error_log(print_r($order, true));
            $total_in_smallest_unit = Utils::convert_to_smallest_unit($amount);
            error_log("ZAPRITE: currency in smallest unit $total_in_smallest_unit $currency");

            // Call the Zaprite public api to create the invoice
            $r = $this->api->createCharge($total_in_smallest_unit, $currency, $order_id);

            if ($r['status'] === 200) {
                $resp = $r['response'];
                $status = $r['status'];
                error_log("ZAPRITE: process_payment status $status");

                // Access the orderId field
                $order_id =  $r['response']['id'];
                error_log("orderId => $order_id");

                // save zaprite metadata in woocommerce
                $order->add_meta_data('zaprite_order_id', $order_id, true);
                $order->add_meta_data('zaprite_order_link', "$zaprite_url/order/$order_id", true);
                $order->save();
                $callback = base64_encode($order->get_checkout_order_received_url());
                $checkout_page_id = get_option('woocommerce_checkout_page_id');
                $checkout_page_url = get_permalink($checkout_page_id);
                $backUrl = urlencode($checkout_page_url);
                $redirect_url = "$zaprite_url/_domains/pay/order/$order_id?backUrl=$backUrl";
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

        /**
         * Checks payment on Thank you against the zaprite api
         */
        public function check_payment()
        {
            try {
                $order_id = wc_get_order_id_by_order_key($_REQUEST['key']);
                $order        = wc_get_order($order_id);
                $r = $this->api->checkCharge($order_id);
                if ($r['status'] == 200) {
                    if ($r['response']['paid'] == true) {
                        $order->update_status('processing', 'Order status updated via API.', true);
                        $order->add_order_note('Payment completed (checkout).');
                        $order->payment_complete();
                        $order->save();
                        error_log("ZAPRITE: check_payment paid true!!!");
                    }
                } else {
                    // handle non 200 response status
                    die();
                }
            } catch(Exception $e) {
                die(get_class($e) . ': ' . $e->getMessage());
            }

        }

    }

    /**
     * Custom REST Endpoints
     */
    function register_custom_rest_endpoints () {
        /**
        * Update Status Custom REST Endpoint
        * ~/wp-json/zaprite_server/zaprite/v1/update_status/60/?key=wc_order_9VC4svMp4ttvN
        * This custom endpoint updates the order status from the zaprite app
        * NOTE: This needs to be registered outside of the WC_Payment_Gateway
        */
        function zaprite_server_add_update_status_callback($data)
        {
            error_log("ZAPRITE: webhook zaprite_server_add_update_status_callback");
            $order_id = $data["id"];
            $api_key = $data->get_header("apiKey");
            $api = new API($api_key);
            $orderStatusRes = $api->checkCharge($order_id);
            if (empty($order_id) || $orderStatusRes['status'] !== 200 || $api_key == null) {
                return new WP_Error(
                    'server_error',
                    'Missing Order Id or apiKey',
                    array( 'status' => 500 )
                );
            }

            $order = wc_get_order($order_id);
            // check keys
            $keyToCheck = $data->get_param('key');
            $key = $order->get_order_key(); // key from the order database

            if ($key !== $keyToCheck) {
                return new WP_Error(
                    'unauthorized_access',
                    'Unauthorized access - keys do not match',
                    array( 'status' => 401 )
                );
            }

            // check status
            $status = $orderStatusRes['response']['status'];
            error_log("ZAPRITE: order status update from zaprite api - $status");
            $wooStatus = Utils::convert_zaprite_order_status_to_woo_status($status);
            error_log("ZAPRITE: wooStatus - $wooStatus");

            switch ($wooStatus) {
                case 'processing':
                    $order->add_order_note('Payment is settled.');
                    // check if fiat premium was applied, if so, save to custom data in woo
                    $paidPremium = $orderStatusRes['response']['paidPremium'];
                    $paidPremiumCurrency = $orderStatusRes['response']['currency'];
                    error_log("ZAPRITE: paidPremium minor units $paidPremium $paidPremiumCurrency ");
                    if ($paidPremium) {
                        // add fee to order

                        // Edge Case
                        // return error if the currencies do not match...this could be an edge case where the
                        // woo store owner changes his currency before this order's payment is settled.
                        // TODO: in the future we could convert the currencies and do the math but I do
                        // not know how to easily do that in PHP
                        $wooDefaultCurrency = get_woocommerce_currency();
                        if ($wooDefaultCurrency !== $paidPremiumCurrency){
                            return new WP_REST_Response("Currencies do not match. Woo currency is $wooDefaultCurrency. Zaprite currency for premium paid is $paidPremiumCurrency", 400);
                        }
                        // convert to major units (woo requires major units)
                        $paidPremiumAmountMajorUnits = Utils::convert_to_major_unit($paidPremium);
                        error_log("ZAPRITE: paidPremium major units $paidPremiumAmountMajorUnits");
                        $item_fee = new WC_Order_Item_Fee();
                        $item_fee->set_name("Fiat Premium Fee");
                        $item_fee->set_amount($paidPremiumAmountMajorUnits);
                        $item_fee->set_tax_class( '' ); // or 'standard' if the fee is taxable
                        $item_fee->set_tax_status( 'none' ); // or 'taxable'
                        $item_fee->set_total($paidPremiumAmountMajorUnits); // The total amount of the fee
                        $order->add_item( $item_fee );
                        // Calculate totals and save the order
                        $order->calculate_totals();
                        $order->add_meta_data('zaprite_fiat_premium_extra_paid_amount', $paidPremiumAmountMajorUnits, true);
                        $order->save();
                    }
                    $order->payment_complete();
                    $order->save();
                    error_log("PAID");
                    echo(json_encode(array(
                        'result'   => 'success',
                        'redirect' => $order->get_checkout_order_received_url(),
                        'paid'     => true
                    )));
                    break;
                case 'wc-btc-pending':
                case 'wc-overpaid':
                case 'wc-underpaid':
                    // update status
                    $order->update_status($wooStatus, 'Order status updated via API.', true);
                    $order->save();
                    error_log("ZAPRITE: update status - $wooStatus");
                    break;
                case 'pending':
                    // do nothing
                    break;
                default:
                    return new WP_REST_Response('Invalid order status.', 400);
                    break;

            }
            return new WP_REST_Response('Order status updated.', 200);
        }
        add_action("rest_api_init", function () {
            error_log("ZAPRITE: rest_api_init zaprite");
            register_rest_route("zaprite_server/zaprite/v1", "/update_status/(?P<id>\d+)", array(
                "methods"  => "GET",
                "callback" => "zaprite_server_add_update_status_callback",
                "permission_callback" => "__return_true",
            ));
        });
    }

    register_custom_rest_endpoints();

    /**
    * Filters and Actions
    */
    function register_filters_and_actions() {

        // Settings Link
        function zaprite_add_settings_link($links) {
            $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=zaprite') . '">' . __('Settings', 'zaprite-for-woocommerce') . '</a>';
            array_unshift($links, $settings_link);
            return $links;
        }

        // Setup Guide Link
        function zaprite_add_meta_links($links, $file) {
            $plugin_base = plugin_basename(__FILE__);
            if ($file == $plugin_base) {
                // Add your custom links
                $new_links = array(
                    '<a href="https://blog.zaprite.com/integrating-zaprite-with-woocommerce">' . __('Setup Guide', 'zaprite-for-woocommerce') . '</a>',
                    // Add more links as needed
                );
                $links = array_merge($links, $new_links);
            }
            return $links;
        }

        // Set the cURL timeout to 15 seconds. When requesting a lightning invoice
        // If using Tor, a short timeout can result in failures.
        function zaprite_server_http_request_args($r) //called on line 237
        {
            $r['timeout'] = 15;
            return $r;
        }

        function zaprite_server_http_api_curl($handle)
        {
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($handle, CURLOPT_TIMEOUT, 15);
        }

        function add_custom_order_status() {
            register_post_status('wc-underpaid', array(
                'label'                     => 'Underpaid',
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop('Underpaid (%s)', 'Underpaid (%s)')
            ));
            register_post_status('wc-overpaid', array(
                'label'                     => 'Overpaid',
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop('Overpaid (%s)', 'Overpaid (%s)')
            ));
            register_post_status('wc-btc-pending', array(
                'label'                     => 'BTC Pending',
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop('BTC Pending (%s)', 'BTC Pending (%s)')
            ));
        }

        function add_custom_order_statuses($order_statuses) {
            $new_order_statuses = array();
            // add new order status after processing
            foreach ($order_statuses as $key => $status) {
                $new_order_statuses[$key] = $status;

                if ('wc-processing' === $key) {
                    $new_order_statuses['wc-underpaid'] = 'Underpaid';
                    $new_order_statuses['wc-overpaid'] = 'Overpaid';
                    $new_order_statuses['wc-btc-pending'] = 'BTC Pending';
                }
            }
            return $new_order_statuses;
        }

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'zaprite_add_settings_link');
        add_filter('plugin_row_meta', 'zaprite_add_meta_links', 10, 2);
        add_filter('http_request_args', 'zaprite_server_http_request_args', 100, 1);
        add_action('http_api_curl', 'zaprite_server_http_api_curl', 100, 1);
        add_action('init', 'add_custom_order_status');
        add_filter('wc_order_statuses', 'add_custom_order_statuses');

        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                error_log("ZAPRITE: PaymentMethodRegistry");
                $payment_method_registry->register( new WC_Gateway_Zaprite_Blocks_Support() );
            }
        );

    }

    register_filters_and_actions();

}
