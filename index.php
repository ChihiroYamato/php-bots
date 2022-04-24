<?php

require_once __DIR__ . '/src/vendor/autoload.php';

if (file_exists(OAUTH_TOKEN_JSON)) {
    echo 'file with oAuth tokken is already exist' . PHP_EOL;
    die;
}

use Google\Client;
use Google\Service\YouTube;

$client = new Client();
$client->setApplicationName(APP_NAME);

$client->setAuthConfig(CLIENT_SECRET_JSON);

$current_url = strtolower(preg_replace('/\/.*/', '://', $_SERVER['SERVER_PROTOCOL'])) . $_SERVER['HTTP_HOST'] . preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
$client->setRedirectUri($current_url);

$client->setAccessType('offline');

$client->setScopes([
    YouTube::YOUTUBE_FORCE_SSL,
    YouTube::YOUTUBE_READONLY,
]);
$client->setLoginHint(APP_EMAIL);

if (! isset($_GET['code'])) {
    $authUrl = $client->createAuthUrl();
    header('Location: ' . $authUrl);
    die;
} else {
    $client->fetchAccessTokenWithAuthCode($_GET['code']);
    file_put_contents(OAUTH_TOKEN_JSON, json_encode($client->getAccessToken(), JSON_FORCE_OBJECT));

    echo 'oAuth tokken saved successful' . PHP_EOL;
}
