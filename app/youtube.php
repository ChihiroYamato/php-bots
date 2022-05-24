<?php

if (count($argv) < 2) {
    exit('second argument $argv must be specified by Youtube url'. PHP_EOL);
}

use App\Anet\Bots;
use App\Anet\Helpers;
use Google\Service;

require_once __DIR__ . '/vendor/autoload.php';

do {
    $restart = false;

    try {
        $youtubeBot = new Bots\YouTubeBot($argv[1]);
    } catch (Service\Exception $error) {
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

    $error = json_decode((string) Helpers\Loger::fetchLastNode($youtubeBot->getName(), 'error')->message, true)['error'] ?? null;

    if ($error !== null && $error['code'] === 401 && mb_strpos($error['message'], 'Request had invalid authentication credentials. Expected OAuth 2 access token') !== false) {
        unset($youtubeBot);
        sleep(60);
        Helpers\Loger::print('System', 'system restarting script by code <failed oAuth>');
        $restart = true;
    }
} while ($restart);
