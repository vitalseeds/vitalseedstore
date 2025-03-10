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

// Growing Guide

// If ACF enabled
if (function_exists('get_field')) {

	if (function_exists('vs_sowing_calendar')) {
		add_action('woocommerce_archive_description', function () {
			if (is_product_category()) {
				$term = get_queried_object();
				echo "<h4>Growing calendar</h4>";
				vs_sowing_calendar("term_$term->term_id");
			}
		}, 3);
	}

	// Add the ACF field group for the Growers Guide
	require_once('includes/acf/fields/acf-growing-guide.php');
	function category_growing_guide($term_id = null)
	{
		$category = null;
		$show_images = true;
		if (is_product_category()) {
			$category = get_queried_object();
		} elseif (is_product()) {
			$terms = get_the_terms(get_the_ID(), 'product_cat');
			// Only use a 'seed' category, eg not 'large packet'
			$parent_category = get_term_by('slug', 'seeds', 'product_cat');
			foreach ($terms as $term) {
				if (term_is_ancestor_of($parent_category, $term, 'product_cat')) {
					$category = $term;
					break;
				}
			}
			$show_images = false;
		}
		if ($category) {
			// get acf field from category
			$growing_guide = get_field('growing_guide', 'product_cat_' . $category->term_id);
			if ($growing_guide) {
				echo "<details class='growingguide'>";
				echo "<summary>" . $growing_guide[0]->post_title . "</summary><div>	";
				$args = array('growing_guide_id' => $growing_guide[0]->ID, 'show_images' => $show_images);
				get_template_part('parts/growingguide', 'sections', $args);
				echo "</div></details>";
			}
		}
	}
	add_action('woocommerce_archive_description', 'category_growing_guide', 3);

	// add_action('woocommerce_after_single_product_summary', 'category_growing_guide', 3);
} else {
	function vital_growingguide_admin_notice()
	{
		echo // Customize the message below as needed
		'<div class="notice notice-warning is-dismissible">
		<p>Vital Growers Guide will not display unless Advanced Custom Fields plugin is installed.</p>
		</div>';
	}
	add_action('admin_notices', 'vital_growingguide_admin_notice');
}

/**
 * (Unused) Adds Growing Information product tab to the WooCommerce product pages.
 *
 * @param array $tabs An array of existing WooCommerce product tabs.
 * @return array Modified array of WooCommerce product tabs with the custom tab added.
 */
function vital_custom_product_tab($tabs)
{
	$tabs['vital_tab'] = array(
		'title'    => __('Growing Information', 'vital-sowing-calendar'),
		'priority' => 1,
		'callback' => 'vital_custom_product_content'
	);
	return [];
	return $tabs;
}
// add_filter('woocommerce_product_tabs', 'vital_custom_product_tab');
add_filter('woocommerce_product_tabs', '__return_empty_array');

add_action('woocommerce_after_main_content', 'vital_custom_product_content');
function vital_custom_product_content()
{
	if (function_exists('vs_sowing_calendar')) {
		vs_sowing_calendar();
	}
	$growing_guide = category_growing_guide();
}

/**
 * Filters the main term used in WooCommerce breadcrumbs.
 *
 * This function modifies the main term used in WooCommerce breadcrumbs to ensure
 * that the 'seeds' category is considered as the main term if it is an ancestor
 * of any of the terms in the breadcrumb trail.
 *
 * @param WP_Term $main_term The main term used in the breadcrumb.
 * @param array $terms An array of terms in the breadcrumb trail.
 * @return WP_Term The modified main term.
 */
function vital_breadcrumb_main_term($main_term, $terms)
{
	$parent_category = get_term_by('slug', 'seeds', 'product_cat');
	$terms = array_reverse($terms);
	foreach ($terms as $term) {
		if (term_is_ancestor_of($parent_category, $term, 'product_cat')) {
			return $term;
		}
	}
	return $main_term;
}
add_filter('woocommerce_breadcrumb_main_term', 'vital_breadcrumb_main_term', 10, 2);

/**
 * Filters the WooCommerce breadcrumb trail to remove the 'Seeds' category.
 *
 * This function removes any breadcrumb entries that contain the 'Seeds' category
 * from the WooCommerce breadcrumb trail.
 *
 * @param array $crumbs The breadcrumb trail.
 * @param WC_Breadcrumb $breadcrumb The WooCommerce breadcrumb object.
 * @return array The modified breadcrumb trail.
 */
function vital_remove_seeds_from_breadcrumbs($crumbs, $breadcrumb)
{
	foreach ($crumbs as $key => $crumb) {
		if (in_array('Seeds', $crumb)) {
			unset($crumbs[$key]);
		}
	}
	// Reindex the array to ensure proper rendering
	$crumbs = array_values($crumbs);
	return $crumbs;
}
add_filter('woocommerce_get_breadcrumb', 'vital_remove_seeds_from_breadcrumbs', 10, 2);

/**
 * Adds VAT information to the shipping total in the order invoice.
 *
 * For the other modifications to the invoice, see template at woocommerce/pdf/vital_invoice/invoice.php
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
			'value' => $order_item_totals['shipping']['value'] . " (includes " . wc_price( $order->get_shipping_tax(), array( 'currency' => $order->get_currency() ) ) . ' VAT)',);
	}
	return $order_item_totals;
}
add_filter('wpo_wcpdf_raw_order_totals', 'vital_invoice_order_totals', 10, 2);