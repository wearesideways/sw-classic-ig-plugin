<?php

if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

$helpers = new SwIgPlugin\SWIGHelpers();
$settings = $helpers->getPluginSettings();

$fbConnector = new SwIgPlugin\FacebookApiConnector();
$loginUrl = $fbConnector->getLoginUrl();

echo '<a href="' . htmlspecialchars($loginUrl) . '" id="sw-ig-login-button"><img src="https://static.xx.fbcdn.net/rsrc.php/v3/y9/r/OF6ddsGKpeB.png" alt="">Connect new Facebook Account</a>';

//$removeHashtags = SW_IG_REMOVE_HASHTAGS ? 'yes' : 'no';
//echo "<table class='form-table'>
//<tr><th>Default Post Type</th><td>" . $settings['post_type'] . "</td></tr>
//<tr><th>Default Post Status</th><td>" . $settings['post_status'] . "</td></tr>
//<tr><th>Should remove hashtags?</th><td>" . $removeHashtags . "</td></tr>
//</table>";

echo "<h3 class='section-title'>Connected Accounts</h3>";

if (!empty($settings['accounts'])) {
    foreach ($settings['accounts'] as $account) {
        echo "<div class='sw-ig-account-panel'>
              <div class='sw-ig-account-panel-title'>" . $account['name'] . "
              <div class='sw-ig-account-panel-accounts'>";
        if (isset($account['pages'])) {
            foreach ($account['pages'] as $page) {
                echo "<span>" . $page['ig_username'] . "</span>";
            }
            echo "</div></div>
            <button type='button' class='button-primary sw-ig-plugin-sync-feed'
                    data-access-token='" . $account['access_token'] . "'>Import Instagram Posts</button>";
        } else {
            echo "<span>No Instagram Accounts Linked</span></div></div>";
        }
        echo "<span class='sw-ig-account-panel-status'></span></div>";
    }
} else {
    echo "<span>There are not configured accounts.</span>";
}