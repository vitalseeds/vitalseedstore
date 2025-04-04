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

	// Add the ACF field group for the Growers Guide
	require_once('includes/acf/fields/acf-growing-guide.php');
	require_once('includes/utils.php');

	function category_growing_guide($term_id = null, $show_images=true)
	{
		$category = null;
		$details = false;
		if (is_product_category()) {
			$category = get_queried_object();
			$details = true;
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
				if ($details) {
					echo "<details class='growingguide'><summary>" . $growing_guide[0]->post_title . "</summary><div>  ";
					echo "<h2>" . $growing_guide[0]->post_title . "</h2>";
				}
				$args = array(
					'growing_guide_id' => $growing_guide[0]->ID,
					'show_images' => $show_images,
					'show_pdf_link' => true,

				);
				get_template_part('parts/growingguide', 'sections', $args);
				if ($details) {
					echo "</div></details>";
				}
			}
			return $growing_guide;
		}
	}

	function product_growing_guide($product_id=null, $show_images=false)
	{
		if (!$product_id && is_product()) {
			$product_id = get_the_ID();
		}
		if ($product_id && get_field('growing_guide', $product_id)) {
			$growing_guide = get_field('growing_guide', $product_id);
			$args = array(
				'growing_guide_id' => $growing_guide->ID,
				'show_images' => $show_images,
				'show_pdf_link' => false,

			);
			get_template_part('parts/growingguide', 'sections', $args);
			return $growing_guide;
		}
		return false;
	}

	// Display vital content like Growing Guides and calendars

	// Remove growing information product tabs

	add_filter('woocommerce_product_tabs', '__return_empty_array', 98);

	if (function_exists('vs_sowing_calendar')) {
		add_action('woocommerce_archive_description', function () {
			if (is_product_category()) {
				$term = get_queried_object();
				echo "<h4>Growing calendar</h4>";
				vs_sowing_calendar("term_$term->term_id");
			}
		}, 3);
		add_action('woocommerce_after_single_product_summary', function () {
			vs_sowing_calendar();
		}, 3);
	}

	if (function_exists('category_growing_guide')) {
		// add_action('woocommerce_before_single_product_summary', function () {
		add_action('woocommerce_after_single_product_summary', function () {
			// Either display product growing guide or category growing guide
			product_growing_guide() ?  : category_growing_guide();
		}, 3);

		add_action('woocommerce_archive_description', function () {
			if (is_seed_category()) {
				remove_action('woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10);
				category_growing_guide(null, false);
			}
		}, 3);
		// Remove the default WooCommerce taxonomy archive description
	}

	// add_action('woocommerce_archive_description', 'category_growing_guide', 3);

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
// function vital_custom_product_tab($tabs)
// {
// 	$tabs['vital_tab'] = array(
// 		'title'    => __('Growing Information', 'vital-sowing-calendar'),
// 		'priority' => 1,
// 		'callback' => 'vital_custom_product_content'
// 	);
// 	return [];
// 	return $tabs;
// }
// add_filter('woocommerce_product_tabs', 'vital_custom_product_tab');

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

require_once('includes/remove_seeds_url.php');


// Admin tweaks

/**
 * Adds a custom meta box to the product edit screen in the WordPress admin.
 *
 * Displays a link to the product's related Growing Guide or a message if none is found.
 *
 * @hook add_action('add_meta_boxes')
 */
add_action('add_meta_boxes', function () {
	add_meta_box(
		'growing_guide_link',
		__('Related Growing Guide', 'vital-sowing-calendar'),
		'display_growing_guide_link',
		'product',
		'side',
		'high' // Set priority to 'high' to make it appear directly under the Publish meta box
	);
});

/**
 * Displays the content of the "Related Growing Guide" meta box.
 *
 * Retrieves product categories and checks for a related Growing Guide via ACF.
 * Displays an edit link if found, otherwise shows a "not found" message.
 *
 * @param WP_Post $post The current post object.
 */
function display_growing_guide_link($post) {
	if (get_field('growing_guide', $post->ID)) {
		$growing_guide = get_field('growing_guide', $post->ID);
		echo '<p>Product <a href="' . get_edit_post_link($growing_guide->ID) . '" target="_blank">' . __('Growing Guide', 'vital-sowing-calendar') . '</a></p>';
		echo '<p><em>A growing guide is specified for this product, so it overrides the category growing guide.</em></p>';
		return;
	}
	$terms = get_the_terms($post->ID, 'product_cat');
	if ($terms && !is_wp_error($terms)) {
		foreach ($terms as $term) {
			$growing_guide = get_field('growing_guide', 'product_cat_' . $term->term_id);
			if ($growing_guide) {
				echo '<p>Category <a href="' . get_edit_post_link($growing_guide[0]->ID) . '" target="_blank">' . __('Growing Guide', 'vital-sowing-calendar') . '</a></p>';
				echo '<p><em>Growing guide is specified for category and not overridden by product.</em></p>';
				return;
			}
		}
	}
	echo '<p>' . __('No related Growing Guide found.', 'vital-sowing-calendar') . '</p>';
	echo '<p><em>No growing guide is specified for either category or product, so no guide will be shown.</em></p>';
}