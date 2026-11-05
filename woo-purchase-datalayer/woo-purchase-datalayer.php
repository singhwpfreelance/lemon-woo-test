<?php
/**
 * Plugin Name: Woo Purchase DataLayer
 * Description: Pushes a GA4 "purchase" event to the GTM dataLayer on the WooCommerce order-received page.
 * Version: 0.1.1
 * Requires Plugins: woocommerce
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPDL_PURCHASE_TRACKED_META', '_wpdl_purchase_event_sent' );

add_action( 'woocommerce_thankyou', 'wpdl_push_purchase_event', 20 );

/**
 * Output the purchase event for GTM.
 *
 * A per-order meta flag guarantees the event is emitted at most once per
 * order, even when the thank-you page is reloaded or revisited.
 */
function wpdl_push_purchase_event( $order_id ) {
	if ( ! $order_id ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	if ( $order->has_status( 'failed' ) ) {
		return;
	}

	if ( $order->get_meta( WPDL_PURCHASE_TRACKED_META ) ) {
		return;
	}

	$items = array();
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		$items[] = array(
			'item_id'   => $product ? $product->get_sku() : $item->get_product_id(),
			'item_name' => $item->get_name(),
			'price'     => (float) $order->get_item_total( $item ),
			'quantity'  => $item->get_quantity(),
		);
	}

	$event = array(
		'event'     => 'purchase',
		'ecommerce' => array(
			'transaction_id' => $order->get_order_number(),
			'value'          => (float) $order->get_total(),
			'tax'            => (float) $order->get_total_tax(),
			'shipping'       => (float) $order->get_shipping_total(),
			'currency'       => $order->get_currency(),
			'items'          => $items,
		),
	);
	?>
	<script>
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push({ ecommerce: null });
		window.dataLayer.push(<?php echo wp_json_encode( $event ); ?>);
	</script>
	<?php

	$order->update_meta_data( WPDL_PURCHASE_TRACKED_META, gmdate( 'c' ) );
	$order->save();
}
