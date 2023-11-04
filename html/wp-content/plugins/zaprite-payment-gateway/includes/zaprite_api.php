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

    public function createCharge($amount, $memo, $order_id, $invoice_expiry_time = 1440) {

        error_log("ZAPRITE: URL $this->zaprite_url");

        // TODO need an api endpoint to look up the following data (currently its hardcoded)
        //  - next order id number (increment by 1?)
        //  - org id (can probably lookup vias api-key)
        //  - customer id (do we even need a customer if this is handled in woocommerce?)
        //  - payment methods

        $c = new CurlWrapper();
        $order = wc_get_order($order_id);
        $randomNumber = mt_rand(10000, 99999); // Get random number for order id
        $amount = $amount * 100;
        error_log("ZAPRITE: amount in cents $amount");
        $lineItemText = "WooCommerce order number $order_id"; // look at order label in zaprite
        $data = '{"0":{"json":{"daysToPay":30,"date":"2023-11-01","number":"'
            . "$randomNumber"
            . '","hideOrgAddress":false,"hideOrgEmail":false,"hideOrgName":false,"orgId":"clofxadld000333ngnzk0b3zm","customerId":"clofya4nz000833ngkd3dji4w","footerNote":null,"lineItems":[{"id":"tmp::'
            . "$randomNumber"
            . '","name":"'. "$lineItemText"
            . '","quantityHundredths":100,"unitPrice":"' . "$amount" . '"}],"order":{"currency":"USD","customCheckout":{"id":null,"label":"","paymentMethods":[{"pluginSlug":"fakePayment","position":1}]}}},"meta":{"values":{"lineItems.0.unitPrice":["bigint"],"order.customCheckout.id":["undefined"]}}}}';
        $cookie = $this->api_key;
        error_log("ZAPRITE: API KEY $cookie ");
        $headers = array(
            'Cookie' => $cookie,
            'Content-Type' => 'application/json'
        );

        $completelink = $order->get_checkout_order_received_url();
        error_log("Zapite: Complete Link $completelink");

        // 1. create invoice (via url params instead of trpc quote)
        $response = $c->post("$this->zaprite_url/api/trpc/invoice.upsert?batch=1", array(),  $data , $headers);
        error_log("Create invoice status ===>" . $response['status']);

        // 2. send invoice
        $order_id =  $response ['response']['0']['result']['data']['json']['orderId'];
        $org_id = "clofxadld000333ngnzk0b3zm";
        $data_send = '{"0":{"json":{"orderId":"' . $order_id . '","orgId":"' . "clofxadld000333ngnzk0b3zm" . '"}}}';
        $response2 = $c->post("$this->zaprite_url/api/trpc/invoice.send?batch=1", array(),  $data_send , $headers);
        error_log("Send invoice status ===>" . $response2['status'] );
        return $response;
    }

    public function checkChargePaid($payment_id) {
        error_log("ZAPRITE: checkChargePaid");
        $c = new CurlWrapper();
        $headers = array(
            // 'X-Api-Key' => $this->api_key,
            'Content-Type' => 'application/json'
        );
        return $c->get($this->url.'/satspay/api/v1/charge/'.$payment_id, array(), $headers);
    }
}
