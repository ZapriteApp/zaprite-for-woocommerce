<?php
namespace ZapritePlugin;

use ZapritePlugin\CurlWrapper;

/**
 * For calling Zaprite API
 */
class API {

	protected $api_key;
	protected $api_url = ZAPRITE_API_URL;

	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	public function createCharge( $amount, $currency, $order_id ) {

		error_log( "ZAPRITE: URL $this->api_url" );

		$c     = new CurlWrapper();
		$order = wc_get_order( $order_id );
		error_log( "ZAPRITE: amount in smallest unit $amount $currency" );
		$headers = array(
			'Content-Type' => 'application/json',
		);

		$completelink = $order->get_checkout_order_received_url();
		$key          = $order->get_order_key();
		$site_url     = site_url();

		error_log( "Zapite: Complete Link $completelink" );

		$orderUpdateCallback = "$site_url/wp-json/zaprite_server/zaprite/v1/update_status/$order_id/?key=$key";
		$data                = array(
			'apiKey'              => $this->api_key,
			'amount'              => $amount,
			'currency'            => $currency,
			'orderUpdateCallback' => $orderUpdateCallback,
			'redirectUrl'         => $completelink,
			'externalOrderId'     => "$order_id",
			'externalUniqId'      => $key,
		);
		$query_params = array(
			'wooPluginVersion'    => ZAPRITE_WOOCOMMERCE_VERSION,
		);
		$response            = $c->post( "$this->api_url/api/public/woo/create-order", $query_params, json_encode( $data ), $headers );
		error_log( 'Send invoice status ===>' . $response['status'] );
		return $response;
	}

	public function checkCharge( $order_id ) {
		error_log( 'ZAPRITE: checkCharge' );
		$c              = new CurlWrapper();
		$order          = wc_get_order( $order_id );
		$headers        = array(
			'Content-Type' => 'application/json',
		);
		$zapriteOrderId = $order->get_meta( 'zaprite_order_id', $order_id, true );
		error_log( "ZAPRITE: checkCharge zapriteOrderId = $zapriteOrderId" );
		$data     = array(
			'apiKey'  => $this->api_key,
			'orderId' => "$zapriteOrderId",
		);
		$query_params = array(
			'wooPluginVersion'    => ZAPRITE_WOOCOMMERCE_VERSION,
		);
		$apiKey   = $this->api_key;
		$response = $c->post( "$this->api_url/api/public/woo/check-order", $query_params, json_encode( $data ), $headers );
		error_log( 'Check order status ===>' . $response['status'] );
		return $response;
	}
}
