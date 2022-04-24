<?php

define('DEFAULT_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36');
define('SETTING_DIR', __DIR__ . '/settings');

if (! is_dir(SETTING_DIR)) {
    throw new \Exception('Create ' . SETTING_DIR);
}

$secret = array_values(array_filter(scandir(SETTING_DIR), fn ($item) => preg_match('/client_secret.*\.json/', $item)));
if (count($secret) > 1) {
    throw new \Exception('Too many client_secret files');
}

define('CLIENT_SECRET_JSON', SETTING_DIR . "/{$secret[0]}");
define('OAUTH_TOKEN_JSON', SETTING_DIR . '/oauth_token.json');
