<?php
add_action('wp_ajax_sync_feed', function () {
    $helpers = new SwIgPlugin\SWIGHelpers();

    if (!current_user_can('publish_posts')) {
        $helpers->addMessageError('Sorry, you do not have the right privileges');
        echo wp_json_encode(['success' => false]);
        wp_die();
    }

    $settings = $helpers->getPluginSettings();

    $accessToken = $_POST['access_token'];
    $fbAccount = [];

    foreach ($settings['accounts'] as $account) {
        if ($account['access_token'] == $accessToken) {
            $fbAccount = $account;
            break;
        }
    }

    $importer = new SwIgPlugin\PostsImporter();
    $importStatus = $importer->import_ig_posts( $fbAccount );

    wp_send_json(['success' => (bool) $importStatus]);
});

add_action('wp_ajax_clean_admin_notices', function () {
    update_option('sw-ig-errors', []);

    wp_send_json([ 'success' => true ]);
});

add_action('wp_ajax_update_autosync_option', function () {
    $helpers  = new SwIgPlugin\SWIGHelpers();
    $settings = $helpers->getPluginSettings();
    $newValue = $_POST['autosync'] === 'true';

    $settings['autosync_enabled'] = $newValue;
    update_option( 'sw-ig-settings', $settings );

    wp_send_json([ 'success' => true ]);
});