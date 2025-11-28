<?php
/**
 * WP-CLI commands for Vitalseedstore theme
 */

if (!defined('WP_CLI') || !WP_CLI) {
	return;
}

/**
 * Manage navigation menus with category links
 */
class Vitalseedstore_Menu_Command extends WP_CLI_Command {

	/**
	 * Populate a navigation menu with product categories and subcategories
	 *
	 * ## OPTIONS
	 *
	 * <menu>
	 * : The menu slug, ID, or name to populate
	 *
	 * [--parent-category=<slug>]
	 * : Only include categories under this parent category (default: all top-level categories)
	 *
	 * [--clear]
	 * : Clear existing menu items before adding new ones
	 *
	 * [--dry-run]
	 * : Preview changes without actually modifying the menu
	 *
	 * ## EXAMPLES
	 *
	 *     # Populate the 'primary' menu with all product categories
	 *     wp vitalseedstore menu populate primary
	 *
	 *     # Populate menu with only categories under 'seeds' parent
	 *     wp vitalseedstore menu populate primary --parent-category=seeds
	 *
	 *     # Clear existing items and populate with categories
	 *     wp vitalseedstore menu populate primary --clear
	 *
	 *     # Preview changes without modifying
	 *     wp vitalseedstore menu populate primary --dry-run
	 *
	 * @when after_wp_load
	 */
	public function populate($args, $assoc_args) {
		list($menu_identifier) = $args;

		$parent_category = isset($assoc_args['parent-category']) ? $assoc_args['parent-category'] : null;
		$clear = isset($assoc_args['clear']);
		$dry_run = isset($assoc_args['dry-run']);

		// Get the menu object
		$menu = $this->get_menu($menu_identifier);
		if (!$menu) {
			WP_CLI::error("Menu '$menu_identifier' not found.");
		}

		WP_CLI::log("Working with menu: {$menu->name} (ID: {$menu->term_id})");

		// Clear existing menu items if requested
		if ($clear && !$dry_run) {
			$this->clear_menu_items($menu->term_id);
			WP_CLI::success("Cleared existing menu items.");
		} elseif ($clear && $dry_run) {
			WP_CLI::log("[DRY RUN] Would clear existing menu items.");
		}

		// Get categories to add
		$categories = $this->get_categories_tree($parent_category);

		if (empty($categories)) {
			WP_CLI::warning("No categories found to add to menu.");
			return;
		}

		WP_CLI::log(sprintf("Found %d top-level categories to add.", count($categories)));

		if ($dry_run) {
			WP_CLI::log("\n[DRY RUN] Would add the following menu structure:");
			$this->preview_menu_structure($categories);
		} else {
			$added = $this->add_categories_to_menu($menu->term_id, $categories);
			WP_CLI::success(sprintf("Added %d menu items to '%s'.", $added, $menu->name));
		}
	}

	/**
	 * Get menu object by slug, ID, or name
	 */
	private function get_menu($identifier) {
		// Try as ID first
		if (is_numeric($identifier)) {
			$menu = wp_get_nav_menu_object($identifier);
			if ($menu) {
				return $menu;
			}
		}

		// Try as slug
		$menu = wp_get_nav_menu_object($identifier);
		if ($menu) {
			return $menu;
		}

		// Try as name
		$menus = wp_get_nav_menus();
		foreach ($menus as $menu_obj) {
			if ($menu_obj->name === $identifier) {
				return $menu_obj;
			}
		}

		return null;
	}

	/**
	 * Clear all items from a menu
	 */
	private function clear_menu_items($menu_id) {
		$menu_items = wp_get_nav_menu_items($menu_id);
		if ($menu_items) {
			foreach ($menu_items as $item) {
				wp_delete_post($item->ID, true);
			}
		}
	}

	/**
	 * Get hierarchical tree of product categories
	 */
	private function get_categories_tree($parent_slug = null) {
		$parent_id = 0;

		// If parent category is specified, get its ID
		if ($parent_slug) {
			$parent = get_term_by('slug', $parent_slug, 'product_cat');
			if (!$parent) {
				WP_CLI::error("Parent category '$parent_slug' not found.");
			}
			$parent_id = $parent->term_id;
		}

		// Get all categories
		$args = array(
			'taxonomy' => 'product_cat',
			'hide_empty' => false,
			'parent' => $parent_id,
			'orderby' => 'name',
			'order' => 'ASC',
		);

		$categories = get_terms($args);

		if (is_wp_error($categories)) {
			WP_CLI::error("Error fetching categories: " . $categories->get_error_message());
		}

		// Build hierarchical tree
		$tree = array();
		foreach ($categories as $category) {
			$category->children = $this->get_category_children($category->term_id);
			$tree[] = $category;
		}

		return $tree;
	}

	/**
	 * Get child categories recursively
	 */
	private function get_category_children($parent_id) {
		$args = array(
			'taxonomy' => 'product_cat',
			'hide_empty' => false,
			'parent' => $parent_id,
			'orderby' => 'name',
			'order' => 'ASC',
		);

		$children = get_terms($args);

		if (is_wp_error($children) || empty($children)) {
			return array();
		}

		// Recursively get children
		foreach ($children as $child) {
			$child->children = $this->get_category_children($child->term_id);
		}

		return $children;
	}

	/**
	 * Add categories to menu recursively
	 */
	private function add_categories_to_menu($menu_id, $categories, $parent_menu_item_id = 0, $position = 0) {
		$added_count = 0;

		foreach ($categories as $category) {
			$position++;

			// Add the category to the menu
			$menu_item_id = wp_update_nav_menu_item($menu_id, 0, array(
				'menu-item-title' => $category->name,
				'menu-item-url' => get_term_link($category),
				'menu-item-status' => 'publish',
				'menu-item-type' => 'taxonomy',
				'menu-item-object' => 'product_cat',
				'menu-item-object-id' => $category->term_id,
				'menu-item-parent-id' => $parent_menu_item_id,
				'menu-item-position' => $position,
			));

			if (is_wp_error($menu_item_id)) {
				WP_CLI::warning("Failed to add category '{$category->name}': " . $menu_item_id->get_error_message());
				continue;
			}

			$added_count++;

			// Add children recursively
			if (!empty($category->children)) {
				$added_count += $this->add_categories_to_menu($menu_id, $category->children, $menu_item_id, 0);
			}
		}

		return $added_count;
	}

	/**
	 * Preview menu structure (for dry run)
	 */
	private function preview_menu_structure($categories, $indent = 0) {
		foreach ($categories as $category) {
			$prefix = str_repeat('  ', $indent);
			WP_CLI::log($prefix . "- {$category->name} ({$category->slug})");

			if (!empty($category->children)) {
				$this->preview_menu_structure($category->children, $indent + 1);
			}
		}
	}
}

WP_CLI::add_command('vitalseedstore menu', 'Vitalseedstore_Menu_Command');
