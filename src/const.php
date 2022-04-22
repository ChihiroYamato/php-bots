<?php

define('SETTING_DIR', __DIR__ . '/settings');

if (! is_dir(SETTING_DIR)) {
    throw new \Exception('Create ' . SETTING_DIR);
}

$secret = array_values(array_filter(scandir(SETTING_DIR), fn ($item) => preg_match('/client_secret.*\.json/', $item)));
if (count($secret) > 1) {
    throw new \Exception('Too many client_secret files');
}

define('CLIENT_SECRET_JSON', SETTING_DIR . "/{$secret[0]}");
