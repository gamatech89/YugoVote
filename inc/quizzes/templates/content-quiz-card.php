<?php
/**
 * Template Part: Single Quiz Card
 * Used in quiz archive grid
 */

if (!defined('ABSPATH')) exit;

global $post;
$quiz_id = get_the_ID();

// Fetch quiz meta
$num_questions = get_post_meta($quiz_id, '_num_questions', true) ?: 10;
$time_per_question = get_post_meta($quiz_id, '_time_per_question', true) ?: 10;
$quiz_difficulty_id = get_post_meta($quiz_id, '_quiz_difficulty', true);
$quiz_difficulty = '';

if (!empty($quiz_difficulty_id)) {
    $difficulty_post = get_post($quiz_difficulty_id);
    if ($difficulty_post) {
        $quiz_difficulty = $difficulty_post->post_title;
    }
}

// Get category info
$category_color = function_exists('ygv_get_quiz_category_color') 
    ? ygv_get_quiz_category_color($quiz_id) 
    : '#6A0DAD';
$category_name = function_exists('ygv_get_quiz_category_name') 
    ? ygv_get_quiz_category_name($quiz_id) 
    : 'Opšte';

// Get category slug for filtering
$terms = wp_get_object_terms($quiz_id, 'quiz_category', ['fields' => 'slugs']);
$category_slug = (!is_wp_error($terms) && !empty($terms)) ? $terms[0] : 'uncategorized';

// Featured image
$featured_image = get_the_post_thumbnail_url($quiz_id, 'medium');
$excerpt = get_the_excerpt($quiz_id);

// Calculate total time
$total_time = $num_questions * $time_per_question;
$total_time_minutes = ceil($total_time / 60);
?>

<article class="yuv-quiz-card-entry" data-category="<?php echo esc_attr($category_slug); ?>" style="--quiz-card-color: <?php echo esc_attr($category_color); ?>">
    <a href="#" class="yuv-quiz-card-link" data-quiz-id="<?php echo esc_attr($quiz_id); ?>">
        
        <!-- Featured Image -->
        <div class="yuv-quiz-card__image">
            <?php if ($featured_image): ?>
                <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
            <?php else: ?>
                <div class="yuv-quiz-card__placeholder" style="background: linear-gradient(135deg, <?php echo esc_attr($category_color); ?> 0%, rgba(0,0,0,0.3) 100%);"></div>
            <?php endif; ?>
            
            <!-- Category Badge -->
            <span class="yuv-quiz-card__badge" style="background-color: <?php echo esc_attr($category_color); ?>;">
                <?php echo esc_html($category_name); ?>
            </span>
        </div>

        <!-- Card Body -->
        <div class="yuv-quiz-card__body">
            <h3 class="yuv-quiz-card__title"><?php echo esc_html(get_the_title()); ?></h3>
            
            <?php if ($excerpt): ?>
                <p class="yuv-quiz-card__excerpt"><?php echo esc_html(wp_trim_words($excerpt, 15)); ?></p>
            <?php endif; ?>

            <!-- Meta Row -->
            <div class="yuv-quiz-card__meta">
                <div class="yuv-quiz-card__meta-item">
                    <i class="ri-file-list-3-line"></i>
                    <span><?php echo esc_html($num_questions); ?> pitanja</span>
                </div>
                <div class="yuv-quiz-card__meta-item">
                    <i class="ri-time-line"></i>
                    <span>~<?php echo esc_html($total_time_minutes); ?> min</span>
                </div>
                <?php if ($quiz_difficulty): ?>
                    <div class="yuv-quiz-card__meta-item">
                        <i class="ri-star-line"></i>
                        <span><?php echo esc_html($quiz_difficulty); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card Footer -->
        <div class="yuv-quiz-card__footer">
            <span class="yuv-quiz-card__button" style="color: <?php echo esc_attr($category_color); ?>; background: <?php echo esc_attr($category_color); ?>1A;">
                Započni Kviz
                <i class="ri-arrow-right-line"></i>
            </span>
        </div>
    </a>
</article>
