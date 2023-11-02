<?php
namespace ZapritePlugin;

/**
 * For calling Zaprite API
 */
class API {

    protected $url;
    protected $api_key;
    protected $wallet_id = "1234253";
    protected $watch_only_wallet_id = "1234";

    public function __construct($url, $api_key, $wallet_id, $watch_only_wallet_id) {
        $this->url = rtrim($url,"/");
        $this->api_key = $api_key;
        $this->wallet_id = $wallet_id;
        $this->watch_only_wallet_id = $watch_only_wallet_id;
    }

    public function createCharge($amount, $memo, $order_id, $invoice_expiry_time = 1440) {

        // TODO need an api endpoint to look up the following data (currently its hardcoded)
        //  - next order id number
        //  - org id
        //  - customer id
        //  - payment methods

        $c = new CurlWrapper();
        $order = wc_get_order($order_id);
        $randomNumber = mt_rand(10000, 99999); // Get random number for order id
        $amount = $amount * 100;
        error_log("ZAPRITE: amount in cents $amount");
        $lineItemText = "WooCommerce order number $order_id";
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

        // 1. create invoice
        $response = $c->post('http://host.docker.internal:3000/api/trpc/invoice.upsert?batch=1', array(),  $data , $headers);
        error_log("Create invoice status ===>" . $response['status']);

        // 2. send invoice
        $order_id =  $response ['response']['0']['result']['data']['json']['orderId'];
        $org_id = "clofxadld000333ngnzk0b3zm";
        $data_send = '{"0":{"json":{"orderId":"' . $order_id . '","orgId":"' . "clofxadld000333ngnzk0b3zm" . '"}}}';
        $response2 = $c->post('http://host.docker.internal:3000/api/trpc/invoice.send?batch=1', array(),  $data_send , $headers);
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
