<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use ZapritePlugin\Utils;

/**
 * Payments Blocks integration
 */
final class WC_Gateway_Zaprite_Blocks_Support extends AbstractPaymentMethodType {


	/**
	 * The gateway instance.
	 *
	 * @var WC_Gateway_Zaprite
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'zaprite';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_zaprite_settings', array() );
		$this->gateway  = new WC_Gateway_Zaprite_Server();
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = '/js/frontend/blocks.js';
		$script_asset_path = WC_PAYMENT_GATEWAY_ZAPRITE_ASSETS . '/js/frontend/blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? include $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => '1.2.0',
			);
		$script_url        = WC_PAYMENT_GATEWAY_ZAPRITE_ASSETS . $script_path;

		wp_register_script(
			'wc-zaprite-payments-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		// if ( function_exists( 'wp_set_script_translations' ) ) {
		// wp_set_script_translations( 'wc-zaprite-payments-blocks', 'zaprite-payment-gateway', WC_Dummy_Payments::plugin_abspath() . 'languages/' );
		// }

		return array( 'wc-zaprite-payments-blocks' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => $this->gateway->get_option( 'title' ),
			'description' => 'Powered by Zaprite', // TODO hardcode for now because it wont read from disabled setting
			'showImage'   => $this->gateway->get_option( 'payment_image' ),
			'image'       => Utils::get_icon_image_url(),
		);
	}
}
