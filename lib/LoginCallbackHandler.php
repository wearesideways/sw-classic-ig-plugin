<?php

namespace SwIgPlugin;

class LoginCallbackHandler
{
    public static function handleCallback()
    {
        $fbSettings = new FacebookApiSettings();
        $fbConnector = new FacebookApiConnector();
        $fbClient = $fbConnector->getClient();
        $SWIGhelpers = new SWIGHelpers();

        $helper = $fbClient->getRedirectLoginHelper();
        $helper->getPersistentDataHandler()->set('state', base64_encode($fbSettings->getRedirectCallbackUrl()));

        try {
            $accessToken = $helper->getAccessToken($fbSettings->getCallbackUrl());

            // The OAuth 2.0 client handler helps us manage access tokens
            $oAuth2Client = $fbClient->getOAuth2Client();

            if (!$accessToken->isLongLived()) {
                // Exchanges a short-lived access token for a long-lived one
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            }

            $userAccount = [];
            $userAccount['access_token'] = $accessToken->getValue();

            $helpers = new SWIGHelpers();
            $settingsArgs = $helpers->getPluginSettings();

            $userRequest = $fbClient->get('/me?fields=id,name', $userAccount['access_token']);
            $fbUser = json_decode($userRequest->getBody());

            $userAccount['id'] = $fbUser->id;
            $userAccount['name'] = $fbUser->name;

            //Check the logged account already exists, if so then remove it
            foreach ($settingsArgs['accounts'] as $index => $account) {
                if ($userAccount['id'] == $account['id']) {
                    unset($settingsArgs['accounts'][$index]);
                }
            }
            $settingsArgs['accounts'] = array_values($settingsArgs['accounts']);

            //Get User Pages
            $pagesRequest = $fbClient->get('/' . $userAccount['id'] . '/accounts', $userAccount['access_token']);
            $pagesResponse = json_decode($pagesRequest->getBody());

            foreach ($pagesResponse->data as $key => $page) {
                //Get Instagram USER ID
                $IGUserRequest = $fbClient->get('/' . $page->id . '?fields=instagram_business_account{id,username}', $userAccount['access_token']);
                $IGUserResponse = json_decode($IGUserRequest->getBody());

                //We add the pages just if the FB Page has an IG page linked
                if (isset($IGUserResponse->instagram_business_account)) {
                    $userAccount['pages'][$key]['id'] = $page->id;
                    $userAccount['pages'][$key]['name'] = $page->name;
                    $userAccount['pages'][$key]['ig_user_id'] = $IGUserResponse->instagram_business_account->id;
                    $userAccount['pages'][$key]['ig_username'] = $IGUserResponse->instagram_business_account->username;
                }
            }
            $settingsArgs['accounts'][] = $userAccount;
            update_option('sw-ig-settings', $settingsArgs);

            echo "<script>window.location.href = '" . SW_IG_ADMIN_PAGE_URL . "'</script>";
            wp_die();

        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            $SWIGhelpers->addMessageError('Graph returned an error: ' . $e->getMessage());
            wp_die();
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            $SWIGhelpers->addMessageError('Facebook SDK returned an error: ' . $e->getMessage());
            wp_die();
        }
    }
}