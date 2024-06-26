<?php

use Yoast\WP\Duplicate_Post\UI\Column;

function my_theme_enqueue_styles()
{

	$parent_style = 'storefront-style';

	wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css');
	wp_enqueue_style(
		'child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array($parent_style),
		wp_get_theme()->get('Version')
	);
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_styles');

remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);

function storefront_primary_navigation()
{
?>
	<nav id="site-navigation" class="main-navigation" role="navigation" aria-label="<?php esc_html_e('Primary Navigation', 'storefront'); ?>">
		<?php
		wp_nav_menu(
			array(
				'theme_location'	=> 'primary',
				'container_class'	=> 'primary-navigation',
			)
		);

		wp_nav_menu(
			array(
				'theme_location'	=> 'handheld',
				'container_class'	=> 'handheld-navigation',
			)
		);
		?>
	</nav><!-- #site-navigation -->
<?php
}

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
