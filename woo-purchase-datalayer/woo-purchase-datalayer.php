<?php
/**
 * Plugin Name: Woo Purchase DataLayer
 * Description: Pushes a GA4 "purchase" event to the GTM dataLayer on the WooCommerce order-received page.
 * Version: 0.1.0
 * Requires Plugins: woocommerce
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'woocommerce_thankyou', 'wpdl_push_purchase_event', 20 );

/**
 * Output the purchase event for GTM.
 *
 * Known issue: the event fires again every time the customer reloads or
 * revisits the thank-you page, so GA4 records duplicate purchases.
 */
function wpdl_push_purchase_event( $order_id ) {
	if ( ! $order_id ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
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
}
