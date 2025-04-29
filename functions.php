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
