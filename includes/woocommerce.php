<?php


add_filter('woocommerce_new_customer_data', function ($data) {
	$data['user_login'] = $data['user_email'];
	return $data;
});

/**
 * Change number or products per row to 3
 */
add_filter('loop_shop_columns', 'loop_columns');
if (!function_exists('loop_columns')) {
	function loop_columns()
	{
		return 3; // 3 products per row
	}
}
add_filter('woocommerce_sale_flash', 'wc_custom_replace_sale_text');
function wc_custom_replace_sale_text($html)
{
	return str_replace(__('Sale!', 'woocommerce'), __('Discount', 'woocommerce'), $html);
}
add_action('wpo_wcpdf_custom_styles', 'wpo_wcpdf_custom_styles', 10, 2);
function wpo_wcpdf_custom_styles($document_type, $document)
{
?>
	.sku { display: none; }
	tr.multiple td { font-weight:bold; }
<?php
}

//turn off product pagination
add_filter('theme_mod_storefront_product_pagination', '__return_false');


add_filter('woocommerce_variation_is_visible', 'hide_specific_product_variation', 10, 4);
function hide_specific_product_variation($is_visible, $variation_id, $variable_product, $variation)
{
	// For unlogged user, don't hide anything
	if (!is_user_logged_in()) {
		return $is_visible;
	}

	if (current_user_can('wholesale_retail') && $variation->attributes['pa_size'] == 'large') {
		return false;
	}
	return $is_visible;
}