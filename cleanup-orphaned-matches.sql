-- SQL script to clean up orphaned tournament matches
-- Run this in phpMyAdmin or WordPress database tool

-- Step 1: Find all orphaned matches (matches whose tournament doesn't exist)
-- This is just to see what will be deleted (run this first to verify)
SELECT 
    p.ID,
    p.post_title,
    pm.meta_value as tournament_id,
    'ORPHANED' as status
FROM wp_posts p
INNER JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_yuv_tournament_id'
LEFT JOIN wp_posts t ON t.ID = pm.meta_value AND t.post_type = 'yuv_tournament'
WHERE p.post_type = 'voting_list'
AND t.ID IS NULL;

-- Step 2: Delete orphaned matches (run this after verifying above)
-- WARNING: This will permanently delete orphaned matches!

-- Delete post meta for orphaned matches
DELETE pm FROM wp_postmeta pm
INNER JOIN wp_posts p ON pm.post_id = p.ID
INNER JOIN wp_postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_yuv_tournament_id'
LEFT JOIN wp_posts t ON t.ID = pm2.meta_value AND t.post_type = 'yuv_tournament'
WHERE p.post_type = 'voting_list'
AND t.ID IS NULL;

-- Delete orphaned match posts
DELETE p FROM wp_posts p
INNER JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_yuv_tournament_id'
LEFT JOIN wp_posts t ON t.ID = pm.meta_value AND t.post_type = 'yuv_tournament'
WHERE p.post_type = 'voting_list'
AND t.ID IS NULL;

-- Step 3: Update existing matches to point to tournament 32883 (if you want to keep them)
-- Run this INSTEAD of Step 2 if you want to reassign matches rather than delete them
UPDATE wp_postmeta
SET meta_value = '32883'
WHERE meta_key = '_yuv_tournament_id'
AND post_id IN (
    SELECT p.ID FROM wp_posts p
    WHERE p.post_type = 'voting_list'
    AND p.ID IN (
        SELECT pm.post_id FROM wp_postmeta pm
        WHERE pm.meta_key = '_is_tournament_match' AND pm.meta_value = '1'
    )
);
