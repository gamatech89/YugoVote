<?php
/**
 * Template Part: Top Categories with Top 3 Lists
 * 
 * Displays categories in alternating left/right layout with:
 * - Large circular mascot/logo
 * - Category name and total votes
 * - Top 3 voting lists by votes
 * - Link to category archive
 * 
 * @package YugoVote
 */

if (!defined('ABSPATH')) exit;

// Debug: Check if we're getting categories
$parent_categories = get_terms([
    'taxonomy'   => 'voting_list_category',
    'parent'     => 0,
    'hide_empty' => false, // Changed to false to see ALL categories
    'orderby'    => 'name',
    'order'      => 'ASC',
]);

// Debug output
if (is_wp_error($parent_categories)) {
    echo '<!-- Error getting categories: ' . $parent_categories->get_error_message() . ' -->';
    return;
}

if (empty($parent_categories)) {
    echo '<!-- No parent categories found. Total terms: ' . wp_count_terms('voting_list_category') . ' -->';
    return;
}

echo '<!-- Found ' . count($parent_categories) . ' parent categories -->';
?>

<section class="yuv-top-categories-section">
    <div class="cs-container">
        
        <!-- Section Header -->
        <div class="yuv-categories-header">
            <div class="yuv-header-content">
                <h2 class="yuv-section-title">Najpopularnije objave po kategorijama</h2>
                <p class="yuv-section-description">
                    Glasajte za svoje favorite i otkrijte najtraženije liste u svakoj kategoriji.
                </p>
            </div>
            <a href="/liste-za-glasanje" class="yuv-btn-view-all">
                pogledaj<br>više
            </a>
        </div>
        
        <!-- Zigzag Category Rows -->
        <div class="yuv-categories-zigzag">
            <?php 
            $index = 0;
            foreach ($parent_categories as $term): 
                echo '<!-- Processing category: ' . $term->name . ' (ID: ' . $term->term_id . ') -->';
                
                $term_id = $term->term_id;
                $term_slug = $term->slug;
                $term_name = $term->name;
                $term_link = get_term_link($term);
                $term_color = get_term_meta($term_id, 'category_color', true) ?: '#4355A4';
                $logo_id = get_term_meta($term_id, 'category_logo', true);
                
                // Debug: Check if term has any posts at all
                $test_query = new WP_Query([
                    'post_type' => 'voting_list',
                    'posts_per_page' => 1,
                    'tax_query' => [
                        [
                            'taxonomy' => 'voting_list_category',
                            'field' => 'term_id',
                            'terms' => $term_id,
                        ],
                    ],
                ]);
                echo '<!-- Simple query for category ' . $term->name . ': ' . $test_query->found_posts . ' posts (no meta filters) -->';
                wp_reset_postdata();
                
                // Get top 3 voting lists for this category
                $top_lists_args = [
                    'post_type'      => 'voting_list',
                    'post_status'    => 'publish',
                    'posts_per_page' => 3,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
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
                
                echo '<!-- Found ' . $top_lists->found_posts . ' posts for category ' . $term->name . ' with full query -->';
                
                // Skip categories with no lists
                if (!$top_lists->have_posts()) {
                    echo '<!-- Skipping category ' . $term->name . ' - no posts -->';
                    wp_reset_postdata();
                    continue;
                }
                
                // Calculate total votes in category
                $all_lists_args = array_merge($top_lists_args, [
                    'posts_per_page' => -1, 
                    'fields' => 'ids'
                ]);
                $all_lists = get_posts($all_lists_args);
                $total_votes = 0;
                foreach ($all_lists as $post_id) {
                    $total_votes += (int) get_post_meta($post_id, '_yuv_voting_total_votes', true);
                }
                
                // Alternate left/right layout
                $is_even = ($index % 2 === 0);
                $side_class = $is_even ? 'yuv-cat-row--left' : 'yuv-cat-row--right';
            ?>
            
            <div class="yuv-cat-row <?php echo esc_attr($side_class); ?>">
                
                <!-- Mascot Circle -->
                <div class="yuv-cat-mascot" style="background-color: <?php echo esc_attr($term_color); ?>;">
                    <?php if ($logo_id): ?>
                        <?php echo wp_get_attachment_image($logo_id, 'large', false, ['class' => 'mascot-img']); ?>
                    <?php endif; ?>
                </div>
                
                <!-- Content Box with Top 3 Lists -->
                <div class="yuv-cat-content">
                    <div class="yuv-cat-box">
                        
                        <!-- Category Header -->
                        <div class="yuv-cat-header">
                            <h3 class="yuv-cat-name" style="color: <?php echo esc_attr($term_color); ?>;">
                                <?php echo esc_html(strtoupper($term_name)); ?>
                            </h3>
                            <span class="yuv-cat-votes" style="color: <?php echo esc_attr($term_color); ?>;">
                                <?php echo number_format_i18n($total_votes); ?> GLASOVA
                            </span>
                        </div>
                        
                        <!-- Top 3 Ranking List -->
                        <ul class="yuv-ranking-list">
                            <?php 
                            $rank = 1;
                            while ($top_lists->have_posts()): 
                                $top_lists->the_post();
                                $list_id = get_the_ID();
                                $list_votes = (int) get_post_meta($list_id, '_yuv_voting_total_votes', true);
                            ?>
                                <li class="yuv-rank-item">
                                    <span class="rank-num" style="background-color: <?php echo esc_attr($term_color); ?>;">
                                        #<?php echo $rank; ?>
                                    </span>
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
                        
                        <!-- View More Arrow Button -->
                        <a href="<?php echo esc_url($term_link); ?>" 
                           class="yuv-cat-arrow" 
                           style="background-color: <?php echo esc_attr($term_color); ?>;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="20" height="20">
                                <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                            </svg>
                        </a>
                        
                    </div>
                </div>
            </div>
            
            <?php 
                $index++;
            endforeach; 
            ?>
        </div>
        
    </div>
</section>
