<?php


// Growing Guide

// If ACF enabled
if (function_exists('get_field')) {

	// Add the ACF field group for the Growers Guide
	require_once('acf/fields/acf-growing-guide.php');
	require_once('acf/fields/acf-migration-backups.php');
	require_once('utils.php');

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
					echo "<details class='growingguide'><summary>" . $growing_guide->post_title . "</summary><div>  ";
					echo "<h2>" . $growing_guide->post_title . "</h2>";
				}
				$args = array(
					'growing_guide_id' => $growing_guide->ID,
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
		echo '<p><a href="' . get_edit_post_link($growing_guide->ID) . '" target="_blank">' . $growing_guide->post_title . '</a></p>';
		echo '<p><em>A growing guide is specified for the <strong>product</strong>, so it overrides the category growing guide.</em></p>';
		return;
	}
	$terms = get_the_terms($post->ID, 'product_cat');
	if ($terms && !is_wp_error($terms)) {
		foreach ($terms as $term) {
			$growing_guide = get_field('growing_guide', 'product_cat_' . $term->term_id);
			if ($growing_guide) {
				echo '<p><a href="' . get_edit_post_link($growing_guide->ID) . '" target="_blank">' . $growing_guide->post_title . '</a></p>';
				echo '<p><em>Growing guide is specified for <strong>category</strong> and not overridden by product.</em></p>';
				return;
			}
		}
	}
	echo '<p>' . __('No related Growing Guide found.', 'vital-sowing-calendar') . '</p>';
	echo '<p><em>No growing guide is specified for either category or product, so no guide will be shown.</em></p>';
}

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
