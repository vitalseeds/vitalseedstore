<?php
/**
 * WP-CLI commands for Vitalseedstore theme
 */

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

/**
 * Manage navigation menus with category links
 *
 * ## EXAMPLES
 *
 *     # Populate menu with all product categories
 *     $ wp vitalseedstore menu populate primary
 *
 *     # Populate menu with categories under a parent
 *     $ wp vitalseedstore menu populate primary --parent-category=seeds
 *
 *     # Add categories under an existing menu item
 *     $ wp vitalseedstore menu populate primary --parent-menu-item="Shop" --parent-category=seeds
 *
 *     # Clear and repopulate submenu items
 *     $ wp vitalseedstore menu populate primary --parent-menu-item="Shop" --clear-submenu
 *
 *     # Clear entire menu (with confirmation)
 *     $ wp vitalseedstore menu clear primary
 *
 *     # Clear only submenu items without confirmation (non-interactive)
 *     $ wp vitalseedstore menu clear primary --parent-menu-item="Shop" --yes
 *
 *     # Remove suffix from all submenu items
 *     $ wp vitalseedstore menu remove_suffix primary "Shop" " - Organic"
 *     $ wp vitalseedstore menu remove_suffix primary "Shop" " Seeds"
 *
 *     # Strip parent category names from submenu items
 *     $ wp vitalseedstore menu strip_categories primary "Shop"
 *
 *     # Set Megamenu Pro display modes for menu and submenus
 *     $ wp vitalseedstore menu populate primary --megamenu-display-mode=megamenu --megamenu-submenu-display-mode=flyout
 *
 *     # Preview changes without modifying
 *     $ wp vitalseedstore menu populate primary --dry-run
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
     * [--parent-menu-item=<title>]
     * : Add categories under this existing menu item (by title)
     *
     * [--clear]
     * : Clear existing menu items before adding new ones
     *
     * [--clear-submenu]
     * : Clear only category items that will be replaced (smart clearing)
     *
     * [--clear-all]
     * : Clear all child items under the parent menu item before adding new ones
     *
     * [--megamenu-display-mode=<mode>]
     * : Megamenu Pro display mode: applies to parent-menu-item if specified, otherwise to top-level categories
     *
     * [--megamenu-submenu-display-mode=<mode>]
     * : Megamenu Pro display mode: applies to items under parent-menu-item if specified, otherwise to submenus
     *
     * [--dry-run]
     * : Preview changes without actually modifying the menu
     *
     * [--yes]
     * : Skip confirmation prompts and proceed with the operation
     *
     * ## EXAMPLES
     *
     *     # Populate the 'primary' menu with all product categories
     *     wp vitalseedstore menu populate primary
     *
     *     # Populate menu with only categories under 'seeds' parent
     *     wp vitalseedstore menu populate primary --parent-category=seeds
     *
     *     # Add categories under an existing "Shop" menu item
     *     wp vitalseedstore menu populate primary --parent-menu-item="Shop"
     *
     *     # Smart clear - only remove category items that will be replaced
     *     wp vitalseedstore menu populate primary --parent-menu-item="Shop" --clear-submenu
     *
     *     # Clear all child items under "Shop" and repopulate
     *     wp vitalseedstore menu populate primary --parent-menu-item="Shop" --clear-all
     *
     *     # Clear existing items and populate with categories
     *     wp vitalseedstore menu populate primary --clear
     *
     *     # Set Megamenu Pro display modes (without parent menu item)
     *     # Top-level categories get 'megamenu', their children get 'flyout'
     *     wp vitalseedstore menu populate primary --megamenu-display-mode=megamenu --megamenu-submenu-display-mode=flyout
     *
     *     # Set Megamenu Pro display modes (with parent menu item)
     *     # "Shop" gets 'megamenu', categories under "Shop" get 'flyout'
     *     wp vitalseedstore menu populate primary --parent-menu-item="Shop" --megamenu-display-mode=megamenu --megamenu-submenu-display-mode=flyout
     *
     *     # Preview changes without modifying
     *     wp vitalseedstore menu populate primary --dry-run
     *
     *     # Populate in non-interactive mode (skip confirmations)
     *     wp vitalseedstore menu populate primary --parent-menu-item="Shop" --parent-category=seeds --clear-submenu --yes
     *
     * @when after_wp_load
     */
    public function populate($args, $assoc_args) {
        list($menu_identifier) = $args;

        $parent_category = isset($assoc_args['parent-category']) ? $assoc_args['parent-category'] : null;
        $parent_menu_item_title = isset($assoc_args['parent-menu-item']) ? $assoc_args['parent-menu-item'] : null;
        $clear = isset($assoc_args['clear']);
        $clear_submenu = isset($assoc_args['clear-submenu']);
        $clear_all = isset($assoc_args['clear-all']);
        $megamenu_display_mode = isset($assoc_args['megamenu-display-mode']) ? $assoc_args['megamenu-display-mode'] : null;
        $megamenu_submenu_display_mode = isset($assoc_args['megamenu-submenu-display-mode']) ? $assoc_args['megamenu-submenu-display-mode'] : null;
        $dry_run = isset($assoc_args['dry-run']);

        // Get the menu object
        $menu = $this->get_menu($menu_identifier);
        if (!$menu) {
            WP_CLI::error("Menu '$menu_identifier' not found.");
        }

        WP_CLI::log("Working with menu: {$menu->name} (ID: {$menu->term_id})");

        // Find or create parent menu item if specified
        $parent_menu_item_id = $this->setup_parent_menu_item(
            $menu->term_id,
            $parent_menu_item_title,
            $dry_run
        );

        // If we have a parent menu item, set its megamenu display mode
        if ($parent_menu_item_id && $megamenu_display_mode && !$dry_run) {
            // Parse display mode: extract base mode and column count (e.g., "grid3" -> "grid" + 3 columns)
            $parsed = $this->parse_display_mode($megamenu_display_mode);
            $display_mode_value = $parsed['mode'];
            $columns = $parsed['columns'];

            // Megamenu Pro uses multiple meta keys for compatibility

            // 1. _megamenu - Main settings array
            $megamenu_settings = get_post_meta($parent_menu_item_id, '_megamenu', true);
            if (!is_array($megamenu_settings)) {
                $megamenu_settings = array();
            }
            $megamenu_settings['type'] = $display_mode_value;
            if ($columns) {
                $megamenu_settings['grid_columns'] = (int)$columns;
            }
            update_post_meta($parent_menu_item_id, '_megamenu', $megamenu_settings);

            // 2. _megamenu_type - Direct type value
            update_post_meta($parent_menu_item_id, '_megamenu_type', $display_mode_value);

            // 3. _menu_item_megamenu_settings - Alternative settings array
            $item_settings = array('type' => $display_mode_value);
            if ($columns) {
                $item_settings['grid_columns'] = (int)$columns;
            }
            update_post_meta($parent_menu_item_id, '_menu_item_megamenu_settings', $item_settings);

            $log_msg = "Set Megamenu display mode '$display_mode_value'" . ($columns ? " with $columns columns" : "") . " on parent menu item (ID: $parent_menu_item_id).";
            WP_CLI::log($log_msg);
        }

        // Clear existing menu items if requested
        if ($clear && !$dry_run) {
            $this->clear_menu_items($menu->term_id);
            WP_CLI::success("Cleared existing menu items.");
        } elseif ($clear && $dry_run) {
            WP_CLI::log("[DRY RUN] Would clear existing menu items.");
        }

        // Get categories that will be added
        $categories = $this->get_categories_tree($parent_category);

        if (empty($categories)) {
            WP_CLI::warning("No categories found to add to menu.");
            return;
        }

        // Clear existing submenu items if requested
        if (($clear_submenu || $clear_all) && $parent_menu_item_id) {
            if ($clear_all) {
                // Clear all submenu items
                $parent_menu_item_id = $this->clear_and_refresh_submenu(
                    $menu->term_id,
                    $parent_menu_item_id,
                    $parent_menu_item_title,
                    $dry_run
                );
            } elseif ($clear_submenu) {
                // Smart clear - only remove category items that will be replaced
                $parent_menu_item_id = $this->clear_matching_categories_from_submenu(
                    $menu->term_id,
                    $parent_menu_item_id,
                    $parent_menu_item_title,
                    $categories,
                    $dry_run
                );
            }
        }

        // Populate menu with categories
        $this->populate_menu_with_categories(
            $menu,
            $parent_category,
            $parent_menu_item_id,
            $parent_menu_item_title,
            $megamenu_display_mode,
            $megamenu_submenu_display_mode,
            $dry_run
        );
    }

    // ========================================================================
    // MENU SETUP & LOOKUP METHODS
    // ========================================================================

    /**
     * Find or create a parent menu item for organizing categories
     *
     * @param int $menu_id The menu term ID
     * @param string|null $parent_title Title of the parent menu item
     * @param bool $dry_run Whether this is a dry run
     * @return int Parent menu item ID (0 if no parent specified)
     */
    private function setup_parent_menu_item($menu_id, $parent_title, $dry_run) {
        if (!$parent_title) {
            return 0;
        }

        // Try to find existing parent menu item
        $parent_id = $this->find_menu_item_by_title($menu_id, $parent_title);

        if (!$parent_id) {
            // Create the parent menu item if it doesn't exist
            if (!$dry_run) {
                $parent_id = wp_update_nav_menu_item($menu_id, 0, array(
                    'menu-item-title' => $parent_title,
                    'menu-item-url' => '#',
                    'menu-item-status' => 'publish',
                    'menu-item-type' => 'custom',
                ));

                if (is_wp_error($parent_id)) {
                    WP_CLI::error("Failed to create parent menu item '$parent_title': " . $parent_id->get_error_message());
                }

                WP_CLI::success("Created parent menu item: $parent_title (ID: $parent_id)");
            } else {
                WP_CLI::log("[DRY RUN] Would create parent menu item: $parent_title");
                $parent_id = 999999; // Placeholder for dry run
            }
        }

        WP_CLI::log("Adding categories under menu item: $parent_title (ID: $parent_id)");
        return $parent_id;
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
     * Find a menu item by its title
     */
    private function find_menu_item_by_title($menu_id, $title) {
        $menu_items = wp_get_nav_menu_items($menu_id);
        if (!$menu_items) {
            return false;
        }

        foreach ($menu_items as $item) {
            if ($item->title === $title) {
                return $item->ID;
            }
        }

        return false;
    }

    // ========================================================================
    // MENU ITEM DELETION METHODS
    // ========================================================================

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
     * Clear all child items under a specific parent menu item (recursively)
     */
    private function clear_submenu_items($menu_id, $parent_item_id) {
        $menu_items = wp_get_nav_menu_items($menu_id);
        $cleared = 0;

        if ($menu_items) {
            // Find all direct children of the parent
            $children_to_delete = array();
            foreach ($menu_items as $item) {
                if ($item->menu_item_parent == $parent_item_id) {
                    $children_to_delete[] = $item->ID;
                }
            }

            // Recursively delete each child and their descendants
            foreach ($children_to_delete as $child_id) {
                $cleared += $this->delete_menu_item_and_descendants($menu_id, $child_id, $menu_items);
            }
        }

        return $cleared;
    }

    /**
     * Delete a menu item and all its descendants recursively
     *
     * Uses depth-first recursive deletion to ensure all descendants are removed
     * before deleting parent items, preventing orphaned menu items.
     *
     * @param int $menu_id The menu term ID
     * @param int $item_id The menu item ID to delete
     * @param array|null $menu_items Optional pre-fetched menu items for efficiency
     * @return int Number of items deleted (including descendants)
     */
    private function delete_menu_item_and_descendants($menu_id, $item_id, $menu_items = null) {
        if ($menu_items === null) {
            $menu_items = wp_get_nav_menu_items($menu_id);
        }

        $deleted = 0;

        // First, recursively delete all children
        foreach ($menu_items as $item) {
            if ($item->menu_item_parent == $item_id) {
                $deleted += $this->delete_menu_item_and_descendants($menu_id, $item->ID, $menu_items);
            }
        }

        // Then delete this item
        wp_delete_post($item_id, true);
        $deleted++;

        return $deleted;
    }

    /**
     * Clear only category menu items that match the categories being added
     *
     * Smart clearing that only removes menu items pointing to categories that will
     * be replaced, preserving any custom menu items.
     *
     * @param int $menu_id The menu term ID
     * @param int $parent_menu_item_id The parent menu item ID
     * @param string $parent_menu_item_title The parent menu item title (for re-verification)
     * @param array $categories Category tree that will be added
     * @param bool $dry_run Whether this is a dry run
     * @return int The parent menu item ID (refreshed after cache clear)
     */
    private function clear_matching_categories_from_submenu($menu_id, $parent_menu_item_id, $parent_menu_item_title, $categories, $dry_run) {
        // Extract all category IDs from the tree recursively
        $category_ids = $this->extract_category_ids($categories);

        $menu_items = wp_get_nav_menu_items($menu_id);
        $cleared = 0;

        if ($menu_items) {
            // Find all direct children of the parent
            $children_to_check = array();
            foreach ($menu_items as $item) {
                if ($item->menu_item_parent == $parent_menu_item_id) {
                    $children_to_check[] = $item->ID;
                }
            }

            // Check each child and delete if it's a category item that will be replaced
            foreach ($children_to_check as $child_id) {
                $cleared += $this->delete_if_matching_category($menu_id, $child_id, $category_ids, $menu_items, $dry_run);
            }
        }

        if (!$dry_run) {
            // Clear WordPress menu cache to ensure fresh data
            wp_cache_delete($menu_id, 'nav_menu_items');
            clean_post_cache($parent_menu_item_id);

            // Re-verify parent menu item still exists and get fresh ID
            $parent_menu_item_id = $this->find_menu_item_by_title($menu_id, $parent_menu_item_title);
            if (!$parent_menu_item_id) {
                WP_CLI::error("Parent menu item '$parent_menu_item_title' was lost after clearing submenu.");
            }

            WP_CLI::success("Cleared $cleared category items under '$parent_menu_item_title' (preserved non-category items).");
        } else {
            WP_CLI::log("[DRY RUN] Would clear $cleared category items under '$parent_menu_item_title' (preserving non-category items).");
        }

        return $parent_menu_item_id;
    }

    /**
     * Extract all category IDs from a category tree recursively
     *
     * @param array $categories Category tree
     * @return array Array of category IDs
     */
    private function extract_category_ids($categories) {
        $ids = array();
        foreach ($categories as $category) {
            $ids[] = $category->term_id;
            if (!empty($category->children)) {
                $ids = array_merge($ids, $this->extract_category_ids($category->children));
            }
        }
        return $ids;
    }

    /**
     * Delete a menu item if it's a category item matching one of the target categories
     *
     * Also recursively deletes children if the parent is deleted.
     *
     * @param int $menu_id The menu term ID
     * @param int $item_id The menu item ID to check
     * @param array $category_ids Array of category IDs that will be replaced
     * @param array $menu_items All menu items
     * @param bool $dry_run Whether this is a dry run
     * @return int Number of items deleted
     */
    private function delete_if_matching_category($menu_id, $item_id, $category_ids, $menu_items, $dry_run) {
        $deleted = 0;

        // Find the item
        $item = null;
        foreach ($menu_items as $menu_item) {
            if ($menu_item->ID == $item_id) {
                $item = $menu_item;
                break;
            }
        }

        if (!$item) {
            return 0;
        }

        // Check if this is a category item that matches our target categories
        $is_matching_category = (
            $item->type === 'taxonomy' &&
            $item->object === 'product_cat' &&
            in_array($item->object_id, $category_ids)
        );

        if ($is_matching_category) {
            // Delete this item and all its descendants
            if (!$dry_run) {
                $deleted = $this->delete_menu_item_and_descendants($menu_id, $item_id, $menu_items);
            } else {
                // Count what would be deleted for dry run
                $deleted = $this->count_descendants($item_id, $menu_items) + 1;
            }
        } else {
            // Not a matching category, but check children
            foreach ($menu_items as $potential_child) {
                if ($potential_child->menu_item_parent == $item_id) {
                    $deleted += $this->delete_if_matching_category($menu_id, $potential_child->ID, $category_ids, $menu_items, $dry_run);
                }
            }
        }

        return $deleted;
    }

    /**
     * Count descendants of a menu item
     *
     * @param int $item_id The menu item ID
     * @param array $menu_items All menu items
     * @return int Number of descendants
     */
    private function count_descendants($item_id, $menu_items) {
        $count = 0;
        foreach ($menu_items as $item) {
            if ($item->menu_item_parent == $item_id) {
                $count++;
                $count += $this->count_descendants($item->ID, $menu_items);
            }
        }
        return $count;
    }

    /**
     * Clear submenu items and refresh menu cache
     *
     * Clears all child items under a parent menu item, then refreshes WordPress
     * caches and re-verifies the parent menu item still exists.
     *
     * @param int $menu_id The menu term ID
     * @param int $parent_menu_item_id The parent menu item ID
     * @param string $parent_menu_item_title The parent menu item title (for re-verification)
     * @param bool $dry_run Whether this is a dry run
     * @return int The parent menu item ID (refreshed after cache clear)
     */
    private function clear_and_refresh_submenu($menu_id, $parent_menu_item_id, $parent_menu_item_title, $dry_run) {
        if (!$dry_run) {
            $cleared = $this->clear_submenu_items($menu_id, $parent_menu_item_id);

            // Clear WordPress menu cache to ensure fresh data
            wp_cache_delete($menu_id, 'nav_menu_items');
            clean_post_cache($parent_menu_item_id);

            // Re-verify parent menu item still exists and get fresh ID
            $parent_menu_item_id = $this->find_menu_item_by_title($menu_id, $parent_menu_item_title);
            if (!$parent_menu_item_id) {
                WP_CLI::error("Parent menu item '$parent_menu_item_title' was lost after clearing submenu.");
            }

            WP_CLI::success("Cleared $cleared existing child items under '$parent_menu_item_title'.");
        } else {
            WP_CLI::log("[DRY RUN] Would clear existing child items under '$parent_menu_item_title'.");
        }

        return $parent_menu_item_id;
    }

    // ========================================================================
    // CATEGORY TREE BUILDING METHODS
    // ========================================================================

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

    // ========================================================================
    // MENU POPULATION METHODS
    // ========================================================================

    /**
     * Populate menu with product categories
     *
     * Gets category tree and adds items to menu, or previews the structure in dry run mode.
     *
     * @param object $menu The menu object
     * @param string|null $parent_category Parent category slug to filter by
     * @param int $parent_menu_item_id Parent menu item ID to nest under
     * @param string|null $parent_menu_item_title Parent menu item title (for display)
     * @param string|null $megamenu_display_mode Megamenu Pro display mode for top-level items
     * @param string|null $megamenu_submenu_display_mode Megamenu Pro display mode for first-level submenus
     * @param bool $dry_run Whether this is a dry run
     */
    private function populate_menu_with_categories($menu, $parent_category, $parent_menu_item_id, $parent_menu_item_title, $megamenu_display_mode, $megamenu_submenu_display_mode, $dry_run) {
        // Get categories to add
        $categories = $this->get_categories_tree($parent_category);

        if (empty($categories)) {
            WP_CLI::warning("No categories found to add to menu.");
            return;
        }

        WP_CLI::log(sprintf("Found %d top-level categories to add.", count($categories)));

        // When there's a parent menu item, shift the display modes down one level
        // The parent gets megamenu_display_mode (already set above)
        // Categories directly under parent get megamenu_submenu_display_mode
        // Their children get nothing
        $effective_menu_mode = $parent_menu_item_id ? $megamenu_submenu_display_mode : $megamenu_display_mode;
        $effective_submenu_mode = $parent_menu_item_id ? null : $megamenu_submenu_display_mode;

        if ($dry_run) {
            WP_CLI::log("\n[DRY RUN] Would add the following menu structure:");
            if ($parent_menu_item_title) {
                WP_CLI::log("Under parent menu item: $parent_menu_item_title");
                if ($megamenu_display_mode) {
                    WP_CLI::log("  - Parent menu item would get: $megamenu_display_mode");
                }
                if ($megamenu_submenu_display_mode) {
                    WP_CLI::log("  - Categories under parent would get: $megamenu_submenu_display_mode");
                }
            } else {
                if ($megamenu_display_mode) {
                    WP_CLI::log("Megamenu display mode (top-level): $megamenu_display_mode");
                }
                if ($megamenu_submenu_display_mode) {
                    WP_CLI::log("Megamenu display mode (submenus): $megamenu_submenu_display_mode");
                }
            }
            $this->preview_menu_structure($categories);
        } else {
            $added = $this->add_categories_to_menu($menu->term_id, $categories, $parent_menu_item_id, 0, $effective_menu_mode, $effective_submenu_mode, 0);
            WP_CLI::success(sprintf("Added %d menu items to '%s'.", $added, $menu->name));

            if ($parent_menu_item_id) {
                // When using parent menu item
                if ($megamenu_display_mode) {
                    WP_CLI::success(sprintf("Set Megamenu display mode to '%s' for parent menu item.", $megamenu_display_mode));
                }
                if ($megamenu_submenu_display_mode) {
                    WP_CLI::success(sprintf("Set Megamenu display mode to '%s' for categories under parent.", $megamenu_submenu_display_mode));
                }
            } else {
                // When NOT using parent menu item
                if ($megamenu_display_mode) {
                    WP_CLI::success(sprintf("Set Megamenu display mode to '%s' for top-level items.", $megamenu_display_mode));
                }
                if ($megamenu_submenu_display_mode) {
                    WP_CLI::success(sprintf("Set Megamenu display mode to '%s' for submenus.", $megamenu_submenu_display_mode));
                }
            }
        }
    }

    /**
     * Add categories to menu recursively
     *
     * @param int $menu_id The menu term ID
     * @param array $categories Array of category objects
     * @param int $parent_menu_item_id Parent menu item ID
     * @param int $position Menu item position
     * @param string|null $megamenu_display_mode Megamenu Pro display mode for top-level
     * @param string|null $megamenu_submenu_display_mode Megamenu Pro display mode for submenus
     * @param int $depth Current depth level (0 = top-level, 1 = submenu, 2+ = deeper)
     * @param int|null $parent_grid_columns Number of grid columns in parent (for distributing items)
     * @return int Number of items added
     */
    private function add_categories_to_menu($menu_id, $categories, $parent_menu_item_id = 0, $position = 0, $megamenu_display_mode = null, $megamenu_submenu_display_mode = null, $depth = 0, $parent_grid_columns = null) {
        $added_count = 0;

        foreach ($categories as $index => $category) {
            $position++;

            // Add the category to the menu
            $menu_item_id = wp_update_nav_menu_item($menu_id, 0, array(
                'menu-item-title' => $category->name,
                'menu-item-url' => get_term_link($category),
                'menu-item-status' => 'publish',
                'menu-item-type' => 'taxonomy',
                'menu-item-object' => 'product_cat',
                'menu-item-object-id' => $category->term_id,
                'menu-item-parent-id' => (int) $parent_menu_item_id,
                'menu-item-position' => $position,
            ));

            if (is_wp_error($menu_item_id)) {
                WP_CLI::warning("Failed to add category '{$category->name}': " . $menu_item_id->get_error_message());
                continue;
            }

            $added_count++;

            // If parent has grid columns, assign this item to a column
            if ($parent_grid_columns) {
                // Distribute items across columns (1-based indexing for Megamenu)
                $column_number = ($index % $parent_grid_columns) + 1;

                // Set column assignment in _megamenu settings array
                $megamenu_settings = get_post_meta($menu_item_id, '_megamenu', true);
                if (!is_array($megamenu_settings)) {
                    $megamenu_settings = array();
                }
                $megamenu_settings['mega_menu_column'] = $column_number;
                update_post_meta($menu_item_id, '_megamenu', $megamenu_settings);

                WP_CLI::log("  → Assigned '{$category->name}' to column $column_number");
            }

            // Set Megamenu Pro display mode if specified
            $mode_to_set = null;

            // Top-level items (depth 0) get the menu display mode
            if ($depth === 0 && $megamenu_display_mode) {
                $mode_to_set = $megamenu_display_mode;
            }
            // First-level submenus (depth 1) get the submenu display mode
            elseif ($depth === 1 && $megamenu_submenu_display_mode) {
                $mode_to_set = $megamenu_submenu_display_mode;
            }

            // Apply the mode if set (Megamenu Pro uses multiple meta keys)
            if ($mode_to_set) {
                // Parse display mode: extract base mode and column count (e.g., "grid3" -> "grid" + 3 columns)
                $parsed = $this->parse_display_mode($mode_to_set);
                $display_mode_value = $parsed['mode'];
                $columns = $parsed['columns'];

                // 1. _megamenu - Main settings array (preserve existing settings like column assignment)
                $megamenu_settings = get_post_meta($menu_item_id, '_megamenu', true);
                if (!is_array($megamenu_settings)) {
                    $megamenu_settings = array();
                }
                $megamenu_settings['type'] = $display_mode_value;
                if ($columns) {
                    $megamenu_settings['grid_columns'] = (int)$columns;
                }
                // Note: This preserves any existing keys like 'mega_menu_column' set earlier
                update_post_meta($menu_item_id, '_megamenu', $megamenu_settings);

                // 2. _megamenu_type - Direct type value
                update_post_meta($menu_item_id, '_megamenu_type', $display_mode_value);

                // 3. _menu_item_megamenu_settings - Alternative settings array
                $item_settings = array('type' => $display_mode_value);
                if ($columns) {
                    $item_settings['grid_columns'] = (int)$columns;
                }
                update_post_meta($menu_item_id, '_menu_item_megamenu_settings', $item_settings);

                $log_msg = "  → Set '{$category->name}' (ID: $menu_item_id) type to '$display_mode_value'" . ($columns ? " ($columns columns)" : "");
                WP_CLI::log($log_msg);
            }

            // Add children recursively
            if (!empty($category->children)) {
                // Check if this item has grid columns - if so, we need to distribute children across columns
                $parent_columns = null;
                if ($mode_to_set) {
                    $parsed = $this->parse_display_mode($mode_to_set);
                    if ($parsed['mode'] === 'grid' && $parsed['columns']) {
                        $parent_columns = $parsed['columns'];
                    }
                }

                $added_count += $this->add_categories_to_menu(
                    $menu_id,
                    $category->children,
                    $menu_item_id,
                    0,
                    $megamenu_display_mode,
                    $megamenu_submenu_display_mode,
                    $depth + 1,
                    $parent_columns  // Pass column count to distribute children
                );
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

    // ========================================================================
    // CLEAR COMMAND
    // ========================================================================

    /**
     * Clear all items from a menu
     *
     * ## OPTIONS
     *
     * <menu>
     * : The menu slug, ID, or name to clear
     *
     * [--parent-menu-item=<title>]
     * : Clear only child items under this menu item (by title)
     *
     * [--dry-run]
     * : Preview what would be deleted without actually deleting
     *
     * [--yes]
     * : Skip confirmation prompt and proceed with deletion
     *
     * ## EXAMPLES
     *
     *     # Clear all items from the 'primary' menu
     *     wp vitalseedstore menu clear primary
     *
     *     # Clear only child items under "Shop" menu item
     *     wp vitalseedstore menu clear primary --parent-menu-item="Shop"
     *
     *     # Preview what would be cleared
     *     wp vitalseedstore menu clear primary --dry-run
     *
     *     # Clear without confirmation prompt (non-interactive)
     *     wp vitalseedstore menu clear primary --yes
     *
     * @when after_wp_load
     */
    public function clear($args, $assoc_args) {
        list($menu_identifier) = $args;

        $parent_menu_item_title = isset($assoc_args['parent-menu-item']) ? $assoc_args['parent-menu-item'] : null;
        $dry_run = isset($assoc_args['dry-run']);
        $skip_confirm = isset($assoc_args['yes']);

        // Get the menu object
        $menu = $this->get_menu($menu_identifier);
        if (!$menu) {
            WP_CLI::error("Menu '$menu_identifier' not found.");
        }

        WP_CLI::log("Working with menu: {$menu->name} (ID: {$menu->term_id})");

        // Find parent menu item if specified
        $parent_menu_item_id = 0;
        if ($parent_menu_item_title) {
            $parent_menu_item_id = $this->find_menu_item_by_title($menu->term_id, $parent_menu_item_title);
            if (!$parent_menu_item_id) {
                WP_CLI::error("Parent menu item '$parent_menu_item_title' not found in menu.");
            }
            WP_CLI::log("Clearing child items under menu item: $parent_menu_item_title (ID: $parent_menu_item_id)");
        }

        // Get menu items to clear
        $menu_items = wp_get_nav_menu_items($menu->term_id);

        if (!$menu_items || empty($menu_items)) {
            WP_CLI::warning("Menu '{$menu->name}' is already empty.");
            return;
        }

        // Filter items if parent menu item is specified
        if ($parent_menu_item_id) {
            $items_to_delete = array_filter($menu_items, function($item) use ($parent_menu_item_id) {
                return $item->menu_item_parent == $parent_menu_item_id;
            });
        } else {
            $items_to_delete = $menu_items;
        }

        if (empty($items_to_delete)) {
            $msg = $parent_menu_item_title
                ? "No child items found under '$parent_menu_item_title'."
                : "Menu '{$menu->name}' is already empty.";
            WP_CLI::warning($msg);
            return;
        }

        $item_count = count($items_to_delete);

        if ($dry_run) {
            WP_CLI::log("\n[DRY RUN] Would delete the following $item_count menu items:");
            foreach ($items_to_delete as $item) {
                $parent_indicator = $item->menu_item_parent ? " (child of item #{$item->menu_item_parent})" : "";
                WP_CLI::log("  - {$item->title} (ID: {$item->ID}){$parent_indicator}");
            }
        } else {
            $confirm_msg = $parent_menu_item_title
                ? "Are you sure you want to delete $item_count child items from '$parent_menu_item_title'?"
                : "Are you sure you want to delete all $item_count items from '{$menu->name}'?";

            if (!$skip_confirm) {
                WP_CLI::confirm($confirm_msg);
            }

            if ($parent_menu_item_id) {
                $cleared = $this->clear_submenu_items($menu->term_id, $parent_menu_item_id);
                WP_CLI::success("Deleted $cleared child items from '$parent_menu_item_title'.");
            } else {
                $this->clear_menu_items($menu->term_id);
                WP_CLI::success("Deleted $item_count items from '{$menu->name}'.");
            }
        }
    }

    // ========================================================================
    // REMOVE SUFFIX COMMAND
    // ========================================================================

    /**
     * Remove a suffix from all submenu items under a parent menu item
     *
     * ## OPTIONS
     *
     * <menu>
     * : The menu slug, ID, or name
     *
     * <parent-menu-item>
     * : The parent menu item title
     *
     * <suffix>
     * : The suffix to remove from menu item titles
     *
     * [--case-insensitive]
     * : Perform case-insensitive suffix matching
     *
     * [--dry-run]
     * : Preview what would be changed without actually modifying
     *
     * [--yes]
     * : Skip confirmation prompt and proceed with changes
     *
     * ## EXAMPLES
     *
     *     # Remove " - Organic" suffix from all items under "Shop"
     *     wp vitalseedstore menu remove_suffix primary "Shop" " - Organic"
     *
     *     # Remove suffix case-insensitively (matches " - ORGANIC", " - organic", etc.)
     *     wp vitalseedstore menu remove_suffix primary "Shop" " - Organic" --case-insensitive
     *
     *     # Preview changes without modifying
     *     wp vitalseedstore menu remove_suffix primary "Shop" " - Organic" --dry-run
     *
     *     # Remove suffix without confirmation (non-interactive)
     *     wp vitalseedstore menu remove_suffix primary "Shop" " - Organic" --yes
     *
     * @when after_wp_load
     */
    public function remove_suffix($args, $assoc_args) {
        list($menu_identifier, $parent_menu_item_title, $suffix) = $args;

        $dry_run = isset($assoc_args['dry-run']);
        $skip_confirm = isset($assoc_args['yes']);
        $case_insensitive = isset($assoc_args['case-insensitive']);

        // Get the menu object
        $menu = $this->get_menu($menu_identifier);
        if (!$menu) {
            WP_CLI::error("Menu '$menu_identifier' not found.");
        }

        WP_CLI::log("Working with menu: {$menu->name} (ID: {$menu->term_id})");

        // Find parent menu item
        $parent_menu_item_id = $this->find_menu_item_by_title($menu->term_id, $parent_menu_item_title);
        if (!$parent_menu_item_id) {
            WP_CLI::error("Parent menu item '$parent_menu_item_title' not found in menu.");
        }

        WP_CLI::log("Finding all descendant items under: $parent_menu_item_title (ID: $parent_menu_item_id)");

        // Get all descendants recursively
        $menu_items = wp_get_nav_menu_items($menu->term_id);
        if (!$menu_items || empty($menu_items)) {
            WP_CLI::warning("Menu '{$menu->name}' is empty.");
            return;
        }

        $descendants = $this->get_menu_item_descendants($parent_menu_item_id, $menu_items);

        if (empty($descendants)) {
            WP_CLI::warning("No submenu items found under '$parent_menu_item_title'.");
            return;
        }

        WP_CLI::log(sprintf("Found %d descendant items.", count($descendants)));

        // Filter items that have the suffix (exclude parent menu item itself)
        $items_to_update = array();
        foreach ($descendants as $item) {
            // Safety check: never modify the parent menu item itself
            if ($item->ID == $parent_menu_item_id) {
                continue;
            }
            if ($this->string_ends_with($item->title, $suffix, $case_insensitive)) {
                $items_to_update[] = $item;
            }
        }

        if (empty($items_to_update)) {
            WP_CLI::warning("No items found with suffix '$suffix' under '$parent_menu_item_title'.");
            return;
        }

        $update_count = count($items_to_update);
        WP_CLI::log(sprintf("Found %d items with suffix '%s'.", $update_count, $suffix));

        if ($dry_run) {
            WP_CLI::log("\n[DRY RUN] Would update the following $update_count menu items:");
            foreach ($items_to_update as $item) {
                $new_title = $this->remove_suffix_from_string($item->title, $suffix, $case_insensitive);
                WP_CLI::log(sprintf("  - '%s' → '%s' (ID: %d)", $item->title, $new_title, $item->ID));
            }
        } else {
            $confirm_msg = sprintf(
                "Are you sure you want to remove suffix '%s' from %d items under '%s'?%s",
                $suffix,
                $update_count,
                $parent_menu_item_title,
                $case_insensitive ? ' (case-insensitive)' : ''
            );

            if (!$skip_confirm) {
                WP_CLI::confirm($confirm_msg);
            }

            // Update each item
            $updated = 0;
            foreach ($items_to_update as $item) {
                $new_title = $this->remove_suffix_from_string($item->title, $suffix, $case_insensitive);

                // Preserve all existing menu item properties
                $result = wp_update_nav_menu_item($menu->term_id, $item->ID, array(
                    'menu-item-title' => $new_title,
                    'menu-item-url' => $item->url,
                    'menu-item-status' => 'publish',
                    'menu-item-type' => $item->type,
                    'menu-item-object' => $item->object,
                    'menu-item-object-id' => $item->object_id,
                    'menu-item-parent-id' => (int) $item->menu_item_parent,
                    'menu-item-position' => $item->menu_order,
                    'menu-item-classes' => implode(' ', $item->classes),
                    'menu-item-xfn' => $item->xfn,
                    'menu-item-description' => $item->description,
                    'menu-item-attr-title' => $item->attr_title,
                    'menu-item-target' => $item->target,
                ));

                if (is_wp_error($result)) {
                    WP_CLI::warning(sprintf("Failed to update item '%s' (ID: %d): %s", $item->title, $item->ID, $result->get_error_message()));
                } else {
                    $updated++;
                    WP_CLI::log(sprintf("Updated: '%s' → '%s'", $item->title, $new_title));
                }
            }

            WP_CLI::success(sprintf("Updated %d of %d items.", $updated, $update_count));
        }
    }

    // ========================================================================
    // STRIP CATEGORIES COMMAND
    // ========================================================================

    /**
     * Strip parent category names from all submenu items recursively
     *
     * Loops through all submenu items and removes their direct parent's title
     * from the end of their title (case-insensitive). Also handles plural/singular
     * variations automatically. For example, if "Beans" has children "Black Beans",
     * "Red Bean", they become "Black" and "Red". Works with "Seeds"/"Seed",
     * "Berries"/"Berry", etc.
     *
     * ## OPTIONS
     *
     * <menu>
     * : The menu slug, ID, or name
     *
     * <parent-menu-item>
     * : The parent menu item title to start from
     *
     * [--dry-run]
     * : Preview what would be changed without actually modifying
     *
     * [--yes]
     * : Skip confirmation prompt and proceed with changes
     *
     * ## EXAMPLES
     *
     *     # Strip category names from all submenu items
     *     # (handles both "Black Beans" and "Red Bean" under "Beans" parent)
     *     wp vitalseedstore menu strip_categories primary "Shop"
     *
     *     # Preview changes without modifying
     *     wp vitalseedstore menu strip_categories primary "Shop" --dry-run
     *
     *     # Strip without confirmation (non-interactive)
     *     wp vitalseedstore menu strip_categories primary "Shop" --yes
     *
     * @when after_wp_load
     */
    public function strip_categories($args, $assoc_args) {
        list($menu_identifier, $parent_menu_item_title) = $args;

        $dry_run = isset($assoc_args['dry-run']);
        $skip_confirm = isset($assoc_args['yes']);

        // Get the menu object
        $menu = $this->get_menu($menu_identifier);
        if (!$menu) {
            WP_CLI::error("Menu '$menu_identifier' not found.");
        }

        WP_CLI::log("Working with menu: {$menu->name} (ID: {$menu->term_id})");

        // Find parent menu item
        $parent_menu_item_id = $this->find_menu_item_by_title($menu->term_id, $parent_menu_item_title);
        if (!$parent_menu_item_id) {
            WP_CLI::error("Parent menu item '$parent_menu_item_title' not found in menu.");
        }

        WP_CLI::log("Finding all descendant items under: $parent_menu_item_title (ID: $parent_menu_item_id)");

        // Get all menu items and descendants
        $menu_items = wp_get_nav_menu_items($menu->term_id);
        if (!$menu_items || empty($menu_items)) {
            WP_CLI::warning("Menu '{$menu->name}' is empty.");
            return;
        }

        // Create a lookup map for quick parent title retrieval
        $menu_items_by_id = array();
        foreach ($menu_items as $item) {
            $menu_items_by_id[$item->ID] = $item;
        }

        $descendants = $this->get_menu_item_descendants($parent_menu_item_id, $menu_items);

        if (empty($descendants)) {
            WP_CLI::warning("No submenu items found under '$parent_menu_item_title'.");
            return;
        }

        WP_CLI::log(sprintf("Found %d descendant items.", count($descendants)));

        // Filter items that end with their parent's title or its plural/singular form (case-insensitive)
        $items_to_update = array();
        foreach ($descendants as $item) {
            // Skip if item has no parent or parent not found
            if (!$item->menu_item_parent || !isset($menu_items_by_id[$item->menu_item_parent])) {
                continue;
            }

            $parent_item = $menu_items_by_id[$item->menu_item_parent];

            // Get all variations of the parent title (original, plural, singular)
            $parent_variations = $this->get_word_variations($parent_item->title);

            // Check if item title ends with any variation of parent title (case-insensitive)
            $matched_suffix = null;
            foreach ($parent_variations as $variation) {
                $test_suffix = ' ' . $variation;
                if ($this->string_ends_with($item->title, $test_suffix, true)) {
                    $matched_suffix = $test_suffix;
                    break;
                }
            }

            if ($matched_suffix) {
                $items_to_update[] = array(
                    'item' => $item,
                    'suffix' => $matched_suffix,
                    'parent_title' => $parent_item->title
                );
            }
        }

        if (empty($items_to_update)) {
            WP_CLI::warning("No items found that end with their parent's title.");
            return;
        }

        $update_count = count($items_to_update);
        WP_CLI::log(sprintf("Found %d items to strip category names from.", $update_count));

        if ($dry_run) {
            WP_CLI::log("\n[DRY RUN] Would update the following $update_count menu items:");
            foreach ($items_to_update as $update_data) {
                $item = $update_data['item'];
                $suffix = $update_data['suffix'];
                $new_title = $this->remove_suffix_from_string($item->title, $suffix, true);
                WP_CLI::log(sprintf("  - '%s' → '%s' (ID: %d, parent: %s)",
                    $item->title, $new_title, $item->ID, $update_data['parent_title']));
            }
        } else {
            $confirm_msg = sprintf(
                "Are you sure you want to strip category names from %d items under '%s'?",
                $update_count,
                $parent_menu_item_title
            );

            if (!$skip_confirm) {
                WP_CLI::confirm($confirm_msg);
            }

            // Update each item
            $updated = 0;
            foreach ($items_to_update as $update_data) {
                $item = $update_data['item'];
                $suffix = $update_data['suffix'];
                $new_title = $this->remove_suffix_from_string($item->title, $suffix, true);

                // Preserve all existing menu item properties
                $result = wp_update_nav_menu_item($menu->term_id, $item->ID, array(
                    'menu-item-title' => $new_title,
                    'menu-item-url' => $item->url,
                    'menu-item-status' => 'publish',
                    'menu-item-type' => $item->type,
                    'menu-item-object' => $item->object,
                    'menu-item-object-id' => $item->object_id,
                    'menu-item-parent-id' => (int) $item->menu_item_parent,
                    'menu-item-position' => $item->menu_order,
                    'menu-item-classes' => implode(' ', $item->classes),
                    'menu-item-xfn' => $item->xfn,
                    'menu-item-description' => $item->description,
                    'menu-item-attr-title' => $item->attr_title,
                    'menu-item-target' => $item->target,
                ));

                if (is_wp_error($result)) {
                    WP_CLI::warning(sprintf("Failed to update item '%s' (ID: %d): %s",
                        $item->title, $item->ID, $result->get_error_message()));
                } else {
                    $updated++;
                    WP_CLI::log(sprintf("Updated: '%s' → '%s'", $item->title, $new_title));
                }
            }

            WP_CLI::success(sprintf("Updated %d of %d items.", $updated, $update_count));
        }
    }

    // ========================================================================
    // HELPER METHODS FOR REMOVE SUFFIX
    // ========================================================================

    /**
     * Get all descendant menu items recursively
     *
     * @param int $parent_id The parent menu item ID
     * @param array $all_items All menu items
     * @return array Array of descendant menu items
     */
    private function get_menu_item_descendants($parent_id, $all_items) {
        $descendants = array();

        // Find direct children
        foreach ($all_items as $item) {
            if ($item->menu_item_parent == $parent_id) {
                $descendants[] = $item;
                // Recursively get children of this child
                $children = $this->get_menu_item_descendants($item->ID, $all_items);
                $descendants = array_merge($descendants, $children);
            }
        }

        return $descendants;
    }

    /**
     * Get plural and singular variations of a word
     *
     * @param string $word The word to get variations of
     * @return array Array of variations (includes original, plural, and singular forms)
     */
    private function get_word_variations($word) {
        $variations = array($word);

        // Generate singular form
        if (substr($word, -3) === 'ies') {
            // berries → berry
            $variations[] = substr($word, 0, -3) . 'y';
        } elseif (substr($word, -1) === 's') {
            // beans → bean, seeds → seed
            $variations[] = substr($word, 0, -1);
        }

        // Generate plural form
        if (substr($word, -1) === 'y' && !in_array(substr($word, -2, 1), array('a', 'e', 'i', 'o', 'u'))) {
            // berry → berries (but not for "key" → "keies")
            $variations[] = substr($word, 0, -1) . 'ies';
        } elseif (substr($word, -1) !== 's') {
            // bean → beans, seed → seeds
            $variations[] = $word . 's';
        }

        // Remove duplicates
        return array_unique($variations);
    }

    /**
     * Check if a string ends with a suffix
     *
     * @param string $string The string to check
     * @param string $suffix The suffix to check for
     * @param bool $case_insensitive Whether to perform case-insensitive comparison
     * @return bool True if string ends with suffix
     */
    private function string_ends_with($string, $suffix, $case_insensitive = false) {
        $length = strlen($suffix);
        if ($length == 0) {
            return true;
        }

        $string_end = substr($string, -$length);

        if ($case_insensitive) {
            return strcasecmp($string_end, $suffix) === 0;
        }

        return $string_end === $suffix;
    }

    /**
     * Remove suffix from a string
     *
     * @param string $string The string to modify
     * @param string $suffix The suffix to remove
     * @param bool $case_insensitive Whether to perform case-insensitive matching
     * @return string The string without the suffix
     */
    private function remove_suffix_from_string($string, $suffix, $case_insensitive = false) {
        if ($this->string_ends_with($string, $suffix, $case_insensitive)) {
            return substr($string, 0, -strlen($suffix));
        }
        return $string;
    }

    /**
     * Parse display mode to extract base mode and column count
     *
     * Examples:
     *   "grid3" -> array('mode' => 'grid', 'columns' => 3)
     *   "grid" -> array('mode' => 'grid', 'columns' => null)
     *   "flyout" -> array('mode' => 'flyout', 'columns' => null)
     *
     * @param string $mode_string The display mode string (e.g., "grid3", "flyout")
     * @return array Array with 'mode' and 'columns' keys
     */
    private function parse_display_mode($mode_string) {
        // Match pattern: base mode followed by optional digits
        if (preg_match('/^([a-z]+)(\d+)?$/i', $mode_string, $matches)) {
            return array(
                'mode' => $matches[1],
                'columns' => isset($matches[2]) && $matches[2] !== '' ? (int)$matches[2] : null
            );
        }

        // Fallback: return as-is if pattern doesn't match
        return array(
            'mode' => $mode_string,
            'columns' => null
        );
    }
}

WP_CLI::add_command('vitalseedstore menu', 'Vitalseedstore_Menu_Command');
