<?php
add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_6656eb0c53cea',
        'title' => 'Growing guide',
        'fields' => array(
            array(
                'key' => 'field_6656eb0dbb99d',
                'label' => 'Growing guide',
                'name' => 'growing_guide',
                'aria-label' => '',
                'type' => 'post_object',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'post_type' => array(
                    0 => 'growing-guide',
                ),
                'post_status' => '',
                'taxonomy' => '',
                'return_format' => 'object',
                'multiple' => 1,
                'allow_null' => 0,
                'bidirectional' => 0,
                'ui' => 1,
                'bidirectional_target' => array(),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'taxonomy',
                    'operator' => '==',
                    'value' => 'product_cat',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'acf_after_title',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 1,
    ));

    acf_add_local_field_group(array(
        'key' => 'group_665f1cbf9c770',
        'title' => 'Hero',
        'fields' => array(
            array(
                'key' => 'field_665f1ff4db7b6',
                'label' => 'Images',
                'name' => 'hero',
                'aria-label' => '',
                'type' => 'group',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'block',
                'sub_fields' => array(
                    array(
                        'key' => 'field_665f1ceb8288d',
                        'label' => 'Image 1',
                        'name' => 'hero_image_1',
                        'aria-label' => '',
                        'type' => 'image',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'return_format' => 'array',
                        'library' => 'all',
                        'min_width' => 1000,
                        'min_height' => 1000,
                        'min_size' => '',
                        'max_width' => '',
                        'max_height' => '',
                        'max_size' => '',
                        'mime_types' => '',
                        'preview_size' => 'medium',
                    ),
                    array(
                        'key' => 'field_665f1f3da852f',
                        'label' => 'Image 2',
                        'name' => 'hero_image_2',
                        'aria-label' => '',
                        'type' => 'image',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'return_format' => 'array',
                        'library' => 'all',
                        'min_width' => 1000,
                        'min_height' => 1000,
                        'min_size' => '',
                        'max_width' => '',
                        'max_height' => '',
                        'max_size' => '',
                        'mime_types' => '',
                        'preview_size' => 'medium',
                    ),
                    array(
                        'key' => 'field_665f1f48a8530',
                        'label' => 'Image 3',
                        'name' => 'hero_image_3',
                        'aria-label' => '',
                        'type' => 'image',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'return_format' => 'array',
                        'library' => 'all',
                        'min_width' => 1000,
                        'min_height' => 1000,
                        'min_size' => '',
                        'max_width' => '',
                        'max_height' => '',
                        'max_size' => '',
                        'mime_types' => '',
                        'preview_size' => 'medium',
                    ),
                    array(
                        'key' => 'field_665f1f4aa8531',
                        'label' => 'Image 4',
                        'name' => 'hero_image_4',
                        'aria-label' => '',
                        'type' => 'image',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'return_format' => 'array',
                        'library' => 'all',
                        'min_width' => 1000,
                        'min_height' => 1000,
                        'min_size' => '',
                        'max_width' => '',
                        'max_height' => '',
                        'max_size' => '',
                        'mime_types' => '',
                        'preview_size' => 'medium',
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'growing-guide',
                ),
            ),
        ),
        'menu_order' => 1,
        'position' => 'side',
        'style' => 'default',
        'label_placement' => 'left',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ));

    acf_add_local_field_group(array(
        'key' => 'group_6656ee4e073ba',
        'title' => 'Growing information',
        'fields' => array(
            array(
                'key' => 'field_6656ee4fa7c36',
                'label' => 'Product category',
                'name' => 'product_category',
                'aria-label' => '',
                'type' => 'taxonomy',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'taxonomy' => 'product_cat',
                'add_term' => 1,
                'save_terms' => 0,
                'load_terms' => 0,
                'return_format' => 'object',
                'field_type' => 'select',
                'allow_null' => 0,
                'bidirectional' => 0,
                'multiple' => 0,
                'bidirectional_target' => array(),
            ),
            array(
                'key' => 'field_665ef13ce3be5',
                'label' => 'Seed sowing',
                'name' => 'seed_sowing',
                'aria-label' => '',
                'type' => 'wysiwyg',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'tabs' => 'visual',
                'toolbar' => 'basic',
                'media_upload' => 1,
                'delay' => 0,
            ),
            array(
                'key' => 'field_665efe8b05d76',
                'label' => 'Transplanting',
                'name' => 'transplanting',
                'aria-label' => '',
                'type' => 'wysiwyg',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'tabs' => 'visual',
                'toolbar' => 'basic',
                'media_upload' => 1,
                'delay' => 0,
            ),
            array(
                'key' => 'field_665efeb61e7e7',
                'label' => 'Plant Care',
                'name' => 'plant_care',
                'aria-label' => '',
                'type' => 'wysiwyg',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'tabs' => 'visual',
                'toolbar' => 'basic',
                'media_upload' => 1,
                'delay' => 0,
            ),
            array(
                'key' => 'field_665efecdfae45',
                'label' => 'Challenges',
                'name' => 'challenges',
                'aria-label' => '',
                'type' => 'wysiwyg',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'tabs' => 'visual',
                'toolbar' => 'basic',
                'media_upload' => 1,
                'delay' => 0,
            ),
            array(
                'key' => 'field_665efedfea5cf',
                'label' => 'Harvest',
                'name' => 'harvest',
                'aria-label' => '',
                'type' => 'wysiwyg',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'tabs' => 'visual',
                'toolbar' => 'basic',
                'media_upload' => 1,
                'delay' => 0,
            ),
            array(
                'key' => 'field_665efef6ea5d0',
                'label' => 'Culinary Ideas',
                'name' => 'culinary_ideas',
                'aria-label' => '',
                'type' => 'wysiwyg',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'tabs' => 'visual',
                'toolbar' => 'basic',
                'media_upload' => 1,
                'delay' => 0,
            ),
            array(
                'key' => 'field_665eff17ea5d1',
                'label' => 'Seed Saving',
                'name' => 'seed_saving',
                'aria-label' => '',
                'type' => 'wysiwyg',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'tabs' => 'visual',
                'toolbar' => 'basic',
                'media_upload' => 1,
                'delay' => 0,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'growing-guide',
                ),
            ),
        ),
        'menu_order' => 3,
        'position' => 'acf_after_title',
        'style' => 'seamless',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ));
});

add_action('init', function () {
    register_post_type('growing-guide', array(
        'labels' => array(
            'name' => 'Growing Guides',
            'singular_name' => 'Growing guide',
            'menu_name' => 'Growing Guides',
            'all_items' => 'All Growing Guides',
            'edit_item' => 'Edit Growing guide',
            'view_item' => 'View Growing guide',
            'view_items' => 'View Growing Guides',
            'add_new_item' => 'Add New Growing guide',
            'add_new' => 'Add New Growing guide',
            'new_item' => 'New Growing guide',
            'parent_item_colon' => 'Parent Growing guide:',
            'search_items' => 'Search Growing Guides',
            'not_found' => 'No growing guides found',
            'not_found_in_trash' => 'No growing guides found in the bin',
            'archives' => 'Growing guide Archives',
            'attributes' => 'Growing guide Attributes',
            'insert_into_item' => 'Insert into growing guide',
            'uploaded_to_this_item' => 'Uploaded to this growing guide',
            'filter_items_list' => 'Filter growing guides list',
            'filter_by_date' => 'Filter growing guides by date',
            'items_list_navigation' => 'Growing Guides list navigation',
            'items_list' => 'Growing Guides list',
            'item_published' => 'Growing guide published.',
            'item_published_privately' => 'Growing guide published privately.',
            'item_reverted_to_draft' => 'Growing guide reverted to draft.',
            'item_scheduled' => 'Growing guide scheduled.',
            'item_updated' => 'Growing guide updated.',
            'item_link' => 'Growing guide Link',
            'item_link_description' => 'A link to a growing guide.',
        ),
        'public' => true,
        'show_in_rest' => true,
        'supports' => array(
            0 => 'title',
            1 => 'thumbnail',
        ),
        'taxonomies' => array(
            0 => 'product_cat',
        ),
        'delete_with_user' => false,
    ));
});