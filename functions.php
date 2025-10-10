<?php

use Yoast\WP\Duplicate_Post\UI\Column;

function my_theme_enqueue_styles()
{
	$parent_style = 'storefront-style';

	// Ensure parent theme stylesheet is enqueued (WordPress auto-enqueues child theme)
	if (!wp_style_is($parent_style, 'enqueued')) {
		wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css');
	}

	// Build dependencies array including parent and WooCommerce styles
	$dependencies = array($parent_style);

	// Add WooCommerce extension styles as dependencies if they exist
	// This ensures child theme loads after ALL Storefront styles
	$wc_extension_styles = array(
		'storefront-woocommerce-style',
		'storefront-woocommerce-brands-style',
		'storefront-woocommerce-bookings-style',
		'storefront-woocommerce-wishlists-style'
	);

	foreach ($wc_extension_styles as $style_handle) {
		if (wp_style_is($style_handle, 'registered') || wp_style_is($style_handle, 'enqueued')) {
			$dependencies[] = $style_handle;
		}
	}

	// WordPress automatically enqueues the child theme style.css with handle 'storefront-child-style'
	// Dequeue it and re-enqueue with proper dependencies to control loading order
	wp_dequeue_style('storefront-child-style'); // Remove auto-enqueued child theme style

	// Enqueue child theme stylesheet with all Storefront styles as dependencies
	wp_enqueue_style(
		'vitalseedstore-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		$dependencies, // Ensure all Storefront styles load first
		wp_get_theme()->get('Version')
	);
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_styles', 150); // Load after all Storefront styles (including extensions at priority 99)

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

function dequeue_all_elementor_styles() {
	global $wp_styles;

	$elementor_styles = array_filter(
		array_keys($wp_styles->registered),
		fn($handle) => strpos($handle, 'elementor') !== false
	);

	foreach ($elementor_styles as $handle) {
		wp_dequeue_style($handle);
	}
	// "elementor-icons"
	// "elementor-gallery"
	// "elementor-wp-admin-bar"
	// "elementor-frontend"
	// "elementor-icons-shared-0"
	// "elementor-common"
	// "elementor-post-12120" (default kit)
	// "elementor-post-7905" (?elementor_library=seed-sowing-guide)
	// "yoast-seo-elementor"
}

// add_action('wp_enqueue_scripts', 'dequeue_all_elementor_styles', 100);

include_once('includes/woocommerce.php');
include_once('includes/breadcrumbs.php');
include_once('includes/invoices.php');
include_once('includes/growing_guide.php');
include_once('includes/sowing_calendar.php');
