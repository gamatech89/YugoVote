<?php
/**
 * Tournament Bracket Shortcode
 * Usage: 
 * - [yuv_auto_bracket id="123"] - Display specific tournament bracket
 * - [yuv_active_tournament] - Display currently active tournament (for home page)
 * - [yuv_tournament_archive] - Display list of all past tournaments (for archive page)
 */

if (!defined('ABSPATH')) exit;

function yuv_auto_bracket_shortcode($atts) {
    $atts = shortcode_atts(['id' => ''], $atts, 'yuv_auto_bracket');
    
    $tournament_id = intval($atts['id']);
    if (!$tournament_id) {
        return '<p>Nevažeći ID turnira.</p>';
    }

    $bracket_lists = get_post_meta($tournament_id, '_yuv_bracket_lists', true);
    if (empty($bracket_lists)) {
        return '<p>Bracket još nije kreiran za ovaj turnir.</p>';
    }

    ob_start();
    ?>
    <div class="yuv-tournament-bracket">
        <style>
            .yuv-tournament-bracket { max-width: 1200px; margin: 40px auto; }
            .yuv-bracket-round { margin-bottom: 40px; }
            .yuv-bracket-round h3 { text-align: center; font-size: 24px; margin-bottom: 20px; }
            .yuv-bracket-matches { display: grid; gap: 20px; }
            .yuv-bracket-matches.qf { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
            .yuv-bracket-matches.sf { grid-template-columns: repeat(2, 1fr); max-width: 800px; margin: 0 auto; }
            .yuv-bracket-matches.final { max-width: 400px; margin: 0 auto; }
            .yuv-match-card { background: white; border: 2px solid #ddd; border-radius: 12px; padding: 20px; transition: all 0.3s; }
            .yuv-match-card.completed { border-color: #28a745; }
            .yuv-match-card.active { border-color: #2271b1; box-shadow: 0 4px 12px rgba(34, 113, 177, 0.3); }
            .yuv-match-card.pending { opacity: 0.6; }
            .yuv-match-title { font-weight: 700; font-size: 16px; margin-bottom: 15px; text-align: center; }
            .yuv-match-items { display: flex; flex-direction: column; gap: 12px; }
            .yuv-match-item { display: flex; align-items: center; gap: 12px; padding: 10px; background: #f9f9f9; border-radius: 8px; }
            .yuv-match-item.winner { background: #d4edda; border: 2px solid #28a745; }
            .yuv-match-item img { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; }
            .yuv-match-item-name { flex: 1; font-weight: 600; }
            .yuv-match-item-score { font-size: 18px; font-weight: 700; }
            .yuv-match-status { text-align: center; margin-top: 12px; font-size: 13px; }
            .yuv-match-status.completed { color: #28a745; }
            .yuv-match-status.active { color: #2271b1; }
            .yuv-tie-icon { display: inline-block; margin-left: 5px; font-size: 16px; }
        </style>

        <!-- Quarterfinals -->
        <div class="yuv-bracket-round">
            <h3>⚔️ Četvrtfinale</h3>
            <div class="yuv-bracket-matches qf">
                <?php foreach ($bracket_lists['qf'] as $list_id): ?>
                    <?php echo yuv_render_match_card($list_id); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Semifinals -->
        <div class="yuv-bracket-round">
            <h3>🏆 Polufinale</h3>
            <div class="yuv-bracket-matches sf">
                <?php foreach ($bracket_lists['sf'] as $list_id): ?>
                    <?php echo yuv_render_match_card($list_id); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Final -->
        <div class="yuv-bracket-round">
            <h3>👑 FINALE</h3>
            <div class="yuv-bracket-matches final">
                <?php echo yuv_render_match_card($bracket_lists['final'][0]); ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('yuv_auto_bracket', 'yuv_auto_bracket_shortcode');

/**
 * Active Tournament Shortcode (for home page)
 * Displays the currently active tournament
 * Safe for use in Elementor - doesn't rely on global $post
 */
function yuv_active_tournament_shortcode($atts) {
    // Save and clear global post to avoid conflicts
    global $post;
    $original_post = $post;
    
    try {
        // Query for active tournaments (published, bracket created, not completed)
        $args = array(
            'post_type' => 'yuv_tournament',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_yuv_bracket_created',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        );

        $query = new WP_Query($args);

        if (!$query->have_posts()) {
            wp_reset_postdata();
            $post = $original_post; // Restore original post
            return '<div class="yuv-no-tournament"><p>Trenutno nema aktivnih turnira.</p></div>';
        }

        $tournament = $query->posts[0];
        $tournament_id = $tournament->ID;
        
        // Check if tournament is actually still active (has uncompleted matches)
        $bracket_lists = get_post_meta($tournament_id, '_yuv_bracket_lists', true);
        $winner_id = get_post_meta($tournament_id, '_yuv_winner_id', true);
        
        wp_reset_postdata();
        $post = $original_post; // Restore original post
        
        if ($winner_id) {
            return '<div class="yuv-no-tournament"><p>Trenutno nema aktivnih turnira.</p></div>';
        }

        // Build output without relying on global post context
        ob_start();
        ?>
        <div class="yuv-active-tournament-wrapper">
            <div class="yuv-tournament-header">
                <h2 class="yuv-tournament-title"><?php echo esc_html($tournament->post_title); ?></h2>
                <?php if ($tournament->post_content): ?>
                    <div class="yuv-tournament-description">
                        <?php echo wpautop($tournament->post_content); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php 
            // Directly render bracket without shortcode to avoid context issues
            if (!empty($bracket_lists)) {
                echo yuv_render_tournament_bracket($tournament_id, $bracket_lists);
            }
            ?>
        </div>
        <style>
            .yuv-active-tournament-wrapper { margin: 40px 0; }
            .yuv-tournament-header { text-align: center; margin-bottom: 40px; }
            .yuv-tournament-title { font-size: 32px; font-weight: 700; margin-bottom: 15px; }
            .yuv-tournament-description { font-size: 16px; color: #666; max-width: 800px; margin: 0 auto; }
            .yuv-no-tournament { text-align: center; padding: 60px 20px; background: #f5f5f5; border-radius: 12px; }
            .yuv-no-tournament p { font-size: 18px; color: #666; margin: 0; }
        </style>
        <?php
        return ob_get_clean();
    } catch (Exception $e) {
        $post = $original_post; // Restore original post on error
        error_log('YUV Active Tournament Error: ' . $e->getMessage());
        return '<div class="yuv-no-tournament"><p>Greška pri učitavanju turnira.</p></div>';
    }
}
add_shortcode('yuv_active_tournament', 'yuv_active_tournament_shortcode');

/**
 * Helper function to render bracket HTML
 */
function yuv_render_tournament_bracket($tournament_id, $bracket_lists) {
    ob_start();
    ?>
    <div class="yuv-tournament-bracket">
        <style>
            .yuv-tournament-bracket { max-width: 1200px; margin: 40px auto; }
            .yuv-bracket-round { margin-bottom: 40px; }
            .yuv-bracket-round h3 { text-align: center; font-size: 24px; margin-bottom: 20px; }
            .yuv-bracket-matches { display: grid; gap: 20px; }
            .yuv-bracket-matches.qf { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); }
            .yuv-bracket-matches.sf { grid-template-columns: repeat(2, 1fr); max-width: 800px; margin: 0 auto; }
            .yuv-bracket-matches.final { max-width: 400px; margin: 0 auto; }
            .yuv-match-card { background: white; border: 2px solid #ddd; border-radius: 12px; padding: 20px; transition: all 0.3s; }
            .yuv-match-card.completed { border-color: #28a745; }
            .yuv-match-card.active { border-color: #2271b1; box-shadow: 0 4px 12px rgba(34, 113, 177, 0.3); }
            .yuv-match-card.pending { opacity: 0.6; }
            .yuv-match-title { font-weight: 700; font-size: 16px; margin-bottom: 15px; text-align: center; }
            .yuv-match-items { display: flex; flex-direction: column; gap: 12px; }
            .yuv-match-item { display: flex; align-items: center; gap: 12px; padding: 10px; background: #f9f9f9; border-radius: 8px; }
            .yuv-match-item.winner { background: #d4edda; border: 2px solid #28a745; }
            .yuv-match-item img { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; }
            .yuv-match-item-name { flex: 1; font-weight: 600; }
            .yuv-match-item-score { font-size: 18px; font-weight: 700; }
            .yuv-match-status { text-align: center; margin-top: 12px; font-size: 13px; }
            .yuv-match-status.completed { color: #28a745; }
            .yuv-match-status.active { color: #2271b1; }
            .yuv-tie-icon { display: inline-block; margin-left: 5px; font-size: 16px; }
        </style>

        <!-- Quarterfinals -->
        <div class="yuv-bracket-round">
            <h3>⚔️ Četvrtfinale</h3>
            <div class="yuv-bracket-matches qf">
                <?php foreach ($bracket_lists['qf'] as $list_id): ?>
                    <?php echo yuv_render_match_card($list_id); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Semifinals -->
        <div class="yuv-bracket-round">
            <h3>🏆 Polufinale</h3>
            <div class="yuv-bracket-matches sf">
                <?php foreach ($bracket_lists['sf'] as $list_id): ?>
                    <?php echo yuv_render_match_card($list_id); ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Final -->
        <div class="yuv-bracket-round">
            <h3>👑 FINALE</h3>
            <div class="yuv-bracket-matches final">
                <?php echo yuv_render_match_card($bracket_lists['final'][0]); ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
        .yuv-tournament-description { font-size: 16px; color: #666; max-width: 800px; margin: 0 auto; }
        .yuv-no-tournament { text-align: center; padding: 60px 20px; background: #f5f5f5; border-radius: 12px; }
        .yuv-no-tournament p { font-size: 18px; color: #666; margin: 0; }
    </style>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('yuv_active_tournament', 'yuv_active_tournament_shortcode');

/**
 * Tournament Archive Shortcode
 * Displays list of all past tournaments
 */
function yuv_tournament_archive_shortcode($atts) {
    $atts = shortcode_atts(['per_page' => 12], $atts, 'yuv_tournament_archive');
    
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    
    $args = array(
        'post_type' => 'yuv_tournament',
        'post_status' => 'publish',
        'posts_per_page' => intval($atts['per_page']),
        'paged' => $paged,
        'orderby' => 'date',
        'order' => 'DESC'
    );

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return '<div class="yuv-no-tournaments"><p>Nema dostupnih turnira.</p></div>';
    }

    ob_start();
    ?>
    <div class="yuv-tournament-archive">
        <style>
            .yuv-tournament-archive { max-width: 1200px; margin: 0 auto; }
            .yuv-tournament-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; margin-bottom: 40px; }
            .yuv-tournament-card { background: white; border: 2px solid #ddd; border-radius: 12px; padding: 25px; transition: all 0.3s; cursor: pointer; }
            .yuv-tournament-card:hover { border-color: #2271b1; box-shadow: 0 6px 20px rgba(34, 113, 177, 0.2); transform: translateY(-5px); }
            .yuv-tournament-card.active { border-color: #28a745; background: linear-gradient(135deg, #f0f9f4 0%, white 100%); }
            .yuv-tournament-card.completed { border-color: #6c757d; opacity: 0.85; }
            .yuv-tournament-card-header { margin-bottom: 15px; }
            .yuv-tournament-card-title { font-size: 20px; font-weight: 700; margin-bottom: 8px; color: #333; }
            .yuv-tournament-card-date { font-size: 13px; color: #666; }
            .yuv-tournament-card-status { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 15px; }
            .yuv-tournament-card-status.active { background: #28a745; color: white; }
            .yuv-tournament-card-status.completed { background: #6c757d; color: white; }
            .yuv-tournament-card-winner { margin-top: 15px; padding-top: 15px; border-top: 2px solid #e9ecef; }
            .yuv-tournament-card-winner-label { font-size: 12px; color: #666; margin-bottom: 8px; text-transform: uppercase; }
            .yuv-tournament-card-winner-info { display: flex; align-items: center; gap: 12px; }
            .yuv-tournament-card-winner-info img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
            .yuv-tournament-card-winner-name { font-weight: 700; font-size: 16px; }
            .yuv-tournament-card-meta { font-size: 13px; color: #666; margin-top: 12px; }
            .yuv-tournament-archive-pagination { text-align: center; margin-top: 40px; }
        </style>

        <div class="yuv-tournament-grid">
            <?php while ($query->have_posts()): $query->the_post();
                $tournament_id = get_the_ID();
                $bracket_created = get_post_meta($tournament_id, '_yuv_bracket_created', true);
                $winner_id = get_post_meta($tournament_id, '_yuv_winner_id', true);
                $start_date = get_post_meta($tournament_id, '_yuv_start_date', true);
                $bracket_lists = get_post_meta($tournament_id, '_yuv_bracket_lists', true);
                
                $is_active = $bracket_created && !$winner_id;
                $is_completed = $bracket_created && $winner_id;
                
                $status_class = $is_active ? 'active' : ($is_completed ? 'completed' : '');
                $status_text = $is_active ? '🔴 U toku' : ($is_completed ? '✅ Završeno' : '⏳ Predstoji');
            ?>
                <div class="yuv-tournament-card <?php echo esc_attr($status_class); ?>" 
                     onclick="window.location.href='<?php echo get_permalink(); ?>'">
                    
                    <div class="yuv-tournament-card-header">
                        <h3 class="yuv-tournament-card-title"><?php the_title(); ?></h3>
                        <?php if ($start_date): ?>
                            <div class="yuv-tournament-card-date">
                                📅 <?php echo date('d.m.Y', $start_date); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <span class="yuv-tournament-card-status <?php echo $is_active ? 'active' : ($is_completed ? 'completed' : ''); ?>">
                        <?php echo esc_html($status_text); ?>
                    </span>

                    <?php if ($is_completed && $winner_id): 
                        $winner = get_post($winner_id);
                        $winner_image = get_post_meta($winner_id, '_custom_image_url', true);
                    ?>
                        <div class="yuv-tournament-card-winner">
                            <div class="yuv-tournament-card-winner-label">👑 Pobednik</div>
                            <div class="yuv-tournament-card-winner-info">
                                <?php if ($winner_image): ?>
                                    <img src="<?php echo esc_url($winner_image); ?>" 
                                         alt="<?php echo esc_attr($winner->post_title); ?>">
                                <?php endif; ?>
                                <span class="yuv-tournament-card-winner-name">
                                    <?php echo esc_html($winner->post_title); ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($bracket_created && !empty($bracket_lists)): 
                        $total_matches = count($bracket_lists['qf']) + count($bracket_lists['sf']) + count($bracket_lists['final']);
                        $completed_matches = 0;
                        foreach (['qf', 'sf', 'final'] as $stage) {
                            foreach ($bracket_lists[$stage] as $list_id) {
                                if (get_post_meta($list_id, '_yuv_match_completed', true)) {
                                    $completed_matches++;
                                }
                            }
                        }
                    ?>
                        <div class="yuv-tournament-card-meta">
                            ⚔️ Mečeva: <?php echo $completed_matches; ?>/<?php echo $total_matches; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if ($query->max_num_pages > 1): ?>
            <div class="yuv-tournament-archive-pagination">
                <?php
                echo paginate_links(array(
                    'total' => $query->max_num_pages,
                    'current' => $paged,
                    'prev_text' => '← Prethodna',
                    'next_text' => 'Sledeća →'
                ));
                ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('yuv_tournament_archive', 'yuv_tournament_archive_shortcode');

/**
 * Render single match card
 */
function yuv_render_match_card($list_id) {
    global $wpdb;

    $post = get_post($list_id);
    
    if (!$post) {
        return '<div class="yuv-match-card"><p>Meč nije pronađen</p></div>';
    }
    
    $completed = get_post_meta($list_id, '_yuv_match_completed', true);
    $winner_id = get_post_meta($list_id, '_yuv_winner_id', true);
    $tie_breaker = get_post_meta($list_id, '_yuv_tie_breaker_used', true);
    $final_scores = get_post_meta($list_id, '_yuv_final_scores', true) ?: [];
    $items = get_post_meta($list_id, '_voting_items', true) ?: [];
    $end_time = get_post_meta($list_id, '_yuv_end_time', true);

    $status_class = $post->post_status === 'publish' 
        ? ($completed ? 'completed' : 'active') 
        : 'pending';

    ob_start();
    ?>
    <div class="yuv-match-card <?php echo esc_attr($status_class); ?>">
        <div class="yuv-match-title">
            <?php echo esc_html($post->post_title); ?>
        </div>

        <div class="yuv-match-items">
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $item_id): 
                    $item_post = get_post($item_id);
                    if (!$item_post) continue;

                    $is_winner = ($completed && $winner_id == $item_id);
                    $score = $final_scores[$item_id] ?? 0;
                    $image = get_post_meta($item_id, '_custom_image_url', true);
                ?>
                    <div class="yuv-match-item <?php echo $is_winner ? 'winner' : ''; ?>">
                        <?php if ($image): ?>
                            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($item_post->post_title); ?>">
                        <?php endif; ?>
                        <span class="yuv-match-item-name"><?php echo esc_html($item_post->post_title); ?></span>
                        <?php if ($completed): ?>
                            <span class="yuv-match-item-score"><?php echo esc_html($score); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="yuv-match-item">
                    <span class="yuv-match-item-name">TBD</span>
                </div>
                <div class="yuv-match-item">
                    <span class="yuv-match-item-name">TBD</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="yuv-match-status <?php echo esc_attr($status_class); ?>">
            <?php if ($completed): ?>
                ✅ Završeno
                <?php if ($tie_breaker): ?>
                    <span class="yuv-tie-icon" title="Pobednik određen izvlačenjem">🎲</span>
                <?php endif; ?>
            <?php elseif ($post->post_status === 'publish'): ?>
                🔴 U toku - Završava: <?php echo date('d.m.Y H:i', $end_time); ?>
            <?php else: ?>
                ⏳ Čeka pobednike
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
