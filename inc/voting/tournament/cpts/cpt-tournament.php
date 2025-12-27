<?php
/**
 * Custom Post Type: Tournament
 * Manages automated voting tournaments with bracket progression
 */

if (!defined('ABSPATH')) exit;

function yuv_register_tournament_cpt() {
    $labels = [
        'name'               => 'Turniri',
        'singular_name'      => 'Turnir',
        'menu_name'          => 'Turniri',
        'add_new'            => 'Novi Turnir',
        'add_new_item'       => 'Dodaj novi turnir',
        'edit_item'          => 'Izmeni turnir',
        'new_item'           => 'Novi turnir',
        'view_item'          => 'Prikaži turnir',
        'search_items'       => 'Pretraži turnire',
        'not_found'          => 'Nema pronađenih turnira',
        'not_found_in_trash' => 'Nema turnira u korpi'
    ];

    $args = [
        'labels'              => $labels,
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => 'edit.php?post_type=voting_list',
        'menu_position'       => 27,
        'menu_icon'           => 'dashicons-awards',
        'capability_type'     => 'post',
        'hierarchical'        => false,
        'supports'            => ['title', 'editor', 'thumbnail'],
        'has_archive'         => true,
        'rewrite'             => [
            'slug' => 'turnir',
            'with_front' => false
        ],
    ];

    register_post_type('yuv_tournament', $args);
}
add_action('init', 'yuv_register_tournament_cpt');
