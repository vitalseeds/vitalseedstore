<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


/**
 * Removes the 'seeds' slug from product category permalinks.
 *
 * This function hooks into the 'term_link' filter to modify the permalink structure
 * for product categories. If the taxonomy is 'product_cat' and the permalink contains
 * 'product-category/seeds/', it replaces 'product-category/seeds/' with 'product-category/'.
 *
 * This allows us to move all the seeds categories under a parent seeds category
 * without affecting the URLS.
 *
 * Later, we can remove this, and use 'seeds' instead of 'product-category' in permalinks.
 * This will require making redirects for every single category, so is a separate task.
 *
 * @param string $permalink The original permalink.
 * @param WP_Term $term The term object.
 * @param string $taxonomy The taxonomy slug.
 * @return string The modified permalink.
 */
function rcs_remove_seeds_slug_permalinks( $permalink, $term, $taxonomy ) {
    if ( $taxonomy === 'product_cat' && strpos( $permalink, 'product-category/seeds/' ) !== false ) {
        $permalink = str_replace( 'product-category/seeds/', 'product-category/', $permalink );
    }
    return $permalink;
}
add_filter( 'term_link', 'rcs_remove_seeds_slug_permalinks', 10, 3 );
