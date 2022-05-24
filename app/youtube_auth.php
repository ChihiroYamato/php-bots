<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Anet\Bots;

if (Bots\YouTube::createAuthTokken()) {
    echo 'oAuth tokken saved successful' . PHP_EOL;
} else {
    echo 'file with oAuth tokken is already exist' . PHP_EOL;
}

return 0;
