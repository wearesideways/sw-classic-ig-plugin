<?php

/*
Plugin Name: Instagram Settings
Description: Sync Instagram feed
Version:     0.9.9
Author:     SW Dev Team
*/

if (!defined('ABSPATH')) {
    die('Invalid Request');
}

require dirname(__FILE__) . '/vendor/autoload.php';
require dirname(__FILE__) . '/lib/ajax_functions.php';

//************************************************************************************
// This remove a filter from ACF plugin to hide the default custom fields box
add_filter('acf/settings/remove_wp_meta_box', '__return_false');
//************************************************************************************

if (!defined('SW_IG_PLUGIN_URL')) {
    define('SW_IG_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('SW_IG_ADMIN_PAGE_URL')) {
    define('SW_IG_ADMIN_PAGE_URL', admin_url('options-general.php?page=instagram-settings', 'https'));
}

if (!defined('SW_IG_REMOVE_HASHTAGS')) {
    define('SW_IG_REMOVE_HASHTAGS', false);
}

add_action('admin_enqueue_scripts', function () {
    wp_register_script('sw_ig_plugin_instagram_scripts', SW_IG_PLUGIN_URL . 'js/admin.js', ['jquery'], '1.0.0', false);
    $vars = ['ajaxurl' => admin_url('admin-ajax.php')];
    wp_localize_script('sw_ig_plugin_instagram_scripts', 'vars', $vars);
    wp_enqueue_script('sw_ig_plugin_instagram_scripts');
    wp_enqueue_style('sw_ig_plugin_instagram_style', SW_IG_PLUGIN_URL . '/css/admin.css', [], '1.0.0');
});

add_action('admin_notices', function () {
    $screen = get_current_screen();
    $errors = get_option('sw-ig-errors', []);

    if ($screen->id == 'settings_page_instagram-settings' && !empty($errors)) {
        echo "<div id='sw-ig-admin-notices' class='notice notice-error is-dismissible'><ul>";
        foreach ($errors as $error) {
            echo "<li>- $error</li>";
        }
        echo "</ul></div>";
    }
});

if (is_admin()) {
    add_action('admin_menu', function () {
        add_options_page('Instagram Settings', 'Instagram Settings', 'manage_options', 'instagram-settings', function () {
            ?>
            <div class="wrap">
                <h1>Instagram Plugin Settings</h1>

                <?php
                $isLoginCallback = isset($_GET['fb_login_callback']);
                if ($isLoginCallback) {
                    SwIgPlugin\LoginCallbackHandler::handleCallback();
                } else {
                    require dirname(__FILE__) . '/pages/main.php';
                }
                ?>

            </div>

            <?php
        });
    });
}
?>