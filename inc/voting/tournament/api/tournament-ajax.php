<?php
/**
 * Tournament AJAX Handlers
 */

if (!defined('ABSPATH')) exit;

// Manual advance tournament
add_action('wp_ajax_yuv_manual_advance_tournament', 'yuv_manual_advance_tournament_ajax');

function yuv_manual_advance_tournament_ajax() {
    check_ajax_referer('yuv_manual_advance', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Nemate dozvolu']);
    }

    $tournament_id = intval($_POST['tournament_id']);
    if (!$tournament_id) {
        wp_send_json_error(['message' => 'Nevažeći ID turnira']);
    }

    $manager = new YUV_Tournament_Manager();
    $results = $manager->advance_tournament($tournament_id);

    if (empty($results)) {
        wp_send_json_success([
            'message' => 'Nema mečeva za napredovanje (svi su ili aktivni ili već završeni)',
            'results' => []
        ]);
    }

    $messages = array_map(function($r) {
        return $r['message'] ?? 'Unknown result';
    }, $results);

    wp_send_json_success([
        'message' => 'Uspešno napredovanje! ' . count($results) . ' meč(eva) završeno.',
        'details' => implode(', ', $messages),
        'results' => $results,
    ]);
}

// Search voting items for tournament candidates
add_action('wp_ajax_yuv_search_voting_items', 'yuv_search_voting_items_ajax');

function yuv_search_voting_items_ajax() {
    check_ajax_referer('yuv_search_items', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Nemate dozvolu']);
    }

    $query = sanitize_text_field($_POST['query'] ?? '');
    if (strlen($query) < 2) {
        wp_send_json_error(['message' => 'Pretraga mora biti duža od 2 karaktera']);
    }

    $args = [
        'post_type' => 'voting_items',
        'post_status' => 'publish',
        'posts_per_page' => 10,
        's' => $query,
    ];

    $items_query = new WP_Query($args);
    $results = [];

    if ($items_query->have_posts()) {
        while ($items_query->have_posts()) {
            $items_query->the_post();
            $post_id = get_the_ID();
            
            // Get featured image
            $image_id = get_post_thumbnail_id($post_id);
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
            
            // Get description (short description meta or excerpt)
            $description = get_post_meta($post_id, '_short_description', true);
            if (empty($description)) {
                $description = get_the_excerpt();
            }
            
            $results[] = [
                'id' => $post_id,
                'name' => get_the_title(),
                'description' => wp_trim_words($description, 15),
                'image_id' => $image_id,
                'image' => $image_url,
            ];
        }
        wp_reset_postdata();
    }

    wp_send_json_success($results);
}
