<?php
/**
 * Template Part: Top Categories Zigzag Layout
 * 
 * Displays categories in alternating left/right layout with:
 * - Large circular mascot/logo
 * - Category info box with top voting list
 * - Alternating sides for visual interest
 * 
 * @package YugoVote
 */

if (!defined('ABSPATH')) exit;

// Fetch all top-level (parent) categories
$parent_categories = get_terms([
    'taxonomy'   => 'voting_list_category',
    'parent'     => 0,
    'hide_empty' => true,
    'orderby'    => 'name',
    'order'      => 'ASC',
]);

if (empty($parent_categories) || is_wp_error($parent_categories)) {
    return;
}
?>

<section class="yuv-top-categories-section">
    <div class="cs-container">
        <!-- Section Header -->
        <div class="yuv-categories-header">
            <div class="yuv-header-content">
                <h2 class="yuv-section-title">Najpopularnije objave po kategorijama</h2>
                <p class="yuv-section-description">
                    Vestibulum sollicitudin ultricies orci, et posuere tortor fermentum nec. Integer iaculis molestie lectus.
                </p>
            </div>
            <a href="/sve-kategorije" class="yuv-btn-view-all">
                pogledaj<br>više
            </a>
        </div>
        
        <!-- Zigzag Category List -->
        <div class="yuv-categories-zigzag">
            <?php 
            $index = 0;
            foreach ($parent_categories as $term): 
                $term_id = $term->term_id;
                $term_slug = $term->slug;
                $term_name = $term->name;
                $term_link = get_term_link($term);
                $term_color = get_term_meta($term_id, 'category_color', true) ?: '#4355A4';
                $logo_id = get_term_meta($term_id, 'category_logo', true);
                $slogan = get_term_meta($term_id, 'category_slogan', true);
                
                // Get top voting list for this category
                $top_list_args = [
                    'post_type'      => 'voting_list',
                    'post_status'    => 'publish',
                    'posts_per_page' => 1,
                    'orderby'        => 'meta_value_num',
                    'order'          => 'DESC',
                    'meta_key'       => '_yuv_voting_total_votes',
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
                
                $top_list = new WP_Query($top_list_args);
                
                if (!$top_list->have_posts()) {
                    wp_reset_postdata();
                    $index++;
                    continue;
                }
                
                $top_list->the_post();
                $list_id = get_the_ID();
                $list_title = get_the_title();
                $list_excerpt = get_the_excerpt();
                $list_votes = (int) get_post_meta($list_id, '_yuv_voting_total_votes', true);
                $list_permalink = get_permalink();
                
                // Calculate total votes in category
                $all_lists_args = array_merge($top_list_args, ['posts_per_page' => -1, 'fields' => 'ids']);
                $all_lists = get_posts($all_lists_args);
                $total_votes = 0;
                foreach ($all_lists as $post_id) {
                    $total_votes += (int) get_post_meta($post_id, '_yuv_voting_total_votes', true);
                }
                
                wp_reset_postdata();
                
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
                
                <!-- Content Box -->
                <div class="yuv-cat-content">
                    <div class="yuv-cat-box">
                        <h3 class="yuv-cat-name" style="color: <?php echo esc_attr($term_color); ?>;">
                            <?php echo esc_html(strtoupper($term_name)); ?>
                        </h3>
                        <span class="yuv-cat-votes" style="color: <?php echo esc_attr($term_color); ?>;">
                            <?php echo number_format_i18n($total_votes); ?> GLASOVA
                        </span>
                        
                        <h4 class="yuv-list-title">
                            <?php echo esc_html($list_title); ?>
                        </h4>
                        
                        <p class="yuv-list-description">
                            <?php echo esc_html($list_excerpt); ?>
                        </p>
                        
                        <a href="<?php echo esc_url($term_link); ?>" 
                           class="yuv-cat-arrow" 
                           style="background-color: <?php echo esc_attr($term_color); ?>;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="16" height="16">
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
