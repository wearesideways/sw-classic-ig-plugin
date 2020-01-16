<?php
add_action('wp_ajax_sync_feed', function () {
    $fbConnector = new FacebookApiConnector();
    $fbClient = $fbConnector->getClient();

    $helpers = new SWIGHelpers();
    $settings = $helpers->getPluginSettings();

    $accessToken = $_POST['access_token'];
    $fbAccount = [];

    foreach ($settings['accounts'] as $account) {
        if ($account['access_token'] == $accessToken) {
            $fbAccount = $account;
            break;
        }
    }

    foreach ($fbAccount['pages'] as $page) {

        $mediaRequest = $fbClient->get('/' . $page['ig_user_id'] . '/media?limit=20', $fbAccount['access_token']);
        $mediaResponse = json_decode($mediaRequest->getBody())->data;

        foreach ($mediaResponse as $media) {
            $getMediaItem = $fbClient->get('/' . $media->id . '?fields=id,media_type,media_url,caption,permalink,timestamp,username', $fbAccount['access_token']);
            $mediaItem = json_decode($getMediaItem->getBody());

            if (!imageExists($mediaItem) && $mediaItem->media_type === 'IMAGE') {
                $title = SW_IG_REMOVE_HASHTAGS ? removeHashtags($mediaItem->caption) : $mediaItem->caption;

                $newPostArgs = [
                    'post_title' => $title,
                    'post_type' => $settings['post_type'],
                    'post_status' => $settings['post_status'],
                ];
                $postId = wp_insert_post($newPostArgs);

                $upload = saveImageToUploads($mediaItem->media_url, $helpers);

                $filepath = $upload['file'];
                $filename = basename($filepath);

                $wpFiletype = wp_check_filetype($filename, null);
                $attachment = [
                    'post_mime_type' => $wpFiletype['type'],
                    'post_title' => sanitize_file_name($filename),
                    'post_content' => '',
                    'guid' => $mediaItem->permalink,
                    'post_status' => 'inherit',
                    'post_date' => gmdate('Y-m-d H:i:s', strtotime($mediaItem->timestamp))
                ];
                $attachId = wp_insert_attachment($attachment, $filepath, $postId);

                $attach_data = wp_generate_attachment_metadata($attachId, $filepath);
                wp_update_attachment_metadata($attachId, $attach_data);
                set_post_thumbnail($postId, $attachId);

                // Add alt text
                update_post_meta($attachId, '_wp_attachment_image_alt', $title);

                saveIGCustomFields($mediaItem, $postId);
            }
        }
    }
    echo wp_json_encode(['success' => true]);
    exit;
});

add_action('wp_ajax_clean_admin_notices', function () {
    update_option('sw-ig-errors', []);
    echo wp_json_encode(['success' => true]);
    exit;
});

function saveIGCustomFields($mediaItem, $postId)
{
    $meta = [
        'instagram_id' => $mediaItem->id,
        'instagram_type' => $mediaItem->media_type,
        'instagram_username' => $mediaItem->username,
        'instagram_link' => esc_url_raw($mediaItem->permalink),
        'instagram_created_time' => $mediaItem->timestamp,
    ];

    foreach ($meta as $key => $value) {
        update_post_meta($postId, $key, $value);
    }
}

function imageExists($mediaItem)
{
    $helpers = new SWIGHelpers();
    $settings = $helpers->getPluginSettings();
    $alreadyInSystem = new WP_Query(
        [
            'post_type' => $settings['post_type'],
            'post_status' => 'any',
            'no_found_rows' => true,
            'meta_query' => [
                [
                    'key' => 'instagram_created_time',
                    'value' => $mediaItem->timestamp
                ]
            ]
        ]
    );
    // Returns whether photo already exists in WP
    return $alreadyInSystem->have_posts();
}

function removeHashtags($string)
{
    $hashtagPattern = '/(^|[^0-9A-Z&\/\?]+)([#＃]+)([0-9A-Z_]*[A-Z_]+[a-z0-9_üÀ-ÖØ-öø-ÿ]*)/iu';
    $cleanContent = trim(preg_replace($hashtagPattern, '', $string));

    // If the result is empty (only hashtags), remove only the hash symbol instead
    $content = empty($cleanContent) ? trim(str_replace('#', '', $string)) : $cleanContent;
    return $content;
}

function saveImageToUploads($mediaUrl, $helpers)
{
    if (!class_exists('WP_Http')) {
        include_once ABSPATH . WPINC . '/class-http.php';
    }

    $http = new \WP_Http();
    $response = $http->request($mediaUrl);

    if (is_wp_error($response)) {
        $helpers->addMessageError($response->get_error_message());
    }

    $mediaUrlCleaned = preg_replace('/\?.*/', '', basename($mediaUrl));
    $upload = wp_upload_bits($mediaUrlCleaned, null, $response['body']);
    if (!empty($upload['error'])) {
        $helpers->addMessageError('An error ocurred during the image import to the media library');
    }
    return $upload;
}

?>