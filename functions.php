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

function best_selling_vs_products_shortcode($atts)
{

	$num_products_to_show = 4;
	$show_heading = true;

	// Extract shortcode attributes
	$atts = shortcode_atts(
		array(
			'category' => 'uncategorized',
			'limit' => $num_products_to_show,
			'columns' => 4,
		),
		$atts,
		'best_selling_products'
	);

	try {
		$atts = array_merge(
			array(
				'limit'        => $atts['limit'],
				'columns'      => $atts['columns'],
				'category'     => $atts['category'],
				'cat_operator' => 'IN',
			),
			(array) $atts
		);

		$shortcode = new WC_Shortcode_Products($atts, 'best_selling_products');
		$output	= '';
		if ($show_heading) {
			$output .= '<h2>Most popular ' . ucwords(str_replace('-', ' ', $atts['category'])) . '</h2>';
		}

		$output .= $shortcode->get_content();
	} catch (exception $e) {
		$output = $e;
	}

	return $output;
}

add_shortcode('best_selling_vs_products', 'best_selling_vs_products_shortcode');


// ACF helper functions

function acf_enabled()
{
	return function_exists('get_field');
}

if (acf_enabled()) {
	function get_group_field(string $group, string $field, $post_id = 0)
	{
		$group_data = get_field($group, $post_id);
		if (is_array($group_data) && array_key_exists($field, $group_data)) {
			return $group_data[$field];
		}
		return null;
	}
	function get_group_field_int(string $group, string $field, $post_id = 0)
	{
		return intval(get_group_field($group, $field, $post_id)) ?: null;
	}
}

// Add the ACF field group for the sowing calendar
// require_once('includes/acf/fields/acf-seed-calendar.php');

// TODO: replace constant with a WC option
define('VS_LARGE_PACKET__NO_STOCK_AMOUNT', 5);

function product_is_large_packet($product)
{
	return $product->get_type() == 'variation' && $product->get_attributes()["pa_size"] == "large";
}

function get_large_pack_is_in_stock($status, $product)
{
	if (
		product_is_large_packet($product) &&
		$product->get_stock_quantity() > VS_LARGE_PACKET__NO_STOCK_AMOUNT
	) {
		return true;
	}
	return $status;
}
add_filter('woocommerce_product_is_in_stock', 'get_large_pack_is_in_stock', 10, 2);

function large_packet_no_stock_notification($default, $product_id)
{
	// If the product is a large packet then it should not send out of stock
	// notifications until it reaches the separate large packet threshold.

	$product = wc_get_product($product_id);
	if (!product_is_large_packet($product)) {
		return $default;
	}

	// The wc option that controls the regular threshold for sending out of stock notifications
	// get_option('woocommerce_notify_no_stock_amount', 0)

	$stock_quantity = $product->stock_quantity;
	$stock_status = $product->stock_status; // 'instock' or 'outofstock'
	if ($stock_status == 'outofstock' && $stock_quantity > VS_LARGE_PACKET__NO_STOCK_AMOUNT) {
		return false;
	}
	return $default;
}
add_filter('woocommerce_should_send_no_stock_notification', 'large_packet_no_stock_notification', 10, 2);

/**
 * Returns a row of cells for a calendar table.
 *
 * Expects the start and end date fields to be in the format 'dd/mm/yyyy'.
 *
 * @param string $action		eg sow, transplant, harvest
 * @param string $start_month	the start date field
 * @param string $end_month		the end date field
 * @return string				the row of cells (td elements)
 */
function get_vs_calendar_row_cells(
	$action,
	$start_month,
	$end_month
) {
	if (!$start_month || !$end_month) {
		return '';
	}
	$row = [];
	for ($i = 1; $i <= 12; $i++) {
		$month = date('F', mktime(0, 0, 0, $i, 10));
		$month_class = strtolower($month);
		$hl_class = '';
		if ($i >= $start_month && $i <= $end_month) {
			$hl_class .= ' highlight highlight-' . $action;
			$title = "title='$action in $month'";
		}
		$row[] = "<td class='$month_class $hl_class' $title></td>";
	}
	return implode("\n", $row);
}


/**
 * Displays a sowing calendar for the product.
 */
function vs_sowing_calendar()
{
	if (!acf_enabled()) return;

	if (!get_field('sowing_start_date') && !get_field('transplant_end_date') && !get_field('harvest_end_date')) {
		return;
	}

	$args = array(
		'sowing_row' => @get_vs_calendar_row_cells(
			'sow',
			get_group_field('sow_months', 'start_month'),
			get_group_field('sow_months', 'end_month')
		),
		'transplant_row' => @get_vs_calendar_row_cells(
			'transplant',
			get_group_field('transplant_months', 'start_month'),
			get_group_field('transplant_months', 'end_month')
		),
		'harvest_row' => @get_vs_calendar_row_cells(
			'harvest',
			get_group_field('harvest_months', 'start_month'),
			get_group_field('harvest_months', 'end_month')
		),
	);

	get_template_part('sowing-calendar', null, $args);
}

add_action('woocommerce_after_single_product_summary', 'vs_sowing_calendar', 3);

// function default_sow_months($value, $post_id, $field)
// {
// 	// $address = get_field('center_address', 342);
// 	// $field['default_value'] = $address;
// 	// $field['sub_fields'][0]["default_value';
// 	// $product = get_product()
// 	// $categories = wp_get_post_terms($product->id, 'product_cat')
// 	// $field[min(array_keys($field))] ?? null;
// 	// $field[array_key_first($field)];
// 	$start_month = intval($field[array_key_first($field)]);
// 	$end_month = intval($field[array_key_last($field)]);
// 	if ($start_month == 0 || $end_month == 0) {
// 		$field[array_key_first($field)] = 4;
// 		$field[array_key_last($field)] = 5;
// 	}
// 	return $field;
// }
function default_start_month($value, $post_id, $field)
{
	// $address = get_field('center_address', 342);
	// $field['default_value'] = '4';
	// $field['value'] = '4';
	// $field['sub_fields'][0]["default_value"] = '5';
	// $field['sub_fields'][0]["value"] = '4';
	// $product = get_product()
	// $categories = wp_get_post_terms($product->id, 'product_cat')
	if ($value) return $value;

	// switch ($field['name']) {
	// 	case 'sow_months_start_month':
	// 		$product = wc_get_product($post_id);
	// 		$cats = wp_get_post_terms($product->id, 'product_cat');
	// 		// Use last category, assumption that the last category is the most specific
	// 		$cat = $cats[array_key_last($cats)];
	// 		if (str_contains($cat->slug, 'seed') && $default = get_field('sow_months_start_month', $cat)) {
	// 			return $default;
	// 		}
	// 		return "";
	// 	case 'transplant_months_start_month':
	// 		return 5;
	// 	case 'harvest_months_start_month':
	// 		return 6;
	// }
	$product = wc_get_product($post_id);
	$cats = wp_get_post_terms($product->id, 'product_cat');
	// Use last category, assumption that the last category is the most specific
	$cat = $cats[array_key_last($cats)];
	// Get the field value from the category if it exists
	if (str_contains($cat->slug, 'seed') && $default = get_field($field['name'], $cat)) {
		return $default;
	}
	// TODO: this currently overwrites the value on the product edit page,
	// which means after save no longer inherits from the category
	return $field;
}

// function _wc_get_cached_product_terms($product_id, $taxonomy, $args = array())
// {
// 	$cache_key   = 'wc_' . $taxonomy . md5(wp_json_encode($args));
// 	$cache_group = WC_Cache_Helper::get_cache_prefix('product_' . $product_id) . $product_id;
// 	$terms       = wp_cache_get($cache_key, $cache_group);
// 	if (false !== $terms) {
// 		return $terms;
// 	}
// 	$terms = wp_get_post_terms($product_id, $taxonomy, $args);
// 	wp_cache_add($cache_key, $terms, $cache_group);
// 	return $terms;
// }

add_filter('acf/load_value/name=start_month', 'default_start_month', 10, 3);
// add_filter('acf/load_value/name=sow_months', 'default_sow_months', 10, 3);
