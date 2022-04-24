<?php

if (! defined('SETTING_DIR')) {
    define('SETTING_DIR', __DIR__ . '/settings');
}

if (! is_dir(SETTING_DIR)) {
    throw new \Exception('Create ' . SETTING_DIR);
}

if (! defined('CLIENT_SECRET_JSON')) {
    $secret = array_values(array_filter(scandir(SETTING_DIR), fn ($item) => preg_match('/client_secret.*\.json/', $item)));

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

if (! defined('OAUTH_TOKEN_JSON')) {
    define('OAUTH_TOKEN_JSON', SETTING_DIR . '/oauth_token.json');
}
