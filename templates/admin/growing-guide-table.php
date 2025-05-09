<?php

$args = array(
    'items' => $categories,
    'filter_no_guide' => $filter_no_guide,
    'next_order' => $next_order,
    'type' => 'category',
);

?>
<div class="wrap">
    <h1><?php _e('Growing Guide Product Report', 'vital-sowing-calendar'); ?></h1>
    <form method="get" action="">
        <input type="hidden" name="post_type" value="growing-guide">
        <input type="hidden" name="page" value="growing-guide-product-report">
        <label>
            <input type="checkbox" name="filter_no_guide" value="1" <?php echo $args['filter_no_guide'] ? 'checked' : ''; ?>>
            <?php _e('Only items without a Growing Guide', 'vital-sowing-calendar'); ?>
        </label>
        <button type="submit" class="button"><?php _e('Filter', 'vital-sowing-calendar'); ?></button>
    </form>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th>
                    <a href="?post_type=growing-guide&page=growing-guide-product-report&orderby=product_name&order=<?php echo $args['next_order']; ?>">
                        <?php _e('Product Name', 'vital-sowing-calendar'); ?>
                    </a>
                </th>
                <th><?php _e('Growing Guide', 'vital-sowing-calendar'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($args['items'] as $item): ?>
                <tr>
                    <td>
                        <a href="<?php echo get_edit_post_link($item->ID); ?>">
                            <?php echo esc_html($item->post_title); ?>
                        </a>
                    </td>
                    <td>
                        <?php display_growing_guide_link($item, false); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
