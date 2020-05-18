<?php

namespace SwIgPlugin;

class FacebookApiSettings
{
    public function getLoginPermissions()
    {
        //pages_read_user_content -- consider this if you have issues with permissions
        return ['instagram_basic, pages_show_list'];
    }

    public function getCallbackUrl()
    {
        return 'https://sw-classic-ig-plugin.sidewaysdigital.com/sw_classic_redirect';
    }

    public function getRedirectCallbackUrl()
    {
        return add_query_arg(['fb_login_callback' => true], SW_IG_ADMIN_PAGE_URL);
    }
}