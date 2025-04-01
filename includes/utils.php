<?php

define('SEED_CAT_TERM_ID', 276);

function is_seed_category($term_id = null) {
    is_product_category();
    if (is_tax('product_cat') && !$term_id) {
        $term = get_queried_object();
        $term_id = $term->term_id;
    }
    return $term_id != SEED_PARENT_TERM_ID && term_is_ancestor_of(SEED_PARENT_TERM_ID, $term_id, 'product_cat');
}

function is_seed_product($product_id) {
    $terms = get_the_terms($product_id, 'product_cat');
    if ($terms) {
        foreach ($terms as $term) {
            if (is_seed_category($term->term_id)) {
                return true;
            }
        }
    }
    return false;
}