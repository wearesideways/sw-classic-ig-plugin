<?php
// Offline compatibility checks: every HTTP response comes from Guzzle MockHandler.
require dirname(__DIR__) . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Http\Adapter\Guzzle7\Client as Adapter;
use JanuSoftware\Facebook\Authentication\AccessToken;
use JanuSoftware\Facebook\Exception\ResponseException;
use SwIgPlugin\FacebookApiConnector;

function expect($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
define('SW_IG_FB_APP_ID', '123456');
define('SW_IG_FB_APP_SECRET', 'offline-app-secret');
define('SW_IG_ADMIN_PAGE_URL', 'https://wordpress.invalid/wp-admin/options-general.php');
session_start();

$connector = new FacebookApiConnector();
$client = $connector->getClient();
expect($client->getDefaultGraphVersion() === 'v2.10', 'Existing Graph version changed');
expect($client->getClient()->getHttpClient() instanceof \Psr\Http\Client\ClientInterface, 'HTTP discovery must resolve a PSR-18 client');
$loginUrl = $connector->getLoginUrl();
expect(str_contains($loginUrl, 'dialog/oauth'), 'Login URL missing OAuth endpoint');
expect(str_contains($loginUrl, 'client_id=123456'), 'Login URL missing app id');
expect(str_contains($loginUrl, 'sw-classic-ig-plugin.sidewaysdigital.com'), 'Callback URL changed');

$history = [];
$mock = new MockHandler([
    new Response(200, [], '{"id":"user-1","name":"Offline Person"}'),
    new Response(200, [], '{"data":[{"id":"page-1","name":"Offline Page"}]}'),
    new Response(200, [], '{"data":[{"id":"image-1","media_type":"IMAGE","caption":"Offline caption"}]}'),
    new Response(400, [], '{"error":{"message":"Offline invalid token","type":"OAuthException","code":190}}'),
]);
$stack = HandlerStack::create($mock);
$stack->push(Middleware::history($history));
$client->getClient()->setHttpClient(new Adapter(new Client(['handler' => $stack, 'http_errors' => false])));
$user = json_decode($client->get('/me?fields=id,name', 'offline-token')->getBody());
expect($user->id === 'user-1', 'User body decoding failed');
$pages = json_decode($client->get('/user-1/accounts', 'offline-token')->getBody());
expect($pages->data[0]->id === 'page-1', 'Page body decoding failed');
$media = json_decode($client->get('/ig-1/media?fields=id,media_type,caption&limit=20', 'offline-token')->getBody());
expect($media->data[0]->media_type === 'IMAGE', 'Media body decoding failed');
try {
    $client->get('/me', 'invalid-offline-token');
    throw new RuntimeException('Graph error was not propagated');
} catch (ResponseException $e) {
    expect($e->getCode() === 190, 'Graph error code changed');
    expect(str_contains($e->getMessage(), 'Offline invalid token'), 'Graph error message changed');
}
expect(count($history) === 4, 'Unexpected number of mocked requests');
foreach ($history as $transaction) {
    expect($transaction['request']->getMethod() === 'GET', 'Importer reads must remain GET requests');
    expect(str_contains($transaction['request']->getUri()->getPath(), '/v2.10/'), 'Graph request version changed');
}
$short = new AccessToken('short-offline-token', time() + 3600);
$long = new AccessToken('long-offline-token', time() + 60 * 86400);
expect(!$short->isLongLived() && $long->isLongLived(), 'Token lifetime API changed');
expect($long->getValue() === 'long-offline-token', 'Token value API changed');
echo "SDK compatibility passed: discovered PSR-18 transport, login URL, token API, three GETs and Graph error.\n";
