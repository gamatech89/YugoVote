<?php
/**
 * Tournament Metabox
 * Configuration for tournament rounds and contestants
 */

if (!defined('ABSPATH')) exit;

function yuv_add_tournament_metabox() {
    add_meta_box(
        'yuv_tournament_settings',
        'Podešavanja Turnira',
        'yuv_tournament_metabox_callback',
        'yuv_tournament',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'yuv_add_tournament_metabox');

function yuv_tournament_metabox_callback($post) {
    wp_nonce_field('yuv_tournament_meta_save', 'yuv_tournament_meta_nonce');

    // Get existing values
    $start_date = get_post_meta($post->ID, '_yuv_start_date', true);
    $qf_duration = get_post_meta($post->ID, '_yuv_round_duration_qf', true) ?: 24;
    $sf_duration = get_post_meta($post->ID, '_yuv_round_duration_sf', true) ?: 24;
    $final_duration = get_post_meta($post->ID, '_yuv_round_duration_final', true) ?: 48;
    $contestants = get_post_meta($post->ID, '_yuv_contestants', true) ?: [];
    $bracket_created = get_post_meta($post->ID, '_yuv_bracket_created', true);
    $bracket_lists = get_post_meta($post->ID, '_yuv_bracket_lists', true) ?: [];

    ?>
    <div class="yuv-tournament-meta">
        <style>
            .yuv-tournament-meta { padding: 15px; }
            .yuv-meta-row { margin-bottom: 20px; }
            .yuv-meta-row label { display: block; font-weight: 600; margin-bottom: 8px; }
            .yuv-meta-row input[type="datetime-local"],
            .yuv-meta-row input[type="number"],
            .yuv-meta-row input[type="text"] { width: 100%; max-width: 400px; padding: 8px; }
            .yuv-contestant-list { list-style: none; padding: 0; margin: 10px 0; }
            .yuv-contestant-item { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; padding: 10px; background: #f9f9f9; border-radius: 5px; }
            .yuv-contestant-item input { flex: 1; }
            .yuv-contestant-image { width: 60px; height: 60px; object-fit: cover; border-radius: 5px; }
            .yuv-contestant-remove { color: #dc3545; cursor: pointer; font-weight: bold; }
            .yuv-bracket-status { padding: 15px; background: #e7f3ff; border-left: 4px solid #2271b1; margin-bottom: 20px; }
            .yuv-bracket-status.created { background: #d4edda; border-color: #28a745; }
            .yuv-bracket-list { padding: 10px 0; }
            .yuv-bracket-link { display: inline-block; padding: 5px 10px; background: #2271b1; color: white; text-decoration: none; border-radius: 3px; margin-right: 5px; margin-bottom: 5px; }
            .yuv-manual-advance { margin-top: 15px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; }
        </style>

        <?php if ($bracket_created && !empty($bracket_lists)): ?>
            <div class="yuv-bracket-status created">
                <h3>✅ Bracket kreiran</h3>
                <p><strong>Turnir je u toku!</strong></p>
                <div class="yuv-bracket-list">
                    <h4>Četvrtfinale:</h4>
                    <?php foreach ($bracket_lists['qf'] ?? [] as $list_id): ?>
                        <a href="<?php echo get_edit_post_link($list_id); ?>" class="yuv-bracket-link" target="_blank">
                            QF Match <?php echo get_post_meta($list_id, '_yuv_match_number', true); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="yuv-bracket-list">
                    <h4>Polufinale:</h4>
                    <?php foreach ($bracket_lists['sf'] ?? [] as $list_id): ?>
                        <a href="<?php echo get_edit_post_link($list_id); ?>" class="yuv-bracket-link" target="_blank">
                            SF Match <?php echo get_post_meta($list_id, '_yuv_match_number', true); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="yuv-bracket-list">
                    <h4>Finale:</h4>
                    <?php foreach ($bracket_lists['final'] ?? [] as $list_id): ?>
                        <a href="<?php echo get_edit_post_link($list_id); ?>" class="yuv-bracket-link" target="_blank">
                            FINALE
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="yuv-manual-advance">
                    <h4>⚙️ Ručna kontrola</h4>
                    <button type="button" id="yuv-manual-advance-btn" class="button button-secondary">
                        Pokreni Napredovanje Odmah (Bypass Cron)
                    </button>
                    <p class="description">Ovo će proveriti sve mečeve i pomeriti pobednike u sledeće runde.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="yuv-bracket-status">
                <h3>⚠️ Bracket još nije kreiran</h3>
                <p>Nakon što dodate 8 takmičara i datum početka, kliknite "Publish" ili "Update" da bi se automatski kreirao bracket.</p>
            </div>
        <?php endif; ?>

        <!-- Start Date -->
        <div class="yuv-meta-row">
            <label for="yuv_start_date">📅 Datum početka turnira:</label>
            <input type="datetime-local" 
                   id="yuv_start_date" 
                   name="yuv_start_date" 
                   value="<?php echo esc_attr($start_date); ?>"
                   <?php echo $bracket_created ? 'readonly' : ''; ?>>
            <?php if ($bracket_created): ?>
                <p class="description">Datum se ne može menjati nakon kreiranja bracket-a.</p>
            <?php endif; ?>
        </div>

        <!-- Round Durations -->
        <div class="yuv-meta-row">
            <label for="yuv_round_duration_qf">⏱️ Trajanje četvrtfinala (sati):</label>
            <input type="number" 
                   id="yuv_round_duration_qf" 
                   name="yuv_round_duration_qf" 
                   value="<?php echo esc_attr($qf_duration); ?>" 
                   min="1"
                   <?php echo $bracket_created ? 'readonly' : ''; ?>>
        </div>

        <div class="yuv-meta-row">
            <label for="yuv_round_duration_sf">⏱️ Trajanje polufinala (sati):</label>
            <input type="number" 
                   id="yuv_round_duration_sf" 
                   name="yuv_round_duration_sf" 
                   value="<?php echo esc_attr($sf_duration); ?>" 
                   min="1"
                   <?php echo $bracket_created ? 'readonly' : ''; ?>>
        </div>

        <div class="yuv-meta-row">
            <label for="yuv_round_duration_final">⏱️ Trajanje finala (sati):</label>
            <input type="number" 
                   id="yuv_round_duration_final" 
                   name="yuv_round_duration_final" 
                   value="<?php echo esc_attr($final_duration); ?>" 
                   min="1"
                   <?php echo $bracket_created ? 'readonly' : ''; ?>>
        </div>

        <!-- Contestants -->
        <div class="yuv-meta-row">
            <label>👥 Takmičari (8 kandidata):</label>
            <ul class="yuv-contestant-list" id="yuv-contestant-list">
                <?php 
                if (!empty($contestants) && is_array($contestants)) {
                    foreach ($contestants as $index => $contestant) {
                        $name = $contestant['name'] ?? '';
                        $image = $contestant['image'] ?? '';
                        ?>
                        <li class="yuv-contestant-item" data-index="<?php echo $index; ?>">
                            <?php if ($image): ?>
                                <img src="<?php echo esc_url($image); ?>" class="yuv-contestant-image">
                            <?php endif; ?>
                            <input type="text" 
                                   name="yuv_contestants[<?php echo $index; ?>][name]" 
                                   placeholder="Ime takmičara" 
                                   value="<?php echo esc_attr($name); ?>"
                                   <?php echo $bracket_created ? 'readonly' : ''; ?>>
                            <input type="url" 
                                   name="yuv_contestants[<?php echo $index; ?>][image]" 
                                   placeholder="URL slike" 
                                   value="<?php echo esc_url($image); ?>"
                                   <?php echo $bracket_created ? 'readonly' : ''; ?>>
                            <?php if (!$bracket_created): ?>
                                <span class="yuv-contestant-remove" onclick="removeContestant(this)">✖</span>
                            <?php endif; ?>
                        </li>
                        <?php
                    }
                }
                
                // Fill remaining slots to 8
                $count = is_array($contestants) ? count($contestants) : 0;
                for ($i = $count; $i < 8; $i++) {
                    ?>
                    <li class="yuv-contestant-item" data-index="<?php echo $i; ?>">
                        <input type="text" 
                               name="yuv_contestants[<?php echo $i; ?>][name]" 
                               placeholder="Ime takmičara"
                               <?php echo $bracket_created ? 'readonly' : ''; ?>>
                        <input type="url" 
                               name="yuv_contestants[<?php echo $i; ?>][image]" 
                               placeholder="URL slike"
                               <?php echo $bracket_created ? 'readonly' : ''; ?>>
                        <?php if (!$bracket_created): ?>
                            <span class="yuv-contestant-remove" onclick="removeContestant(this)">✖</span>
                        <?php endif; ?>
                    </li>
                    <?php
                }
                ?>
            </ul>
        </div>
    </div>

    <script>
    function removeContestant(el) {
        if (confirm('Obrisati takmičara?')) {
            el.closest('.yuv-contestant-item').remove();
        }
    }

    // Manual advance button
    jQuery(document).ready(function($) {
        $('#yuv-manual-advance-btn').on('click', function() {
            const btn = $(this);
            const tournamentId = <?php echo $post->ID; ?>;
            
            if (!confirm('Da li ste sigurni da želite ručno pokrenuti napredovanje?')) {
                return;
            }

            btn.prop('disabled', true).text('Procesiranje...');

            $.post(ajaxurl, {
                action: 'yuv_manual_advance_tournament',
                tournament_id: tournamentId,
                nonce: '<?php echo wp_create_nonce('yuv_manual_advance'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('✅ ' + response.data.message);
                    location.reload();
                } else {
                    alert('❌ Greška: ' + response.data.message);
                    btn.prop('disabled', false).text('Pokreni Napredovanje Odmah');
                }
            });
        });
    });
    </script>
    <?php
}

// Save tournament meta
function yuv_save_tournament_meta($post_id) {
    // Security checks
    if (!isset($_POST['yuv_tournament_meta_nonce']) || 
        !wp_verify_nonce($_POST['yuv_tournament_meta_nonce'], 'yuv_tournament_meta_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (get_post_type($post_id) !== 'yuv_tournament') return;

    // Save start date
    if (isset($_POST['yuv_start_date'])) {
        update_post_meta($post_id, '_yuv_start_date', sanitize_text_field($_POST['yuv_start_date']));
    }

    // Save round durations
    if (isset($_POST['yuv_round_duration_qf'])) {
        update_post_meta($post_id, '_yuv_round_duration_qf', intval($_POST['yuv_round_duration_qf']));
    }
    if (isset($_POST['yuv_round_duration_sf'])) {
        update_post_meta($post_id, '_yuv_round_duration_sf', intval($_POST['yuv_round_duration_sf']));
    }
    if (isset($_POST['yuv_round_duration_final'])) {
        update_post_meta($post_id, '_yuv_round_duration_final', intval($_POST['yuv_round_duration_final']));
    }

    // Save contestants
    if (isset($_POST['yuv_contestants']) && is_array($_POST['yuv_contestants'])) {
        $contestants = [];
        foreach ($_POST['yuv_contestants'] as $contestant) {
            if (!empty($contestant['name'])) {
                $contestants[] = [
                    'name' => sanitize_text_field($contestant['name']),
                    'image' => esc_url_raw($contestant['image'] ?? ''),
                ];
            }
        }
        update_post_meta($post_id, '_yuv_contestants', $contestants);
    }

    // Auto-create bracket if not created and we have 8 contestants
    $bracket_created = get_post_meta($post_id, '_yuv_bracket_created', true);
    if (!$bracket_created) {
        $contestants = get_post_meta($post_id, '_yuv_contestants', true);
        if (is_array($contestants) && count($contestants) === 8) {
            $manager = new YUV_Tournament_Manager();
            $result = $manager->create_bracket($post_id);
            
            if ($result['success']) {
                update_post_meta($post_id, '_yuv_bracket_created', true);
                update_post_meta($post_id, '_yuv_bracket_lists', $result['lists']);
            }
        }
    }
}
add_action('save_post', 'yuv_save_tournament_meta');
