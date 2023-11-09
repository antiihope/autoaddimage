<?php

/**
 * Automatically detects if a new post doesn't have a featured image.
 */
function my_plugin_save_post($post_id, $post, $update)
{
    // if autosave , return
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!has_post_thumbnail($post_id)) {
        // get post title
        $title = $post->post_title;
        // santize post title to only get english letters (no weird characters)
        $title = sanitize_title($title);
        // split title and joing '-' between words
        $title = implode('-', explode(' ', $title));

        // Check if this is a publish request
        if ($post->post_status == 'publish' || $post->post_status == 'update') {
            $image_url = 'https://source.unsplash.com/750x600/?' . $title;
            $image_id = upload_image_to_wordpress($image_url, $post_id);
            if ($image_id) {
                update_post_meta($post_id, '_thumbnail_id', $image_id, $title);
            }
        } else {
            // Perform the necessary action, such as displaying a warning message
            // wp_die('Please set a featured image for this post.');
        }
    }
}
add_action('save_post', 'my_plugin_save_post', 10, 3);


function clean_url_image($url)
{
    $url = str_replace('?', '', $url);
}

function upload_image_to_wordpress($image_url, $post_id)
{


    $upload_dir = wp_upload_dir(); // Get the upload directory path
    // wp_die("upload_dir: " . $upload_dir['path']);
    $image_data = file_get_contents($image_url); // Get the image data from the URL
    $filename = basename(clean_url_image($image_url)) . $post_id . '-.png'; // Get the filename from the URL
    $upload_file = $upload_dir['path'] . '/' . $filename; // Create the full path for the image

    file_put_contents($upload_file, $image_data); // Save the image to the uploads directory

    if (!file_exists($upload_file)) {
        wp_die("File not found: $upload_file");
    }


    // Now, you can add it to the media library if you want
    $wp_filetype = wp_check_filetype($filename, null);
    $attachment = array(
        'post_mime_type' => 'image/png',
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $attach_id = wp_insert_attachment($attachment, $upload_file);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload_file);
    wp_update_attachment_metadata($attach_id, $attach_data);
    // You can now use $attach_id to get the attachment ID
    // echo "The attachment ID is: " . $attach_id;
    if (is_wp_error($attach_id)) {
        wp_die("Error adding attachment $upload_file: " . $attach_id->get_error_message());
    } else {
        // wp_die("Success adding attachment $upload_file: " . $attach_id);
    }

    return $attach_id;
}
