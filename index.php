<?php

require_once __DIR__ . '/src/vendor/autoload.php';

use Anet\App\Bots\YouTubeBot;

if (YouTubeBot::createAuthTokken()) {
    echo 'oAuth tokken saved successful' . PHP_EOL;
} else {
    echo 'file with oAuth tokken is already exist' . PHP_EOL;
}

exit;
