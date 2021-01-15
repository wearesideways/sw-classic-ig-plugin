<?php

namespace SwIgPlugin;

class SWIGHelpers {
    public function getPluginSettings() {
        $defaults = [
            'post_type'        => 'instagram',
            'post_status'      => 'publish',
            'accounts'         => [],
            'autosync_enabled' => false,
        ];

        $savedSettings = get_option( 'sw-ig-settings', [] );
        $settings = array_merge( $defaults, $savedSettings );

        if ( defined( 'SW_IG_POST_TYPE' ) ) {
            $settings['post_type'] = SW_IG_POST_TYPE;
        }

        if ( defined( 'SW_IG_POST_STATUS' ) ) {
            $settings['post_status'] = SW_IG_POST_STATUS;
        }
        return $settings;
    }

    public function addMessageError( $error ) {
        $errors   = get_option( 'sw-ig-errors', [] );
        $errors[] = $error;
        update_option( 'sw-ig-errors', $errors );
    }

    public function sendNotification( $message ) {
        $data_string = json_encode(
            [
                'channel'     => 'ig-plugin-notifications',
                'username'    => 'SW Instagram Plugin Cron',
                'text'        => 'WP Cron Job: ' . site_url(),
                'attachments' => [ [ 'text' => $message ] ]
            ]
        );

        $ch = curl_init( 'https://hooks.slack.com/services/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' );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen( $data_string )
            ]
        );
        curl_exec( $ch );
    }
}