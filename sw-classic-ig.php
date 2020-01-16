<?php

/*
Plugin Name: SW Classic Instagram Sync
Description: Sync Instagram posts for SW Classic projects
Version:     0.1.0
Author:     SW Dev Team
*/

if (!defined('ABSPATH')) {
    die('Invalid Request');
}

//************************************************************************************
// This remove a filter from ACF plugin to hide the default custom fields box
add_filter('acf/settings/remove_wp_meta_box', '__return_false');
//************************************************************************************

if (!defined('SW_IG_PLUGIN_URL')) {
    define('SW_IG_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('SW_IG_ADMIN_PAGE_URL')) {
    define('SW_IG_ADMIN_PAGE_URL', home_url('wp-admin/options-general.php?page=sw-classic-ig-admin-page', 'https'));
}

if (!defined('SW_IG_REMOVE_HASHTAGS')) {
    define('SW_IG_REMOVE_HASHTAGS', false);
}

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script('sw_ig_plugin_instagram_scripts', SW_IG_PLUGIN_URL . 'js/admin.js', ['jquery'], '1.0.0', false);
    wp_enqueue_style('sw_ig_plugin_instagram_style', SW_IG_PLUGIN_URL . 'css/admin.css', [], '1.0.0');
});

add_action('admin_notices', function () {
    $screen = get_current_screen();
    $errors = get_option('sw-ig-errors', []);

    if ($screen->id == 'settings_page_sw-classic-ig-admin-page' && !empty($errors)) {
        echo "<div id='sw-ig-admin-notices' class='notice notice-error is-dismissible'><ul>";
        foreach ($errors as $error) {
            echo "<li>- $error</li>";
        }
        echo "</ul></div>";
    }
});

require dirname(__FILE__) . '/vendor/autoload.php';
require dirname(__FILE__) . '/lib/ajax_functions.php';
require dirname(__FILE__) . '/lib/SWIGHelpers.php';
require dirname(__FILE__) . '/lib/FacebookApiSettings.php';
require dirname(__FILE__) . '/lib/FacebookApiConnector.php';

if (is_admin()) {
    add_action('admin_menu', function () {
        add_options_page('SW Classic IG Page', 'SW Classic IG Settings', 'manage_options', 'sw-classic-ig-admin-page', function () {
            ?>
            <div class="wrap">
                <h1>SW Classic Instagram Plugin Options</h1>

                <?php
                $isLoginCallback = isset($_GET['fb_login_callback']);
                if ($isLoginCallback) {
                    require dirname(__FILE__) . '/lib/LoginCallbackHandler.php';
                    LoginCallbackHandler::handleCallback();
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