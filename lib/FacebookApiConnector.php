<?php

class FacebookApiConnector
{
    protected $appId = '';
    protected $appSecret = '';
    protected $graphVersion = 'v2.10';

    public function __construct()
    {
        if (defined('FB_APP_ID')) {
            $this->appId = FB_APP_ID;
        }
        if (defined('FB_APP_SECRET')) {
            $this->appSecret = FB_APP_SECRET;
        }
    }

    public function getClient()
    {
        return new \Facebook\Facebook([
            'app_id' => $this->appId,
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
