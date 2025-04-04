<?php

/**
 * Adds VAT information to the shipping total in the order invoice.
 *
 * For the other modifications to the invoice, see template at 	woocommerce/pdf/vital_invoice/invoice.php
 *
 * This function modifies the order item totals to include VAT information
 * in the shipping total if there is shipping tax applied to the order.
 *
 * @param array $order_item_totals The array of order item totals.
 * @param WC_Order $order The order object.
 * @return array The modified array of order item totals.
 */
function vital_invoice_order_totals($order_item_totals, $order) {
	if ($order->get_shipping_tax() > 0) {
		$order_item_totals['shipping'] = array(
			'label' => $order_item_totals['shipping']['label'],
			'value' => $order_item_totals['shipping']['value'] . "<small> (includes " . wc_price( $order->get_shipping_tax(), array( 'currency' => $order->get_currency() ) ) . ' VAT)</small>',);
	}
	return $order_item_totals;
}
add_filter('wpo_wcpdf_raw_order_totals', 'vital_invoice_order_totals', 10, 2);

require_once('permalinks.php');