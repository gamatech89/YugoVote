<?php
/**
 * Tournament Module Initialization
 * Loads all tournament-related files
 */

if (!defined('ABSPATH')) exit;

$tournament_path = get_stylesheet_directory() . '/inc/voting/tournament/';

// Load tournament CPT
require_once $tournament_path . 'cpts/cpt-tournament.php';

// Load tournament meta boxes
require_once $tournament_path . 'meta/tournament-meta.php';

// Load tournament manager class
require_once $tournament_path . 'classes/class-tournament-manager.php';

// Load AJAX handlers
require_once $tournament_path . 'api/tournament-ajax.php';

// Load cron jobs
require_once $tournament_path . 'tournament-cron.php';

// Load shortcodes
require_once $tournament_path . 'shortcodes/bracket-shortcode.php';
