<?php
namespace ZapritePlugin;

use Money\Money;
use Money\Currencies\ISOCurrencies;
use Money\Parser\DecimalMoneyParser;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Currency;

class Utils {
	public static function convert_to_smallest_unit( $amount, $currencyCode ) {
		$currencies  = new ISOCurrencies();
		$moneyParser = new DecimalMoneyParser( $currencies );
		$money       = $moneyParser->parse( $amount, new Currency( $currencyCode ) );
		return $money->getAmount();
	}
	// Woo uses major units
	public static function convert_to_major_unit( $amount, $currencyCode ) {
		$currencies = new ISOCurrencies();
		$currency   = new Currency( $currencyCode );
		// Check if the currency has subunits
		$subunit = $currencies->subunitFor( $currency );
		if ( $subunit > 0 ) {
			// For currencies with subunits, convert the amount to a string in major units format
			$amountInMajorUnits = bcdiv( (string) $amount, (string) pow( 10, $subunit ), $subunit );
		} else {
			// For zero decimal currencies, the major unit is equivalent to the minor unit
			$amountInMajorUnits = (string) $amount;
		}
		$moneyParser    = new DecimalMoneyParser( $currencies );
		$money          = $moneyParser->parse( $amountInMajorUnits, $currency );
		$moneyFormatter = new DecimalMoneyFormatter( $currencies );
		$majorAmount    = $moneyFormatter->format( $money );
		error_log( "MajorAmount: $majorAmount" );
		return $majorAmount;
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
