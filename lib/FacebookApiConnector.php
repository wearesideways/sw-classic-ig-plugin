<?php

namespace SwIgPlugin;

use JanuSoftware\Facebook\Facebook;

class FacebookApiConnector
{
    private $appId = '';
    private $appSecret = '';
    private $graphVersion = 'v2.10';

    public function __construct()
    {
        if (defined('SW_IG_FB_APP_ID')) {
            $this->appId = SW_IG_FB_APP_ID;
        }
        if (defined('SW_IG_FB_APP_SECRET')) {
            $this->appSecret = SW_IG_FB_APP_SECRET;
        }
    }

    public function getClient()
    {
        return new Facebook([
            'app_id' => (string)$this->appId,
            'app_secret' => $this->appSecret,
            'default_graph_version' => $this->graphVersion,
        ]);
    }

    public function getLoginUrl()
    {
        $fbSettings = new FacebookApiSettings();

        $helper = $this->getClient()->getRedirectLoginHelper();
        $helper->getPersistentDataHandler()->set('state', base64_encode($fbSettings->getRedirectCallbackUrl()));

        return $helper->getLoginUrl($fbSettings->getCallbackUrl(), $fbSettings->getLoginPermissions());
    }
}
