<?php
/**
 * Template Part: Top Categories with Top 3 Lists
 * Compact design with vote counting per category
 * 
 * @package YugoVote
 */

if (!defined('ABSPATH')) exit;

// Fetch all top-level categories
$parent_categories = get_terms([
    'taxonomy'   => 'voting_list_category',
    'parent'     => 0,
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
]);

if (empty($parent_categories) || is_wp_error($parent_categories)) {
    echo '<!-- DEBUG: No parent categories found or WP_Error -->';
    return;
}

echo '<!-- DEBUG: Found ' . count($parent_categories) . ' parent categories -->';
foreach ($parent_categories as $cat) {
    echo '<!-- DEBUG Category: ' . $cat->name . ' (ID: ' . $cat->term_id . ') -->';
}

/**
 * Calculate total votes for entire category (same as trending section)
 */
function yuv_get_category_total_votes($term_id) {
    $args = [
        'post_type'      => 'voting_list',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => '_is_tournament_match',
                'compare' => 'NOT EXISTS',
            ],
        ],
        'tax_query'      => [
            [
                'taxonomy'         => 'voting_list_category',
                'field'            => 'term_id',
                'terms'            => $term_id,
                'include_children' => true,
            ],
        ],
    ];
    
    $list_ids = get_posts($args);
    $total_votes = 0;
    
    foreach ($list_ids as $list_id) {
        // Use the same function as trending section
        if (function_exists('get_total_score_for_voting_list')) {
            $total_votes += (int) get_total_score_for_voting_list($list_id);
        } else {
            // Fallback to meta
            $total_votes += (int) get_post_meta($list_id, 'total_score', true);
        }
    }
    
    return $total_votes;
}
?>

<section class="yuv-top-categories-section">
    <div class="cs-container">
        
        <!-- Category Cards Carousel -->
        <div class="yuv-categories-carousel">
            <button class="yuv-carousel-btn yuv-carousel-prev" aria-label="Prethodna">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 18L9 12L15 6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            
            <div class="yuv-carousel-track">
            <?php foreach ($parent_categories as $term): 
                $term_id = $term->term_id;
                $term_slug = $term->slug;
                $term_name = $term->name;
                $term_link = get_term_link($term);
                $term_color = get_term_meta($term_id, 'category_color', true) ?: '#4355A4';
                $logo_id = get_term_meta($term_id, 'category_logo', true);
                
                // Get top 3 voting lists for this category (sorted by votes)
                $top_lists_args = [
                    'post_type'      => 'voting_list',
                    'post_status'    => 'publish',
                    'posts_per_page' => 3,
                    'orderby'        => 'meta_value_num',
                    'order'          => 'DESC',
                    'meta_key'       => 'total_score',
                    'meta_query'     => [
                        [
                            'key'     => '_is_tournament_match',
                            'compare' => 'NOT EXISTS',
                        ],
                    ],
                    'tax_query'      => [
                        [
                            'taxonomy'         => 'voting_list_category',
                            'field'            => 'term_id',
                            'terms'            => $term_id,
                            'include_children' => true,
                        ],
                    ],
                ];
                
                $top_lists = new WP_Query($top_lists_args);
                
                echo '<!-- DEBUG Category: ' . esc_html($term_name) . ' -->';
                echo '<!-- DEBUG - Term ID: ' . $term_id . ' -->';
                echo '<!-- DEBUG - Found posts: ' . $top_lists->found_posts . ' -->';
                echo '<!-- DEBUG - Post count: ' . $top_lists->post_count . ' -->';
                if ($top_lists->have_posts()) {
                    while ($top_lists->have_posts()) {
                        $top_lists->the_post();
                        $debug_id = get_the_ID();
                        $debug_score = get_post_meta($debug_id, 'total_score', true);
                        $debug_title = get_the_title();
                        echo '<!-- DEBUG - List: ' . esc_html($debug_title) . ' (ID: ' . $debug_id . ', Score: ' . $debug_score . ') -->';
                    }
                    wp_reset_postdata();
                }
                
                // Skip categories with no lists
                if (!$top_lists->have_posts()) {
                    echo '<!-- DEBUG - SKIPPING category (no posts found) -->';
                    wp_reset_postdata();
                    continue;
                }
                
                // Calculate total votes in category (same method as trending)
                $total_votes = yuv_get_category_total_votes($term_id);
                
                echo '<!-- DEBUG - Total category votes: ' . $total_votes . ' -->';
            ?>
            
            <div class="yuv-cat-card" style="--cat-color: <?php echo esc_attr($term_color); ?>;">
                
                <!-- Mascot -->
                <div class="yuv-cat-mascot">
                    <?php if ($logo_id): ?>
                        <?php echo wp_get_attachment_image($logo_id, 'medium', false, ['class' => 'mascot-img']); ?>
                    <?php endif; ?>
                </div>
                
                <!-- Content -->
                <div class="yuv-cat-content">
                    <!-- Header -->
                    <div class="yuv-cat-header">
                        <h3 class="yuv-cat-name">
                            <?php echo esc_html(strtoupper($term_name)); ?>
                        </h3>
                        <span class="yuv-cat-votes">
                            <?php echo number_format_i18n($total_votes); ?> GLASOVA
                        </span>
                    </div>
                    
                    <!-- Top 3 Lists -->
                    <ul class="yuv-ranking-list">
                        <?php 
                        $rank = 1;
                        while ($top_lists->have_posts()): 
                            $top_lists->the_post();
                            $list_id = get_the_ID();
                            
                            // Use same vote counting as trending section
                            $list_votes = 0;
                            if (function_exists('get_total_score_for_voting_list')) {
                                $list_votes = (int) get_total_score_for_voting_list($list_id);
                            } else {
                                $list_votes = (int) get_post_meta($list_id, 'total_score', true);
                            }
                        ?>
                            <li class="yuv-rank-item">
                                <span class="rank-num">#<?php echo $rank; ?></span>
                                <div class="rank-info">
                                    <a href="<?php the_permalink(); ?>" class="rank-title">
                                        <?php the_title(); ?>
                                    </a>
                                    <span class="rank-votes">
                                        <?php echo number_format_i18n($list_votes); ?> glasova
                                    </span>
                                </div>
                            </li>
                        <?php 
                            $rank++;
                        endwhile; 
                        wp_reset_postdata();
                        ?>
                    </ul>
                    
                    <!-- View More Button -->
                    <a href="<?php echo esc_url($term_link); ?>" class="yuv-cat-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="27" height="20" viewBox="0 0 27 20" fill="none">
                            <path d="M1.5 9.81774C6.04008 10.4649 16.322 11.371 21.1292 9.81774C27.1381 7.87618 19.1262 6.48935 14.7196 2.8836C10.3131 -0.72216 27.3384 7.7375 25.3354 9.81774C23.3324 11.898 19.1262 16.3358 16.9229 18" stroke="white" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </a>
                </div>
            </div>
            
            <?php endforeach; ?>
            </div>
            
            <button class="yuv-carousel-btn yuv-carousel-next" aria-label="Sledeća">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18L15 12L9 6" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        
    </div>
</section>
