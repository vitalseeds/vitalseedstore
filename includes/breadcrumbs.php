<?php

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