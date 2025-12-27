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
