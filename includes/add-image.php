<?php

/**
 * Automatically detects if a new post doesn't have a featured image.
 */

function my_plugin_save_post($post_id, $post)
{
    // if it's autosaving , return
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    /*
    we could've used the post slug instead of the post title
    but the slug doesn't update when the title changes
    */

    if (!has_post_thumbnail($post_id)) {
        //  get post title and santize it to only get english letters (no weird characters)
        $title = $post->post_title;
        $title = sanitize_title($title);

        // split title and joing '-' between words
        $title = implode('-', explode(' ', $title));

        // Check if this is a publish request
        if ($post->post_status == 'publish' || $post->post_status == 'update') {
            $image_url = 'https://source.unsplash.com/750x600/?' . $title;
            $image_id = upload_image_to_wordpress($image_url, $post_id, $title);
            if ($image_id) {
                update_post_meta($post_id, '_thumbnail_id', $image_id);
            }
        }
    }
}
add_action('save_post', 'my_plugin_save_post', 10, 2);



function upload_image_to_wordpress($image_url, $post_id, $filename)
{
    $upload_dir = wp_upload_dir(); // Get the upload directory path

    // wp_die("upload_dir: " . $upload_dir['path']);

    $image_data = file_get_contents($image_url); // this basically makes a get request to the image url and stores the response in $image_data
    $filename .= $post_id . '-.png'; // construct the image name
    $upload_file = $upload_dir['path'] . '/' . $filename; // Create the full path for the image

    file_put_contents($upload_file, $image_data); // Save the image to the uploads directory

    if (!file_exists($upload_file)) {
        wp_die("File not found: $upload_file");
    }


    $wp_filetype = wp_check_filetype($filename, null);   // we don't need this line anymore
    $attachment = array(
        'post_mime_type' => 'image/png',
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $attach_id = wp_insert_attachment($attachment, $upload_file); // adding the image to the media library
    require_once(ABSPATH . 'wp-admin/includes/image.php'); // we need this line to generate post meta
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload_file);
    wp_update_attachment_metadata($attach_id, $attach_data);

    if (is_wp_error($attach_id)) {
        wp_die("Error adding attachment $upload_file: " . $attach_id->get_error_message());
    } else {
        // wp_die("Success adding attachment $upload_file: " . $attach_id);
    }

    return $attach_id;
}
