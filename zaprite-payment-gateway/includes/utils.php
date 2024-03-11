<?php
namespace ZapritePlugin;

class Utils {
	public static function get_currency_smallest_unit_factor( $currency ) {
		// source: https://stripe.com/docs/currencies
		if ( in_array( $currency, array( 'CLP', 'JPY', 'KRW', 'UGX', 'VND' ) ) ) {
			return 1;
		}
		if ( in_array( $currency, array( 'BHD', 'JOD', 'KWD', 'OMR', 'TND' ) ) ) {
			return 1000;
		}
		if ( in_array( $currency, array( 'BTC', 'LBTC' ) ) ) {
			return 100_000_000;
		}
		return 100;
	}
	public static function to_smallest_unit( $amount, $currency ) {
		return round( $amount * self::get_currency_smallest_unit_factor( $currency ) );
	}
	public static function from_smallest_unit( $amount, $currency ) {
		return $amount / self::get_currency_smallest_unit_factor( $currency );
	}
	// convert zaprite to woo status
	public static function convert_zaprite_order_status_to_woo_status( $zapriteStatus ) {
		switch ( $zapriteStatus ) {
			case 'PENDING':
				return 'pending'; // do nothing
			case 'PROCESSING':
				return 'wc-btc-pending';
			case 'COMPLETE':
			case 'PAID':
				return 'processing'; // its paid or complete on zaprite but still processing to be shipped on woo
			case 'OVERPAID':
				return 'wc-overpaid';
			case 'UNDERPAID':
				return 'wc-underpaid';
			default:
				// maybe it would be best to throw an exception here
				return '';
		}
	}

	public static function get_icon_image_url() {
		$images_url = WC_PAYMENT_GATEWAY_ZAPRITE_ASSETS . '/images/';
		$icon_file  = 'zaprite-icon@2x.png';
		$icon_style = 'style="max-height: 44px !important;max-width: none !important;"';
		$icon_url   = $images_url . $icon_file;
		return $icon_url;
	}

	public static function get_icon_image_html() {
		$icon_url   = self::get_icon_image_url();
		$icon_style = 'style="max-height: 44px !important;max-width: none !important;"';
		$icon_html  = '<img src="' . $icon_url . '" alt="Zaprite logo" ' . $icon_style . ' />';
		return $icon_html;
	}

	/**
	 * Check if a WooCommerce order contains only virtual and downloadable products.
	 *
	 * @param int $order_id The ID of the WooCommerce order.
	 * @return bool True if all products in the order are virtual and downloadable, false otherwise.
	 */
	public static function is_order_virtual_digital( $order_id ) {
		$order = wc_get_order( $order_id );
		// Check if the order is valid
		if ( ! $order ) {
			return false;
		}
		foreach ( $order->get_items() as $item_id => $item ) {
			$product = $item->get_product();
			// Check if the product is not virtual or not downloadable
			if ( ! $product->is_virtual() || ! $product->is_downloadable() ) {
					return false;
			}
		}
		return true;
	}
}
