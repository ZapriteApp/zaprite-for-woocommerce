<?php
namespace ZapritePlugin;

/**
 * For calling Zaprite API
 */
class API {

    protected $url;
    protected $api_key;
    protected $zaprite_url = "http://host.docker.internal:3000";

    public function __construct($url, $api_key) {
        $this->url = rtrim($url,"/");
        $this->api_key = $api_key;
    }

    public function createCharge($amount, $currency, $memo, $order_id, $invoice_expiry_time = 1440) {

        error_log("ZAPRITE: URL $this->zaprite_url");

        $c = new CurlWrapper();
        $order = wc_get_order($order_id);
        $amount = $amount * 100;
        error_log("ZAPRITE: amount in cents $amount");
        $headers = array(
            'Content-Type' => 'application/json'
        );

        $completelink = $order->get_checkout_order_received_url();
        $key = $order->get_order_key();

        error_log("Zapite: Complete Link $completelink");

        $orderPaidCallback = "http://localhost:8000/wp-json/zaprite_server/zaprite/v2/payment_complete/$order_id/?key=$key";
        // $data_send =
        // '{"apiKey": "'. "$this->api_key"
        //     .'", "amount": ' . $amount
        //     . ', "currency": "USD", "orderPaidCallback": "' . " $orderPaidCallback" . '"'
        //     . ', "redirectUrl": "'. "$completelink" .'" '
        //     . ', "externalOrderId": "'. "$order_id" .'" '
        // . '}';
        $data = [
            "apiKey" => $this->api_key,
            "amount" => $amount,
            "currency" => $currency,
            "orderPaidCallback" => $orderPaidCallback,
            "redirectUrl" => $completelink,
            "externalOrderId" => "$order_id"
        ];
        $response = $c->post("$this->zaprite_url/api/public/woo/create-order", array(), json_encode($data), $headers);
        error_log("Send invoice status ===>" . $response['status'] );
        return $response;
    }

}
