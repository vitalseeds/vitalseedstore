<?php

/**
 * Renders the Sowing Calendar Report admin page for products.
 */
function render_sowing_calendar_product_report_page() {
	$products = get_posts(array(
		'post_type' => 'product',
		'posts_per_page' => -1,
		// 'posts_per_page' => 10,
		'post_status' => 'publish',
	));

	// Filter products without a sowing calendar if requested
	$filter_no_calendar = isset($_GET['filter_no_calendar']) && $_GET['filter_no_calendar'] === '1';
	if ($filter_no_calendar) {
		$products = array_filter($products, function ($product) {
			return !get_field('sowing_calendar', $product->ID);
		});
	}

	// Determine sorting order
	$order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'desc' : 'asc';
	$next_order = $order === 'asc' ? 'desc' : 'asc';

	// Sort products by title
	if (isset($_GET['orderby']) && $_GET['orderby'] === 'product_name') {
		usort($products, function ($a, $b) use ($order) {
			$result = strcmp($a->post_title, $b->post_title);
			return $order === 'asc' ? $result : -$result;
		});
	}
	echo '<div class="wrap">';
	echo '<h1>' . __('Sowing Calendar Product Report', 'vital-sowing-calendar') . '</h1>';
	echo '<form method="get" action="">';
	echo '<input type="hidden" name="post_type" value="growing-guide">';
	echo '<input type="hidden" name="page" value="sowing-calendar-product-report">';
	echo '<label>';
	echo '<input type="checkbox" name="filter_no_calendar" value="1"' . ($filter_no_calendar ? ' checked' : '') . '> ';
	echo __('Only products without a Sowing Calendar', 'vital-sowing-calendar');
	echo '</label>';
	echo '<button type="submit" class="button">' . __('Filter', 'vital-sowing-calendar') . '</button>';
	echo '</form>';
	echo '<table class="widefat fixed striped">';
	echo '<thead><tr>';
	echo '<th><a href="?post_type=growing-guide&page=sowing-calendar-product-report&orderby=product_name&order=' . $next_order . '">' . __('Product Name', 'vital-sowing-calendar') . '</a></th>';
	echo '<th>' . __('Sowing Calendar', 'vital-sowing-calendar') . '</th>';
	echo '</tr></thead>';
	echo '<tbody>';

	foreach ($products as $product) {
		$sow_month_parts = get_value_from_field_or_category('vs_calendar_sow_month_parts', $product->ID);
		$plant_month_parts = get_value_from_field_or_category('vs_calendar_plant_month_parts', $product->ID);
		$harvest_month_parts = get_value_from_field_or_category('vs_calendar_harvest_month_parts', $product->ID);

		$fields = get_fields($product->ID);
		$has_product_calendar = !empty($fields['vs_calendar_sow_month_parts']) ||
							 !empty($fields['vs_calendar_plant_month_parts']) ||
							 !empty($fields['vs_calendar_harvest_month_parts']);

		echo '<tr>';
		echo '<td><a href="' . get_edit_post_link($product->ID) . '">' . esc_html($product->post_title) . '</a></td>';
		echo '<td>';

		if ($sow_month_parts || $plant_month_parts || $harvest_month_parts) {
			$parts = [];
			if ($sow_month_parts) {
				$parts[] = 'sow';
			}
			if ($plant_month_parts) {
				$parts[] = 'plant';
			}
			if ($harvest_month_parts) {
				$parts[] = 'harvest';
			}
			echo implode(' | ', $parts);
			if (!$has_product_calendar) {
				$product_cats = wp_get_post_terms($product->ID, 'product_cat');
				$cat = end($product_cats);
				if ($cat !== null) {
					echo ' <em>(<a href="' . esc_url(get_edit_term_link($cat, 'product_cat')) . '">' . esc_html($cat->name) . '</a>)</em>';
				}
			}
		} else {
			echo '-';
		}
		echo '</td>';
		echo '</tr>';
	}

	echo '</tbody>';
	echo '</table>';
	echo '</div>';
}

// Add the Sowing Calendar Report page to the admin menu
add_action('admin_menu', function () {
	$parent_slug = 'edit.php?post_type=growing-guide'; // Move under 'Growing Guides' post type menu
	add_submenu_page(
		$parent_slug,
		__('Product Calendars', 'vital-sowing-calendar'),
		__('Product Calendars', 'vital-sowing-calendar'),
		'manage_options',
		'sowing-calendar-product-report',
		'render_sowing_calendar_product_report_page'
	);
});

/**
 * Renders the Sowing Calendar Report admin page for product categories.
 */
function render_sowing_calendar_category_report_page() {
	$categories = get_terms(array(
		'taxonomy' => 'product_cat',
		'hide_empty' => false,
		'orderby' => 'name',
		'order' => 'ASC',
	));

	// Filter categories without a sowing calendar if requested
	$filter_no_calendar = isset($_GET['filter_no_calendar']) && $_GET['filter_no_calendar'] === '1';
	if ($filter_no_calendar) {
		$categories = array_filter($categories, function ($category) {
			return !get_field('sowing_calendar', 'product_cat_' . $category->term_id);
		});
	}

	// Determine sorting order
	$order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'desc' : 'asc';
	$next_order = $order === 'asc' ? 'desc' : 'asc';

	// Sort categories by name
	if (isset($_GET['orderby']) && $_GET['orderby'] === 'category_name') {
		usort($categories, function ($a, $b) use ($order) {
			$result = strcmp($a->name, $b->name);
			return $order === 'asc' ? $result : -$result;
		});
	}

	echo '<div class="wrap">';
	echo '<h1>' . __('Sowing Calendar Category Report', 'vital-sowing-calendar') . '</h1>';
	echo '<form method="get" action="">';
	echo '<input type="hidden" name="post_type" value="growing-guide">';
	echo '<input type="hidden" name="page" value="sowing-calendar-category-report">';
	echo '<label>';
	echo '<input type="checkbox" name="filter_no_calendar" value="1"' . ($filter_no_calendar ? ' checked' : '') . '> ';
	echo __('Only categories without a Sowing Calendar', 'vital-sowing-calendar');
	echo '</label>';
	echo '<button type="submit" class="button">' . __('Filter', 'vital-sowing-calendar') . '</button>';
	echo '</form>';
	echo '<table class="widefat fixed striped">';
	echo '<thead><tr>';
	echo '<th>ID</th>';
	echo '<th><a href="?post_type=growing-guide&page=sowing-calendar-category-report&orderby=category_name&order=' . $next_order . '">' . __('Category Name', 'vital-sowing-calendar') . '</a></th>';
	echo '<th>Sowing Calendar</th>';
	echo '<th>Growing Guide</th>';
	echo '<th>Product Count</th>';
	echo '</tr></thead>';
	echo '<tbody>';

	foreach ($categories as $category) {

		$sow_month_parts = get_field('vs_calendar_sow_month_parts', 'product_cat_' . $category->term_id);
		$plant_month_parts = get_field('vs_calendar_plant_month_parts', 'product_cat_' . $category->term_id);
		$harvest_month_parts = get_field('vs_calendar_harvest_month_parts', 'product_cat_' . $category->term_id);

		// Get the number of products assigned to this category
		$product_count = count(get_posts(array(
			'post_type' => 'product',
			'posts_per_page' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $category->term_id,
				),
			),
			'fields' => 'ids',
		)));

		$growing_guide_id = get_field('growing_guide', 'product_cat_' . $category->term_id);
		if ($growing_guide_id) {
			$growing_guide_link = get_edit_post_link($growing_guide_id);
			$growing_guide_title = get_the_title($growing_guide_id);
		}

		echo '<tr>';
		echo '<td>' . esc_html($category->term_id) . '</td>';
		echo '<td><a href="' . get_edit_term_link($category->term_id, 'product_cat') . '">' . esc_html($category->name) . '</a></td>';
		echo '<td>';

		if ($sow_month_parts || $plant_month_parts || $harvest_month_parts) {
			$parts = [];
			if ($sow_month_parts) {
				$parts[] = 'sow';
			}
			if ($plant_month_parts) {
				$parts[] = 'plant';
			}
			if ($harvest_month_parts) {
				$parts[] = 'harvest';
			}
			echo implode(' | ', $parts);
		} else {
			echo '-';
		}
		echo '</td>';
		echo '<td>';
		if (isset($growing_guide_link)) {
			echo '<a href="' . esc_url($growing_guide_link) . '">' . esc_html($growing_guide_title) . '</a>';
		} else {
			echo '-';
		}
		echo '</td>';
		echo '<td>';
		echo $product_count > 0 ? '<span class="count">' . esc_html($product_count) . '</span>' : '-';
		echo '</td>';
		echo '</tr>';
	}

	echo '</tbody>';
	echo '</table>';
	echo '</div>';
}

// Add the Sowing Calendar Report page to the admin menu
add_action('admin_menu', function () {
	$parent_slug = 'edit.php?post_type=growing-guide'; // Move under 'Growing Guides' post type menu
	add_submenu_page(
		$parent_slug,
		__('Category Calendars', 'vital-sowing-calendar'),
		__('Category Calendars', 'vital-sowing-calendar'),
		'manage_options',
		'sowing-calendar-category-report',
		'render_sowing_calendar_category_report_page'
	);
});