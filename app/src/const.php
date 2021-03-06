<?php

Dotenv\Dotenv::createImmutable(__DIR__)->load();

if (! defined('SETTING_DIR')) {
    define('SETTING_DIR', __DIR__ . '/secrets');

    if (! is_dir(SETTING_DIR)) {
        throw new \Exception('Create ' . SETTING_DIR);
    }
}

if (! defined('YOUTUBE_SECRETS')) {
    define('YOUTUBE_SECRETS', SETTING_DIR . '/youtube');

    if (! is_dir(YOUTUBE_SECRETS)) {
        throw new \Exception('Create ' . YOUTUBE_SECRETS);
    }
}

if (! defined('YOUTUBE_CLIENT_SECRET_JSON')) {
    $secret = array_values(array_filter(scandir(YOUTUBE_SECRETS), fn ($item) => preg_match('/^client_secret.*\.json/', $item)));

    switch (count($secret)) {
        case 1:
            break;
        case 0:
            throw new \Exception('Create oAuth client_secret file in [' . YOUTUBE_SECRETS . '] directory');
            break;
        default:
            throw new \Exception('Too many oAuth client_secret files in [' . YOUTUBE_SECRETS . '] directory');
            break;
    }

    define('YOUTUBE_CLIENT_SECRET_JSON', YOUTUBE_SECRETS . "/{$secret[0]}");
}

if (! defined('YOUTUBE_OAUTH_TOKEN_JSON')) {
    define('YOUTUBE_OAUTH_TOKEN_JSON', YOUTUBE_SECRETS . '/oauth_token.json');
}

if (! defined('SMALL_TALK_API_KEY')) {
    $secret = array_values(array_filter(scandir(SETTING_DIR . '/dialogflow'), fn ($item) => preg_match('/^small-talk.*\.json/', $item)));

    switch (count($secret)) {
        case 1:
            break;
        case 0:
            throw new \Exception('Create dialogflow oAuth client_secret file in [' . SETTING_DIR . '/dialogflow/] directory');
            break;
        default:
            throw new \Exception('Too many dialogflow oAuth client_secret files in [' . SETTING_DIR . '/dialogflow/] directory');
            break;
    }

    define('SMALL_TALK_API_KEY', SETTING_DIR . "/dialogflow/{$secret[0]}");
}

if (! defined('LOGS_PATH')) {
    define('LOGS_PATH', $_ENV['PROJECT_LOGS_CONTAINER']);
}

if (! defined('DB_HOST')) {
    define('DB_HOST', $_ENV['DB_SERVICE_NAME']);
}

if (! defined('DB_USER_NAME')) {
    define('DB_USER_NAME', $_ENV['DB_USER_NAME']);
}

if (! defined('DB_BASE')) {
    define('DB_BASE', $_ENV['DB_SQL_DATABASE']);
}

if (! defined('DB_PASSWORD')) {
    define('DB_PASSWORD', $_ENV['DB_PASSWORD']);
}

if (! defined('REDIS_HOST')) {
    define('REDIS_HOST', $_ENV['REDIS_SERVICE_NAME']);
}

if (! defined('REDIS_PORT')) {
    define('REDIS_PORT', $_ENV['REDIS_PORT']);
}

if (! defined('REDIS_PASS')) {
    define('REDIS_PASS', $_ENV['REDIS_PASSWORD']);
}
