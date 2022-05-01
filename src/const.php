<?php

if (! defined('SETTING_DIR')) {
    define('SETTING_DIR', __DIR__ . '/settings');
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

if (! defined('SRC_VOCABULARY')) {
    define('SRC_VOCABULARY', __DIR__ . '/vocabulary');
}

if (! defined('VOC_STANDART')) {
    define('VOC_STANDART', SRC_VOCABULARY . '/standart.php');
}

if (! defined('VOC_NO_ANSWER')) {
    define('VOC_NO_ANSWER', SRC_VOCABULARY . '/no_answer.php');
}

if (! defined('VOC_NO_CARE')) {
    define('VOC_NO_CARE', SRC_VOCABULARY . '/no_care.php');
}

if (! defined('VOC_DEAD_CHAT')) {
    define('VOC_DEAD_CHAT', SRC_VOCABULARY . '/dead_chat.php');
}

if (! defined('VOC_DEAD_INSIDE')) {
    define('VOC_DEAD_INSIDE', SRC_VOCABULARY . '/dead_inside.php');
}

if (! defined('VOC_ANOTHER')) {
    if (file_exists(SRC_VOCABULARY . '/another_close.php')) {
        define('VOC_ANOTHER', SRC_VOCABULARY . '/another_close.php');
    } else {
        define('VOC_ANOTHER', SRC_VOCABULARY . '/another.php');
    }
}

if (! defined('LOGS_PATH')) {
    define('LOGS_PATH', __DIR__ . '/project_logs');
}
