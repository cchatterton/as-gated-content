<?php
/**
 * Custom post type registration.
 *
 * @package AS_Gated_Content
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'asgc_register_post_types');
add_filter('use_block_editor_for_post_type', 'asgc_disable_gate_rule_block_editor', 10, 2);

function asgc_register_post_types(): void
{
    register_post_type(
        'gate',
        array(
            'labels' => array(
                'name'               => __('Gates', 'as-gated-conten'),
                'singular_name'      => __('Gate', 'as-gated-conten'),
                'add_new_item'       => __('Add New Gate', 'as-gated-conten'),
                'edit_item'          => __('Edit Gate', 'as-gated-conten'),
                'new_item'           => __('New Gate', 'as-gated-conten'),
                'view_item'          => __('View Gate', 'as-gated-conten'),
                'search_items'       => __('Search Gates', 'as-gated-conten'),
                'not_found'          => __('No gates found.', 'as-gated-conten'),
                'not_found_in_trash' => __('No gates found in Trash.', 'as-gated-conten'),
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'menu_icon'    => 'dashicons-lock',
            'supports'     => array('title', 'editor'),
            'show_in_rest' => true,
            'has_archive'  => false,
            'rewrite'      => false,
        )
    );

    register_post_type(
        'gate_rule',
        array(
            'labels' => array(
                'name'               => __('Gate Rules', 'as-gated-conten'),
                'singular_name'      => __('Gate Rule', 'as-gated-conten'),
                'add_new_item'       => __('Add New Gate Rule', 'as-gated-conten'),
                'edit_item'          => __('Edit Gate Rule', 'as-gated-conten'),
                'new_item'           => __('New Gate Rule', 'as-gated-conten'),
                'search_items'       => __('Search Gate Rules', 'as-gated-conten'),
                'not_found'          => __('No gate rules found.', 'as-gated-conten'),
                'not_found_in_trash' => __('No gate rules found in Trash.', 'as-gated-conten'),
            ),
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => 'edit.php?post_type=gate',
            'publicly_queryable' => false,
            'exclude_from_search'=> true,
            'supports'           => array('title'),
            'show_in_rest'       => false,
            'has_archive'        => false,
            'rewrite'            => false,
        )
    );
}

function asgc_disable_gate_rule_block_editor(bool $use_block_editor, string $post_type): bool
{
    if ('gate_rule' === $post_type) {
        return false;
    }

    return $use_block_editor;
}
