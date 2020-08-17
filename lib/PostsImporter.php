<?php

namespace SwIgPlugin;

class PostsImporter {
    private $helpers;
    private $fbConnector;
    private $settings;

    public function __construct() {
        $this->helpers     = new SWIGHelpers();
        $this->fbConnector = new FacebookApiConnector();
        $this->settings    = $this->helpers->getPluginSettings();
    }

    public function fetch_from_all_accounts( $isCron = false ) {
        $status = true;
        foreach ( $this->settings['accounts'] as $fbAccount ) {
            // Validate if the FB account has IG pages linked
            if ( isset( $fbAccount['pages'] ) ) {
                if ( !$this->import_ig_posts( $fbAccount, $isCron ) ) {
                    return false;
                }
            }
        }
        return $status;
    }

    public function import_ig_posts( $fbAccount, $isCron = false ) {
        $fbClient = $this->fbConnector->getClient();

        foreach ( $fbAccount['pages'] as $page ) {
            $mediaRequest  = $fbClient->get( '/' . $page['ig_user_id'] . '/media?limit=20', $fbAccount['access_token'] );
            $mediaResponse = json_decode( $mediaRequest->getBody() )->data;

            foreach ( $mediaResponse as $media ) {
                $getMediaItem = $fbClient->get( '/' . $media->id . '?fields=id,media_type,media_url,caption,permalink,timestamp,username', $fbAccount['access_token'] );
                $mediaItem    = json_decode( $getMediaItem->getBody() );

                if ( !$this->imageExists( $mediaItem ) && $mediaItem->media_type === 'IMAGE' ) {
                    $title = SW_IG_REMOVE_HASHTAGS ? $this->removeHashtags( $mediaItem->caption ) : $mediaItem->caption;

                    $newPostArgs = [
                        'post_title'  => $title,
                        'post_type'   => $this->settings['post_type'],
                        'post_status' => $this->settings['post_status'],
                    ];
                    $postId      = wp_insert_post( $newPostArgs );

                    if ( !$upload = $this->saveImageToUploads( $mediaItem->media_url ) ) {
                        $this->helpers->sendNotification( 'An error ocurred during the media import: ' . json_encode( $page ) );

                        if ( !$isCron ) {
                            $this->helpers->addMessageError( 'An error ocurred during the media import' );
                        }
                        return false;
                    }

                    $filepath = $upload['file'];
                    $filename = basename( $filepath );

                    $wpFiletype = wp_check_filetype( $filename, null );
                    $attachment = [
                        'post_mime_type' => $wpFiletype['type'],
                        'post_title'     => sanitize_file_name( $filename ),
                        'post_content'   => '',
                        'guid'           => $mediaItem->permalink,
                        'post_status'    => 'inherit',
                        'post_date'      => gmdate( 'Y-m-d H:i:s', strtotime( $mediaItem->timestamp ) )
                    ];
                    $attachId   = wp_insert_attachment( $attachment, $filepath, $postId );

                    $attach_data = wp_generate_attachment_metadata( $attachId, $filepath );
                    wp_update_attachment_metadata( $attachId, $attach_data );
                    set_post_thumbnail( $postId, $attachId );

                    // Add alt text
                    update_post_meta( $attachId, '_wp_attachment_image_alt', $title );

                    $this->saveIGCustomFields( $mediaItem, $postId );

                }
            }
        }

        return true;
    }

    private function imageExists( $mediaItem ) {
        $alreadyImported = new \WP_Query(
            [
                'post_type'     => $this->settings['post_type'],
                'post_status'   => 'any',
                'no_found_rows' => true,
                'meta_query'    => [
                    [
                        'key'   => 'instagram_id',
                        'value' => $mediaItem->id
                    ]
                ]
            ]
        );
        return $alreadyImported->have_posts();
    }

    private function removeHashtags( $string ) {
        $hashtagPattern = '/(^|[^0-9A-Z&\/\?]+)([#＃]+)([0-9A-Z_]*[A-Z_]+[a-z0-9_üÀ-ÖØ-öø-ÿ]*)/iu';
        $cleanContent   = trim( preg_replace( $hashtagPattern, '', $string ) );

        // If the result is empty (only hashtags), remove only the hash symbol instead
        return empty( $cleanContent ) ? trim( str_replace( '#', '', $string ) ) : $cleanContent;
    }

    private function saveImageToUploads( $mediaUrl ) {
        if ( !class_exists( 'WP_Http' ) ) {
            include_once ABSPATH . WPINC . '/class-http.php';
        }

        $http     = new \WP_Http();
        $response = $http->request( $mediaUrl );

        if ( is_wp_error( $response ) ) {
            return;
        }

        $mediaUrlCleaned = preg_replace( '/\?.*/', '', basename( $mediaUrl ) );
        $upload          = wp_upload_bits( $mediaUrlCleaned, null, $response['body'] );
        if ( !empty( $upload['error'] ) ) {
            return;
        }
        return $upload;
    }

    function saveIGCustomFields( $mediaItem, $postId ) {
        $meta = [
            'instagram_id'       => $mediaItem->id,
            'instagram_type'     => $mediaItem->media_type,
            'instagram_username' => $mediaItem->username,
            'instagram_link'     => esc_url_raw( $mediaItem->permalink )
        ];

        foreach ( $meta as $key => $value ) {
            update_post_meta( $postId, $key, $value );
        }
    }
}