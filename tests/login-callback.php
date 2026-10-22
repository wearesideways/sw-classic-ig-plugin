<?php
namespace SwIgPlugin {
    // Isolate WordPress and the connector, so callback tests cannot write or notify externally.
    class FacebookApiConnector { public function getClient() { return $GLOBALS['offlineClient']; } }
    class SWIGHelpers {
        public function getPluginSettings() { return ['accounts' => [['id' => 'user-1', 'name' => 'Old account']]]; }
        public function addMessageError($message) { $GLOBALS['offlineErrors'][] = $message; }
    }
}
namespace {
    require dirname(__DIR__) . '/vendor/autoload.php';
    use JanuSoftware\Facebook\Authentication\AccessToken;
    use JanuSoftware\Facebook\Exception\SDKException;
    use JanuSoftware\Facebook\Exception\ResponseException;
    use JanuSoftware\Facebook\Facebook;
    use GuzzleHttp\Handler\MockHandler;
    use GuzzleHttp\HandlerStack;
    use GuzzleHttp\Psr7\Response;
    use Http\Adapter\Guzzle7\Client as Adapter;
    function expect($condition, $message) { if (!$condition) { throw new \RuntimeException($message); } }
    function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
    function update_option($key, $value) { $GLOBALS['offlineWrites'][] = [$key, $value]; }
    class CallbackEnded extends \RuntimeException {}
    function wp_die() { throw new CallbackEnded(); }
    define('SW_IG_ADMIN_PAGE_URL', 'https://wordpress.invalid/admin');
    class OfflineHelper {
        public $state;
        public function __construct(public $mode) {}
        public function getPersistentDataHandler() { return $this; }
        public function set($key, $value) { $this->state = [$key, $value]; }
        public function getAccessToken($url) {
            if ($this->mode === 'sdk-error') { throw new SDKException('Offline state invalid'); }
            return new AccessToken('short-offline-token', time() + 3600);
        }
    }
    class OfflineClient {
        public $helper;
        public $exchanges = 0;
        public function __construct(public $sdk, $mode) { $this->helper = new OfflineHelper($mode); }
        public function getRedirectLoginHelper() { return $this->helper; }
        public function getOAuth2Client() { return $this; }
        public function getLongLivedAccessToken($token) { $this->exchanges++; return new AccessToken('long-offline-token', time() + 60 * 86400); }
        public function get($path, $token) { return $this->sdk->get($path, $token); }
    }
    foreach (['success', 'graph-error', 'sdk-error'] as $mode) {
        $GLOBALS['offlineErrors'] = [];
        $GLOBALS['offlineWrites'] = [];
        $responses = $mode === 'graph-error'
            ? [new Response(400, [], '{"error":{"message":"Offline access denied","code":190}}')]
            : [new Response(200, [], '{"id":"user-1","name":"New account"}'),
               new Response(200, [], '{"data":[{"id":"page-1","name":"Offline Page"}]}'),
               new Response(200, [], '{"instagram_business_account":{"id":"ig-1","username":"offline_ig"}}')];
        $sdk = new Facebook(['app_id' => '123456', 'app_secret' => 'offline-secret', 'default_graph_version' => 'v2.10',
            'http_client' => new Adapter(new \GuzzleHttp\Client(['handler' => HandlerStack::create(new MockHandler($responses)), 'http_errors' => false]))]);
        $GLOBALS['offlineClient'] = new OfflineClient($sdk, $mode);
        ob_start();
        try { \SwIgPlugin\LoginCallbackHandler::handleCallback(); } catch (CallbackEnded $e) {}
        $output = ob_get_clean();
        expect(str_contains($output, 'https://wordpress.invalid/admin'), 'Callback redirect changed');
        if ($mode === 'success') {
            expect(count($GLOBALS['offlineWrites']) === 1, 'Expected one stubbed settings write');
            $accounts = $GLOBALS['offlineWrites'][0][1]['accounts'];
            expect(count($accounts) === 1 && $accounts[0]['name'] === 'New account', 'Account replacement failed');
            expect($accounts[0]['access_token'] === 'long-offline-token', 'Long-lived token not retained');
            expect($accounts[0]['pages'][0]['ig_user_id'] === 'ig-1', 'Instagram account mapping failed');
            expect($GLOBALS['offlineClient']->exchanges === 1, 'Token exchange not invoked');
        } else {
            expect(count($GLOBALS['offlineErrors']) === 1, 'SDK exception was not handled');
            expect(!$GLOBALS['offlineWrites'], 'Error path must not write settings');
            $prefix = $mode === 'graph-error' ? 'Graph returned an error:' : 'Facebook SDK returned an error:';
            expect(str_starts_with($GLOBALS['offlineErrors'][0], $prefix), 'Wrong exception catch selected');
        }
    }
    echo "Callback compatibility passed: success/account replacement, token exchange, Graph error and SDK error.\n";
}
