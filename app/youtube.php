<?php

if (count($argv) < 2) {
    exit('second argument $argv must be specified by Youtube url'. PHP_EOL);
}

use App\Anet\Bots\YouTubeBot;
use Google\Service\Exception;

require_once __DIR__ . '/vendor/autoload.php';

connecting:

try {
    $youtubeBot = new YouTubeBot($argv[1]);
} catch (Exception $error) {
    print_r($error->getMessage());
    return 0;
}

if (isset($argv[2])) {
    switch ($argv[2]) {
        case 'test_connect':
            $youtubeBot->testConnect();
            return 0;
        case 'test_send':
            $youtubeBot->testSend();
            return 0;
        default:
            echo 'Incorrect bot check parameter.' . PHP_EOL;
            echo 'Enter "test_connect" to check the connection and display the current chat' . PHP_EOL;
            echo 'Or "test_send" to check if the message has been sent' . PHP_EOL;
            return 0;
    }
}

$youtubeBot->listen(15);

unset($youtubeBot);
