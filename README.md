# SW Classic Instagram Plugin

Custom Wordpress plugin to import Instagram posts to WP posts (This is a replacement for the dsgnwrks-instagram-importer plugin).

### Configuration Vars
You must define following keys to be able to connect with the FB Graph API:

`define('SW_IG_FB_APP_ID', 'XXXX');`

`define('SW_IG_FB_APP_SECRET', 'XXXXX');`

### Plugin Settings
 You can set different settings by project, you must define them on your local-config.php (or wp-config.php) file:
 
- Post Type Name: `define('SW_IG_POST_TYPE', 'instagram')`
- Default Post Status: `define('SW_IG_POST_STATUS', 'publish')`
- On/Off to remove hashtags from title (dsgnwrks remove from post_title, post_content, post_excerpt): `define('SW_IG_REMOVE_HASHTAGS', false)`

### Features
- Plugin settings page to login and sync feed.
- Login with FB Business Accounts (must have an Instagram Page Linked)
    - Can login multiple accounts and each account can have many pages/ig_pages linked to it.
- Import the last 20 Instagram posts as WP Posts.
    - It has validations to prevent duplicated entries compared with the current records on the database.
    - The import is manually.
- Custom settings depending on the project
    - Set custom posts type name
    - Set default status post
    - Set on/off remove hashtags on IG posts title