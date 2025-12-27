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

    // Enqueue media uploader
    wp_enqueue_media();

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
            .yuv-contestant-item { 
                display: grid;
                grid-template-columns: 120px 1fr;
                gap: 15px;
                margin-bottom: 20px;
                padding: 15px;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 8px;
                position: relative;
            }
            .yuv-contestant-image-col { display: flex; flex-direction: column; gap: 8px; }
            .yuv-contestant-image-preview { 
                width: 100px;
                height: 100px;
                border: 2px dashed #ddd;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #fff;
                overflow: hidden;
            }
            .yuv-contestant-image-preview img { 
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .yuv-contestant-image-preview.empty {
                color: #999;
                font-size: 12px;
                text-align: center;
            }
            .yuv-select-image-btn { 
                width: 100%;
                padding: 6px 12px;
                font-size: 13px;
            }
            .yuv-contestant-fields { display: flex; flex-direction: column; gap: 10px; }
            .yuv-contestant-fields input[type="text"],
            .yuv-contestant-fields textarea { 
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .yuv-contestant-fields textarea {
                min-height: 60px;
                resize: vertical;
            }
            .yuv-contestant-remove { 
                position: absolute;
                top: 10px;
                right: 10px;
                color: #dc3545;
                cursor: pointer;
                font-size: 20px;
                font-weight: bold;
                line-height: 1;
                padding: 5px;
            }
            .yuv-contestant-remove:hover { color: #a02622; }
            .yuv-bracket-status { padding: 15px; background: #e7f3ff; border-left: 4px solid #2271b1; margin-bottom: 20px; }
            .yuv-bracket-status.created { background: #d4edda; border-color: #28a745; }
            .yuv-bracket-list { padding: 10px 0; }
            .yuv-bracket-link { display: inline-block; padding: 5px 10px; background: #2271b1; color: white; text-decoration: none; border-radius: 3px; margin-right: 5px; margin-bottom: 5px; }
            .yuv-manual-advance { margin-top: 15px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; }
            .yuv-search-wrapper { position: relative; margin-bottom: 15px; }
            .yuv-search-input { 
                width: 100%;
                padding: 10px;
                border: 2px solid #2271b1;
                border-radius: 6px;
                font-size: 14px;
            }
            .yuv-search-results {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                max-height: 300px;
                overflow-y: auto;
                z-index: 1000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                display: none;
            }
            .yuv-search-result-item {
                padding: 12px;
                border-bottom: 1px solid #f0f0f0;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .yuv-search-result-item:hover {
                background: #f5f5f5;
            }
            .yuv-search-result-image {
                width: 50px;
                height: 50px;
                object-fit: cover;
                border-radius: 4px;
            }
            .yuv-search-result-info h4 {
                margin: 0 0 4px 0;
                font-size: 14px;
            }
            .yuv-search-result-info p {
                margin: 0;
                font-size: 12px;
                color: #666;
            }
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
            
            <?php if (!$bracket_created): ?>
                <!-- Search Existing Voting Items -->
                <div class="yuv-search-wrapper">
                    <input type="text" 
                           id="yuv-candidate-search" 
                           class="yuv-search-input" 
                           placeholder="🔍 Pretraži postojeće kandidate (voting_items)..."
                           autocomplete="off">
                    <div id="yuv-search-results" class="yuv-search-results"></div>
                </div>
            <?php endif; ?>

            <ul class="yuv-contestant-list" id="yuv-contestant-list">
                <?php 
                // Render existing contestants
                if (!empty($contestants) && is_array($contestants)) {
                    foreach ($contestants as $index => $contestant) {
                        $name = $contestant['name'] ?? '';
                        $description = $contestant['description'] ?? '';
                        $image_id = $contestant['image_id'] ?? '';
                        $image_url = $contestant['image_url'] ?? '';
                        
                        // Get image URL from attachment ID if available
                        if ($image_id && !$image_url) {
                            $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                        }

                        yuv_render_contestant_row($index, $name, $description, $image_id, $image_url, $bracket_created);
                    }
                }
                
                // Fill remaining slots to 8
                $count = is_array($contestants) ? count($contestants) : 0;
                for ($i = $count; $i < 8; $i++) {
                    yuv_render_contestant_row($i, '', '', '', '', $bracket_created);
                }
                ?>
            </ul>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        let searchTimer;
        const $searchInput = $('#yuv-candidate-search');
        const $searchResults = $('#yuv-search-results');

        // Search candidates (debounced)
        $searchInput.on('input', function() {
            clearTimeout(searchTimer);
            const query = $(this).val().trim();

            if (query.length < 2) {
                $searchResults.hide().empty();
                return;
            }

            searchTimer = setTimeout(function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'yuv_search_voting_items',
                        query: query,
                        nonce: '<?php echo wp_create_nonce('yuv_search_items'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data.length > 0) {
                            renderSearchResults(response.data);
                        } else {
                            $searchResults.html('<div style="padding: 12px; color: #666;">Nema rezultata</div>').show();
                        }
                    }
                });
            }, 300);
        });

        // Render search results
        function renderSearchResults(items) {
            let html = '';
            items.forEach(item => {
                html += `
                    <div class="yuv-search-result-item" data-item='${JSON.stringify(item)}'>
                        ${item.image ? `<img src="${item.image}" class="yuv-search-result-image">` : ''}
                        <div class="yuv-search-result-info">
                            <h4>${item.name}</h4>
                            ${item.description ? `<p>${item.description}</p>` : ''}
                        </div>
                    </div>
                `;
            });
            $searchResults.html(html).show();
        }

        // Select item from search results
        $searchResults.on('click', '.yuv-search-result-item', function() {
            const item = $(this).data('item');
            
            // Find first empty row
            const $emptyRow = $('.yuv-contestant-item').filter(function() {
                return $(this).find('input[name*="[name]"]').val() === '';
            }).first();

            if ($emptyRow.length === 0) {
                alert('Svi slotovi su popunjeni! Obrišite neki postojeći kandidat.');
                return;
            }

            // Auto-fill fields
            $emptyRow.find('input[name*="[name]"]').val(item.name);
            $emptyRow.find('textarea[name*="[description]"]').val(item.description || '');
            
            if (item.image_id) {
                $emptyRow.find('input[name*="[image_id]"]').val(item.image_id);
                $emptyRow.find('input[name*="[image_url]"]').val(item.image);
                $emptyRow.find('.yuv-contestant-image-preview')
                    .removeClass('empty')
                    .html(`<img src="${item.image}" alt="${item.name}">`);
            }

            // Clear search
            $searchInput.val('');
            $searchResults.hide().empty();
        });

        // Close search results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.yuv-search-wrapper').length) {
                $searchResults.hide();
            }
        });

        // Media uploader
        $('.yuv-contestant-list').on('click', '.yuv-select-image-btn', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $row = $button.closest('.yuv-contestant-item');
            const $preview = $row.find('.yuv-contestant-image-preview');
            const $imageIdInput = $row.find('input[name*="[image_id]"]');
            const $imageUrlInput = $row.find('input[name*="[image_url]"]');

            const frame = wp.media({
                title: 'Izaberite sliku kandidata',
                button: { text: 'Koristi ovu sliku' },
                multiple: false
            });

            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                
                $imageIdInput.val(attachment.id);
                $imageUrlInput.val(attachment.url);
                $preview.removeClass('empty').html(
                    `<img src="${attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url}" alt="Candidate">`
                );
            });

            frame.open();
        });

        // Remove contestant
        $('.yuv-contestant-list').on('click', '.yuv-contestant-remove', function() {
            if (confirm('Obrisati takmičara?')) {
                const $row = $(this).closest('.yuv-contestant-item');
                $row.find('input, textarea').val('');
                $row.find('.yuv-contestant-image-preview').addClass('empty').html('Nema slike');
            }
        });

        // Manual advance button
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

/**
 * Render single contestant row
 */
function yuv_render_contestant_row($index, $name, $description, $image_id, $image_url, $readonly = false) {
    ?>
    <li class="yuv-contestant-item" data-index="<?php echo $index; ?>">
        <div class="yuv-contestant-image-col">
            <div class="yuv-contestant-image-preview <?php echo empty($image_url) ? 'empty' : ''; ?>">
                <?php if ($image_url): ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($name); ?>">
                <?php else: ?>
                    Nema slike
                <?php endif; ?>
            </div>
            <?php if (!$readonly): ?>
                <button type="button" class="button yuv-select-image-btn">
                    <?php echo empty($image_url) ? 'Izaberi Sliku' : 'Promeni Sliku'; ?>
                </button>
            <?php endif; ?>
            <input type="hidden" 
                   name="yuv_contestants[<?php echo $index; ?>][image_id]" 
                   value="<?php echo esc_attr($image_id); ?>">
            <input type="hidden" 
                   name="yuv_contestants[<?php echo $index; ?>][image_url]" 
                   value="<?php echo esc_url($image_url); ?>">
        </div>

        <div class="yuv-contestant-fields">
            <input type="text" 
                   name="yuv_contestants[<?php echo $index; ?>][name]" 
                   placeholder="Ime takmičara" 
                   value="<?php echo esc_attr($name); ?>"
                   <?php echo $readonly ? 'readonly' : ''; ?>>
            
            <textarea 
                name="yuv_contestants[<?php echo $index; ?>][description]" 
                placeholder="Kratak opis / biografija"
                <?php echo $readonly ? 'readonly' : ''; ?>><?php echo esc_textarea($description); ?></textarea>
        </div>

        <?php if (!$readonly): ?>
            <span class="yuv-contestant-remove" title="Obriši kandidata">×</span>
        <?php endif; ?>
    </li>
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
                    'description' => sanitize_textarea_field($contestant['description'] ?? ''),
                    'image_id' => intval($contestant['image_id'] ?? 0),
                    'image_url' => esc_url_raw($contestant['image_url'] ?? ''),
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
