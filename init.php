<?php

if (count($argv) < 2) {
    exit('second argument $argv must be specified by Youtube url'. PHP_EOL);
}

use Anet\App\Bots\YouTubeBot;
use Google\Service\Exception;

require_once __DIR__ . '/src/vendor/autoload.php';

try {
    // $youtubeBot = new YouTubeBot($argv[1]);
} catch (Exception $error) {
    var_dump($error);
    exit;
}
