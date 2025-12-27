<?php
/**
 * Quiz Grid Shortcode
 * Usage: [yuv_quiz_grid posts_per_page="9" category=""]
 */

if (!defined('ABSPATH')) exit;

function yuv_quiz_grid_shortcode($atts) {
    $atts = shortcode_atts([
        'posts_per_page' => 9,
        'category' => '', // Optional category slug
    ], $atts, 'yuv_quiz_grid');

    $posts_per_page = intval($atts['posts_per_page']);
    $category_slug = sanitize_text_field($atts['category']);

    // Build query args
    $args = [
        'post_type' => 'quiz',
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page,
        'orderby' => 'date',
        'order' => 'DESC',
    ];

    // If category is specified, filter by it
    if (!empty($category_slug)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'quiz_category',
                'field' => 'slug',
                'terms' => $category_slug,
            ],
        ];
    }

    $quiz_query = new WP_Query($args);

    // Get all categories for filter tabs
    $categories = get_terms([
        'taxonomy' => 'quiz_category',
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    if ($quiz_query->have_posts() || !empty($categories)) {
        ob_start();
        ?>

        <div class="yuv-quiz-archive">
            
            <?php if (!empty($categories)): ?>
                <!-- Filter Tabs -->
                <div class="yuv-quiz-filters">
                    <button class="yuv-filter-btn active" data-filter="all">
                        <i class="ri-layout-grid-line"></i>
                        Svi Kvizovi
                    </button>
                    <?php foreach ($categories as $term): ?>
                        <?php
                        $term_color = get_term_meta($term->term_id, 'quiz_category_color', true) ?: '#6A0DAD';
                        $term_count = $term->count;
                        ?>
                        <button 
                            class="yuv-filter-btn" 
                            data-filter="<?php echo esc_attr($term->slug); ?>"
                            style="--filter-color: <?php echo esc_attr($term_color); ?>;">
                            <?php echo esc_html($term->name); ?>
                            <span class="yuv-filter-count"><?php echo esc_html($term_count); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Quiz Grid -->
            <div class="yuv-quiz-grid" id="yuv-quiz-grid">
                <?php
                if ($quiz_query->have_posts()) {
                    while ($quiz_query->have_posts()) {
                        $quiz_query->the_post();
                        get_template_part('inc/quizzes/templates/content-quiz-card');
                    }
                    wp_reset_postdata();
                } else {
                    echo '<div class="yuv-quiz-empty">
                        <i class="ri-survey-line" style="font-size: 4rem; color: #ccc; margin-bottom: 20px;"></i>
                        <p>Nema dostupnih kvizova u ovoj kategoriji.</p>
                    </div>';
                }
                ?>
            </div>

            <?php if ($quiz_query->have_posts()): ?>
                <!-- Result Count -->
                <div class="yuv-quiz-results">
                    Prikazano <span id="yuv-visible-count"><?php echo $quiz_query->post_count; ?></span> od <?php echo $quiz_query->post_count; ?> kvizova
                </div>
            <?php endif; ?>

        </div>

        <?php
        return ob_get_clean();
    }

    return '<p>Nema dostupnih kvizova.</p>';
}
add_shortcode('yuv_quiz_grid', 'yuv_quiz_grid_shortcode');
