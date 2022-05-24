<?php

if (! defined('SETTING_DIR')) {
    define('SETTING_DIR', __DIR__ . '/secrets');
}

if (! is_dir(SETTING_DIR)) {
    throw new \Exception('Create ' . SETTING_DIR);
}

if (! defined('CLIENT_SECRET_JSON')) {
    $secret = array_values(array_filter(scandir(SETTING_DIR), fn ($item) => preg_match('/^client_secret.*\.json/', $item)));

    switch (count($secret)) {
        case 1:
            break;
        case 0:
            throw new \Exception('Create oAuth client_secret file in [' . SETTING_DIR . '] directory');
            break;
        default:
            throw new \Exception('Too many oAuth client_secret files in [' . SETTING_DIR . '] directory');
            break;
    }

    define('CLIENT_SECRET_JSON', SETTING_DIR . "/{$secret[0]}");
}

if (! defined('SMALL_TALK_API_KEY')) {
    $secret = array_values(array_filter(scandir(SETTING_DIR), fn ($item) => preg_match('/^small-talk.*\.json/', $item)));

    switch (count($secret)) {
        case 1:
            break;
        case 0:
            throw new \Exception('Create oAuth client_secret file in [' . SETTING_DIR . '] directory');
            break;
        default:
            throw new \Exception('Too many oAuth client_secret files in [' . SETTING_DIR . '] directory');
            break;
    }

    define('SMALL_TALK_API_KEY', SETTING_DIR . "/{$secret[0]}");
}

if (! defined('OAUTH_TOKEN_JSON')) {
    define('OAUTH_TOKEN_JSON', SETTING_DIR . '/oauth_token.json');
}

if (! defined('LOGS_PATH')) {
    define('LOGS_PATH', dirname(__DIR__, 2) . '/project_logs');
}

if (! defined('DB_HOST')) {
    define('DB_HOST', 'db');
}

if (! defined('DB_BASE')) {
    define('DB_BASE', 'bots');
}

if (! defined('REDIS_HOST')) {
    define('REDIS_HOST', 'redis');
}

if (! defined('REDIS_PORT')) {
    define('REDIS_PORT', 6379);
}
