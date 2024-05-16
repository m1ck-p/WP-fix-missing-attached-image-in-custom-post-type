<?php
/*
    Plugin Name: fix-missing-attached-image-in-custom-post-type
    Description: This plugin fixes broken attached image links in custom post types.
    Version: 1.0
    Author: Mick
*/

// hook into plugin activation to fix broken attached image links for all existing posts of a certain type
register_activation_hook(__FILE__, 'fix_broken_attached_image_links_activation');

function fix_broken_attached_image_links_activation() {
    // get all existing posts of the type 'your-custom-post-type'
    $posts = get_posts(array(
        'post_type' => 'your-custom-post-type',
        'posts_per_page' => -1, // get all posts
    ));

    // iterate through posts and fix broken attached image links
    foreach ($posts as $post) {
        fix_broken_attached_image_links($post->ID);
    }
}

function fix_broken_attached_image_links($post_id) {
    // check if the post type is of the type 'your-custom-post-type'
    if (get_post_type($post_id) === 'your-custom-post-type') {
        // get attached images for the post
        $attached_images = get_attached_media('image', $post_id);

        // iterate through attached images
        foreach ($attached_images as $attached_image) {
            // get the URL of the attached image
            $image_link = wp_get_attachment_url($attached_image->ID);

            // check if the image link is broken
            $is_broken = is_image_link_broken($image_link);

            if ($is_broken) {
                // replace broken image link with default image or remove it + update post's metadata accordingly
                fix_attached_image_link($attached_image->ID);
            }
        }
    }
}

// function to check if an image link is broken
function is_image_link_broken($image_link) {

    // send HTTP request to image URL
    $response = wp_remote_get($image_link);

    // check if wp error was encountered during request
    if (is_wp_error($response)) {
        // error occurred -> consider the image link broken
        return true;
    }

    // get response status code
    $response_code = wp_remote_retrieve_response_code($response);

    // check if status code indicates successful (200 OK) or failed (e.g. 404 ERR) response
    if ($response_code === 200) {
        // cool, image link is not broken
        return false;
    } else {
        // uh-oh, image link is broken
        return true;
    }

}

// function to fix (delete) broken attached image link
function fix_attached_image_link($attachment_id) {
    // delete attachment as it is broken
    wp_delete_attachment($attachment_id, true);
}
