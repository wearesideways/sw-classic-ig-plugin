### Plugin Installed on projects

- Hotels G
- Hotel Zephyr
- Moxy _(read below)_

### Features

- Ability to log in with multiple FB business accounts
- Manually sync on each account
- Automatic daily sync on all accounts (checkbox should be enabled on the plugin settings page)

### Technical Notes

- Each sync will fetch the last 20 posts
- During the fb account login, the plugin requests to facebook for long lived token.
- It has a feature to send Slack notifications if the import (manual or auto) fail, those notification will to the Slack's channel "ig-plugin-notifications" - [https://sideways-nyc.slack.com/archives/C019C7PHYKB](https://sideways-nyc.slack.com/archives/C019C7PHYKB)"
- If you need to re-login an existing account just do the normal login workflow on the settings page and the plugin will replace the existing token with the new one

### Login callback handler

- Repo: [https://github.com/wearesideways/sw-classic-ig-login-handler](https://github.com/wearesideways/sw-classic-ig-login-handler)
- Hosted: [Serverless.com](http://serverless.com) / Sideways organization
- URL: [https://sw-classic-ig-plugin.sidewaysdigital.com/sw_classic_redirect](https://sw-classic-ig-plugin.sidewaysdigital.com/sw_classic_redirect)
- App Env: prod

**Notes**

This is just a simple serverless app that works as proxy during the fb login workflow, is hosted in Serverless ([https://app.serverless.com/sideways/apps/sw-classic-ig/sw-classic-ig-login-handler/prod/us-east-1](https://app.serverless.com/sideways/apps/sw-classic-ig/sw-classic-ig-login-handler/prod/us-east-1)) under the sideways organization.

It has just one single endpoint that redirects you to the project where you are trying to login, it is required by the FB graph api and we use this to prevent adding a new redirect url on each instance where the plugin is installed.

**This is important in order to be able to login fb accounts**

### Facebook Account

All the information about the facebook application (keys, app_id, app_token etc...) is under the Sideways Facebook business account, that means everyone in that account has access to the SW IG Classic plugin project, including Luis.

### Moxy Status

The plugin has been added to the repo and is up to date but **it was never configured and tested** in production due to the situation with the client client (no budget).

That means the old plugin "designworks" is still installed at the same time, none of both works but just have in mind that there is some pending work to do here.

The plugin should run without any issue as it works on the other projects, just need to configure and try.