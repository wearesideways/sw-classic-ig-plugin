<?php

namespace SwIgPlugin;

class SWIGHelpers
{
    public function getPluginSettings()
    {
        $defaults = [
            'post_type' => 'instagram',
            'post_status' => 'publish',
            'accounts' => []
        ];

        $savedSettings = get_option('sw-ig-settings', []);

        $settings = array_merge($defaults, $savedSettings);

        //************************************************************************************
        //Will be removed until UX get options to update this
        if (defined('SW_IG_POST_TYPE')) {
            $settings['post_type'] = SW_IG_POST_TYPE;
        }

        if (defined('SW_IG_POST_STATUS')) {
            $settings['post_status'] = SW_IG_POST_STATUS;
        }
        //************************************************************************************
        return $settings;
    }

    public function addMessageError($error)
    {
        $errors = get_option('sw-ig-errors', []);
        $errors[] = $error;
        update_option('sw-ig-errors', $errors);
    }
}