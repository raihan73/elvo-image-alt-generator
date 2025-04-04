<?php
/**
 * Plugin Name: WP Image Alt Tag Generator
 * Plugin URI:  https://www.elvoweb.com
 * Description: Automatically adds missing alt text to images for better SEO.
 * Version:     1.2
 * Author:      Raihanunnabi
 * Author URI:  https://www.elvoweb.com
 * License:     GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Hook into content filter to update image alt text
add_filter('the_content', 'wp_generate_image_alt_text');

function wp_generate_image_alt_text($content) {
    if (is_singular() && in_the_loop() && is_main_query()) {
        $content = preg_replace_callback('/<img[^>]+>/', 'wp_add_missing_alt_text', $content);
    }
    return $content;
}

function wp_add_missing_alt_text($matches) {
    $img_tag = $matches[0];
    if (strpos($img_tag, 'alt=') === false) {
        preg_match('/src=["\']?([^"\'>]+)["\']?/', $img_tag, $src_matches);
        if (!empty($src_matches[1])) {
            $filename = pathinfo($src_matches[1], PATHINFO_FILENAME);
            $alt_text = ucwords(str_replace(['-', '_'], ' ', $filename));
            $new_img_tag = preg_replace('/<img/', '<img alt="' . esc_attr($alt_text) . '"', $img_tag, 1);
            return $new_img_tag;
        }
    }
    return $img_tag;
}

// Add settings page
add_action('admin_menu', 'wp_image_alt_generator_menu');
function wp_image_alt_generator_menu() {
    add_menu_page('Image Alt Generator', 'Alt Tag Generator', 'manage_options', 'wp-image-alt-generator', 'wp_image_alt_generator_settings_page', 'dashicons-format-image', 60);
}

function wp_image_alt_generator_settings_page() {
    ?>
    <div class="wrap">
        <h1><span class="dashicons dashicons-format-image"></span> WP Image Alt Tag Generator</h1>
        <p>Automatically add missing alt tags to images for better SEO.</p>
        <form method="post">
            <input type="submit" name="update_alt_text" class="button button-primary" value="Update All Images">
        </form>
        <?php if (isset($_POST['update_alt_text'])) {
            wp_image_alt_generator_update_existing_images();
        } ?>
        <hr>
        <h2>How It Works</h2>
        <ul>
            <li><strong>Automatic Alt Tag Generation:</strong> The plugin assigns alt tags to images missing them.</li>
            <li><strong>SEO Benefits:</strong> Alt tags improve search engine visibility.</li>
            <li><strong>Manual Update:</strong> Click the button above to update existing images.</li>
        </ul>
    </div>
    <style>
        .wrap h1 { display: flex; align-items: center; gap: 10px; }
        .wrap ul { list-style: disc; margin-left: 20px; }
        .wrap p { font-size: 14px; color: #555; }
    </style>
    <?php
}

// Function to update existing media library images
function wp_image_alt_generator_update_existing_images() {
    $args = array(
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
    );
    $images = get_posts($args);
    foreach ($images as $image) {
        $alt = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
        if (empty($alt)) {
            $filename = pathinfo(get_attached_file($image->ID), PATHINFO_FILENAME);
            $alt_text = ucwords(str_replace(['-', '_'], ' ', $filename));
            update_post_meta($image->ID, '_wp_attachment_image_alt', $alt_text);
        }
    }
    echo '<div class="updated"><p>Alt text updated for all images.</p></div>';
}
