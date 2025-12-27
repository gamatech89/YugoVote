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

    // Enqueue tournament meta admin script
    wp_enqueue_script(
        'yuv-tournament-meta-admin',
        get_stylesheet_directory_uri() . '/js/admin/admin-tournament-meta.js',
        ['jquery'],
        '1.0.1',
        true
    );

    // Get existing values
    $start_date = get_post_meta($post->ID, '_yuv_start_date', true);
    $tournament_size = (int) get_post_meta($post->ID, '_yuv_tournament_size', true) ?: 16;
    $of_duration = get_post_meta($post->ID, '_yuv_round_duration_of', true) ?: 24;
    $qf_duration = get_post_meta($post->ID, '_yuv_round_duration_qf', true) ?: 24;
    $sf_duration = get_post_meta($post->ID, '_yuv_round_duration_sf', true) ?: 24;
    $final_duration = get_post_meta($post->ID, '_yuv_round_duration_final', true) ?: 24;
    $contestants = get_post_meta($post->ID, '_yuv_contestants', true) ?: [];
    $bracket_created = get_post_meta($post->ID, '_yuv_bracket_created', true);
    $bracket_lists = get_post_meta($post->ID, '_yuv_bracket_lists', true) ?: [];
    $contestants_count = is_array($contestants) ? count($contestants) : 0;
    
    // Get categories for dropdown
    $categories = get_terms([
        'taxonomy' => 'voting_list_category',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ]);

    ?>
    <div class="yuv-tournament-meta">
        <style>
            .yuv-tournament-meta { padding: 20px; max-width: 900px; }
            
            .yuv-meta-section { 
                background: #fff; 
                border: 1px solid #ddd; 
                border-radius: 8px; 
                padding: 20px; 
                margin-bottom: 25px;
            }
            .yuv-meta-section h3 { 
                margin: 0 0 20px 0; 
                padding-bottom: 10px; 
                border-bottom: 2px solid #2271b1;
                color: #2271b1;
                font-size: 18px;
            }
            
            .yuv-meta-row { margin-bottom: 20px; }
            .yuv-meta-row label { 
                display: block; 
                font-weight: 600; 
                margin-bottom: 8px;
                color: #1d2327;
            }
            .yuv-meta-row select,
            .yuv-meta-row input[type="datetime-local"],
            .yuv-meta-row input[type="number"],
            .yuv-meta-row input[type="text"] { 
                width: 100%; 
                max-width: 400px; 
                padding: 10px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                font-size: 14px;
            }
            .yuv-meta-row .description { 
                margin: 8px 0 0 0; 
                color: #646970; 
                font-style: italic;
                font-size: 13px;
            }
            
            .yuv-rounds-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }
            .yuv-round-field {
                display: flex;
                flex-direction: column;
            }
            .yuv-round-field label {
                font-weight: 600;
                margin-bottom: 6px;
                font-size: 13px;
            }
            .yuv-round-field input {
                padding: 8px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
            }
            .yuv-round-field small {
                margin-top: 4px;
                color: #646970;
                font-size: 12px;
            }
            
            .yuv-contestants-section { min-height: 200px; }
            .yuv-contestant-counter {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 6px 12px;
                background: #f0f0f1;
                border-radius: 4px;
                font-weight: 600;
                margin-bottom: 15px;
            }
            .yuv-contestant-counter .count { 
                color: #2271b1; 
                font-size: 18px;
            }
            .yuv-contestant-counter .max { 
                color: #646970; 
            }
            
            .yuv-search-wrapper { 
                position: relative; 
                margin-bottom: 15px;
            }
            .yuv-category-filter {
                margin-bottom: 12px;
            }
            .yuv-category-filter select {
                width: 100%;
                max-width: 300px;
                padding: 8px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
            }
            .yuv-search-input { 
                width: 100%;
                padding: 12px 15px;
                border: 2px solid #2271b1;
                border-radius: 6px;
                font-size: 14px;
                transition: box-shadow 0.2s;
            }
            .yuv-search-input:focus {
                outline: none;
                box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.1);
            }
            .yuv-search-results {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                max-height: 350px;
                overflow-y: auto;
                z-index: 1000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                display: none;
                margin-top: 4px;
            }
            .yuv-search-result-item {
                padding: 12px;
                border-bottom: 1px solid #f0f0f0;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 12px;
                transition: background 0.15s;
            }
            .yuv-search-result-item:hover {
                background: #f6f7f7;
            }
            .yuv-search-result-image {
                width: 50px;
                height: 50px;
                object-fit: cover;
                border-radius: 4px;
                flex-shrink: 0;
            }
            .yuv-search-result-info h4 {
                margin: 0 0 4px 0;
                font-size: 14px;
                color: #1d2327;
            }
            .yuv-search-result-info p {
                margin: 0;
                font-size: 12px;
                color: #646970;
                line-height: 1.4;
            }
            
            .yuv-add-contestant-btn {
                background: #2271b1;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                margin-bottom: 15px;
                transition: background 0.2s;
            }
            .yuv-add-contestant-btn:hover:not(:disabled) {
                background: #135e96;
            }
            .yuv-add-contestant-btn:disabled {
                background: #dcdcde;
                cursor: not-allowed;
            }
            
            .yuv-contestant-list { 
                list-style: none; 
                padding: 0; 
                margin: 15px 0 0 0;
            }
            .yuv-contestant-item { 
                display: flex;
                gap: 15px;
                margin-bottom: 15px;
                padding: 15px;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 6px;
                position: relative;
                transition: background 0.15s;
            }
            .yuv-contestant-item:hover {
                background: #f6f7f7;
            }
            .yuv-contestant-image-col { 
                flex-shrink: 0;
                display: flex; 
                flex-direction: column; 
                gap: 8px;
            }
            .yuv-contestant-image-preview { 
                width: 100px;
                height: 100px;
                border: 2px dashed #c3c4c7;
                border-radius: 6px;
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
                color: #a7aaad;
                font-size: 11px;
                text-align: center;
                padding: 5px;
            }
            .yuv-select-image-btn { 
                width: 100px;
                padding: 6px 10px;
                font-size: 12px;
                text-align: center;
            }
            .yuv-contestant-fields { 
                flex: 1;
                display: flex; 
                flex-direction: column; 
                gap: 10px;
            }
            .yuv-contestant-fields input[type="text"],
            .yuv-contestant-fields textarea { 
                width: 100%;
                padding: 8px 10px;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                font-size: 14px;
            }
            .yuv-contestant-fields input[type="text"]:focus,
            .yuv-contestant-fields textarea:focus {
                outline: none;
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
            }
            .yuv-contestant-fields textarea {
                min-height: 70px;
                resize: vertical;
                font-family: inherit;
            }
            .yuv-contestant-remove { 
                position: absolute;
                top: 12px;
                right: 12px;
                background: #dc3545;
                color: white;
                border: none;
                width: 26px;
                height: 26px;
                border-radius: 50%;
                cursor: pointer;
                font-size: 18px;
                font-weight: bold;
                line-height: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.2s;
            }
            .yuv-contestant-remove:hover { 
                background: #a02622;
            }
            
            .yuv-bracket-status { 
                padding: 15px 20px; 
                background: #e7f3ff; 
                border-left: 4px solid #2271b1; 
                margin-bottom: 20px;
                border-radius: 4px;
            }
            .yuv-bracket-status.created { 
                background: #d4edda; 
                border-color: #28a745;
            }
            .yuv-bracket-status h3 {
                margin: 0 0 10px 0;
                font-size: 16px;
            }
            .yuv-bracket-status p {
                margin: 0;
            }
            .yuv-bracket-list { 
                margin-top: 15px;
            }
            .yuv-bracket-list h4 {
                margin: 10px 0 8px 0;
                font-size: 14px;
                color: #1d2327;
            }
            .yuv-bracket-link { 
                display: inline-block; 
                padding: 6px 12px; 
                background: #2271b1; 
                color: white; 
                text-decoration: none; 
                border-radius: 4px; 
                margin-right: 6px; 
                margin-bottom: 6px;
                font-size: 13px;
                transition: background 0.2s;
            }
            .yuv-bracket-link:hover {
                background: #135e96;
                color: white;
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
                <p>Nakon što dodate <?php echo $tournament_size; ?> takmičara i datum početka, kliknite "Publish" ili "Update" da bi se automatski kreirao bracket.</p>
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

        <!-- Stage Durations -->
        <div class="yuv-meta-row">
            <label for="yuv_round_duration_of">⏱️ Trajanje osmine finala (sati):</label>
            <input type="number" 
                   id="yuv_round_duration_of" 
                   name="yuv_round_duration_of" 
                   value="<?php echo esc_attr($of_duration); ?>" 
                   min="1"
                   <?php echo $bracket_created ? 'readonly' : ''; ?>>
            <p class="description">Dan 1: Svih 8 osmina finala istovremeno</p>
        </div>
        
        <div class="yuv-meta-row">
            <label for="yuv_round_duration_qf">⏱️ Trajanje četvrtfinala (sati):</label>
            <input type="number" 
                   id="yuv_round_duration_qf" 
                   name="yuv_round_duration_qf" 
                   value="<?php echo esc_attr($qf_duration); ?>" 
                   min="1"
                   <?php echo $bracket_created ? 'readonly' : ''; ?>>
            <p class="description">Dan 2: Sva 4 četvrtfinala istovremeno</p>
        </div>
        
        <div class="yuv-meta-row">
            <label for="yuv_round_duration_sf">⏱️ Trajanje polufinala (sati):</label>
            <input type="number" 
                   id="yuv_round_duration_sf" 
                   name="yuv_round_duration_sf" 
                   value="<?php echo esc_attr($sf_duration); ?>" 
                   min="1"
                   <?php echo $bracket_created ? 'readonly' : ''; ?>>
            <p class="description">Dan 3: Oba polufinala istovremeno</p>
        </div>
        
        <div class="yuv-meta-row">
            <label for="yuv_round_duration_final">⏱️ Trajanje finala (sati):</label>
            <input type="number" 
                   id="yuv_round_duration_final" 
                   name="yuv_round_duration_final" 
                   value="<?php echo esc_attr($final_duration); ?>" 
                   min="1"
                   <?php echo $bracket_created ? 'readonly' : ''; ?>>
            <p class="description">Dan 4: Finale</p>
        </div>

        <!-- Tournament Size -->
        <div class="yuv-meta-row">
            <label for="yuv_tournament_size">🏆 Broj takmičara:</label>
            <select id="yuv_tournament_size" name="yuv_tournament_size" <?php echo $bracket_created ? 'disabled' : ''; ?>>
                <option value="8" <?php selected($tournament_size, 8); ?>>8 takmičara (Bez osmina finala)</option>
                <option value="16" <?php selected($tournament_size, 16); ?>>16 takmičara (Sa osminama finala)</option>
            </select>
            <?php if ($bracket_created): ?>
                <p class="description">Broj takmičara se ne može menjati nakon kreiranja bracket-a.</p>
            <?php else: ?>
                <p class="description" id="tournament-size-info">Sa <?php echo $tournament_size; ?> takmičara imaćete <?php echo $tournament_size == 16 ? '4 dana (OF + QF + SF + Final)' : '3 dana (QF + SF + Final)'; ?></p>
            <?php endif; ?>
        </div>

        <!-- Contestants -->
        <div class="yuv-meta-row">
            <label>👥 Takmičari (<span id="contestant-counter"><?php echo $contestants_count; ?></span>/<span id="contestant-max"><?php echo $tournament_size; ?></span>):</label>
            
            <?php if (!$bracket_created): ?>
                <!-- Category Filter -->
                <div class="yuv-category-filter" style="margin-bottom: 15px;">
                    <label for="yuv-category-filter" style="display: inline-block; margin-right: 10px; font-weight: normal;">Kategorija:</label>
                    <select id="yuv-category-filter" style="width: 250px;">
                        <option value="">Sve kategorije</option>
                        <?php 
                        $categories = get_terms([
                            'taxonomy' => 'voting_list_category',
                            'hide_empty' => false,
                        ]);
                        foreach ($categories as $cat) {
                            echo '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
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
            const category = $('#yuv-category-filter').val();

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
                        category: category,
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
    
    // Save tournament size
    if (isset($_POST['yuv_tournament_size'])) {
        update_post_meta($post_id, '_yuv_tournament_size', intval($_POST['yuv_tournament_size']));
    }

    // Save stage durations
    if (isset($_POST['yuv_round_duration_of'])) {
        update_post_meta($post_id, '_yuv_round_duration_of', intval($_POST['yuv_round_duration_of']));
    }
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

    // Auto-create bracket if not created and we have correct number of contestants
    $bracket_created = get_post_meta($post_id, '_yuv_bracket_created', true);
    if (!$bracket_created) {
        $contestants = get_post_meta($post_id, '_yuv_contestants', true);
        $tournament_size = get_post_meta($post_id, '_yuv_tournament_size', true) ?: 16;
        if (is_array($contestants) && count($contestants) === $tournament_size) {
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
