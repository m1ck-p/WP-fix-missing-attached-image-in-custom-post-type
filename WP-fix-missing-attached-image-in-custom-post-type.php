<?php
/*
    Plugin Name: WP-fix-missing-attached-image-in-custom-post-type
    Description: This WordPress plugin fixes errors resulting from broken attached image links in custom post types.
    Version: 1.1
    Author: Mick P.
*/

// hook into plugin activation to fix broken attached image links for all existing posts of a certain type
register_activation_hook(__FILE__, 'fix_broken_attached_image_links_activation');

// create options page for plugin
add_action('admin_menu', 'fix_broken_attached_image_links_menu');
function fix_broken_attached_image_links_menu() {
    add_options_page('Fix Broken Attached Image Links Settings', 'Fix Broken Attached Image Links', 'manage_options', 'fix_broken_attached_image_links_settings', 'fix_broken_attached_image_links_options_page');
}

// register plugin settings
add_action('admin_init', 'register_fix_broken_attached_image_links_settings');
function register_fix_broken_attached_image_links_settings() {
    register_setting('fix_broken_attached_image_links_options_group', 'fix_broken_attached_image_links_post_type');
}

// options page
function fix_broken_attached_image_links_options_page() {
    ?>
    <div class="wrap">
        <h1>Fix Broken Attached Image Links Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('fix_broken_attached_image_links_options_group'); ?>
            <?php do_settings_sections('fix_broken_attached_image_links_options_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Select Post Type</th>
                    <td>
                        <select name="fix_broken_attached_image_links_post_type">
                            <?php
                            $post_types = get_post_types(array('public' => true), 'objects');
                            $selected_post_type = get_option('fix_broken_attached_image_links_post_type', 'your-custom-post-type');
                            foreach ($post_types as $post_type) {
                                echo '<option value="' . esc_attr($post_type->name) . '"' . selected($selected_post_type, $post_type->name, false) . '>' . esc_html($post_type->labels->singular_name) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// fix broken attached image links for all existing posts of selected post type upon plugin activation
function fix_broken_attached_image_links_activation() {
    
    // get all existing posts of selected post type from options (or 'your-custom-post-type' if selected post type is not found)
    $post_type = get_option('fix_broken_attached_image_links_post_type', 'your-custom-post-type');

    $posts = get_posts(array(
        'post_type' => $post_type,
        'posts_per_page' => -1, // get all posts
    ));

    // iterate through posts and fix broken attached image links
    foreach ($posts as $post) {
        fix_broken_attached_image_links($post->ID);
    }
}

// fix broken attached image links for specific post if it is of selected post type
function fix_broken_attached_image_links($post_id) {

    // get all existing posts of selected post type from options (or 'your-custom-post-type' if selected post type is not found)
    $post_type = get_option('fix_broken_attached_image_links_post_type', 'your-custom-post-type');
    
    // check if post type is of selected post type
    if (get_post_type($post_id) === $post_type) {
        // get attached images for post
        $attached_images = get_attached_media('image', $post_id);

        // iterate through attached images
        foreach ($attached_images as $attached_image) {
            // get URL of attached image
            $image_link = wp_get_attachment_url($attached_image->ID);

            // check if image link is broken
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
        // error occurred -> consider image link broken
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

// function to fix (by deleting) broken attached image link
function fix_attached_image_link($attachment_id) {
    // delete attachment as it is broken
    wp_delete_attachment($attachment_id, true);
}
