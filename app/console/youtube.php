<?php

if (count($argv) < 2) {
    exit('second argument $argv must be specified by Youtube url'. PHP_EOL);
}

use Anet\App\Bots;
use Anet\App\Helpers;
use Anet\App\YouTubeHelpers;
use Google\Service;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$connectParams = new YouTubeHelpers\ConnectParams(
    YOUTUBE_APP_NAME,
    YOUTUBE_CLIENT_SECRET_JSON,
    YOUTUBE_OAUTH_TOKEN_JSON
);
$spareConnection = true;

do {
    $restart = false;

    try {
        $youtubeBot = new Bots\YouTube($connectParams, $argv[1]);
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

    $error = json_decode((string) Helpers\Logger::fetchLastNode($youtubeBot->getName(), 'error')->message, true)['error'] ?? null;

    if ($youtubeBot->isListening()) {
        if ($error !== null && $error['code'] === 401 && mb_strpos($error['message'], 'Request had invalid authentication credentials. Expected OAuth 2 access token') !== false) {
            unset($youtubeBot);
            sleep(60);
            Helpers\Logger::print('System', 'system restarting script by code <failed oAuth>');
            $restart = true;
        } elseif($spareConnection && $error !== null && $error['code'] === 403 && mb_strpos($error['message'], 'The request cannot be completed because you have exceeded your') !== false) {
            $spareConnection = false;
            unset($youtubeBot);
            sleep(10);
            Helpers\Logger::print('System', 'system restarting script by code <exceeded quota>');
            $connectParams = new YouTubeHelpers\ConnectParams(
                YOUTUBE_APP_NAME_RESERVE,
                YOUTUBE_CLIENT_SECRET_JSON_RESERVE,
                YOUTUBE_OAUTH_TOKEN_JSON_RESERVE
            );
            $restart = true;
        }
    }
} while ($restart);
