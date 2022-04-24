<?php

require_once __DIR__ . '/src/vendor/autoload.php';

if (! file_exists(OAUTH_TOKEN_JSON)) {
    echo 'Create file with oAuth tokken';
    die;
}

use Anet\App\Helpers\Timer;
use Anet\App\Helpers\TimeTracker;
use Google\Client;
use Google\Service\YouTube;

$timeTracker = new TimeTracker(); // TODO ------------------------------------- TIMER

$client = new Client();
$client->setApplicationName('Yamato-Chat-Bot');

$client->setAuthConfig(CLIENT_SECRET_JSON);
$client->setAccessType('offline');

$client->setScopes([
    YouTube::YOUTUBE_FORCE_SSL,
    YouTube::YOUTUBE_READONLY,
]);
$client->setLoginHint('alexan9610@gmail.com');
$client->setAccessToken(json_decode(file_get_contents(OAUTH_TOKEN_JSON), true));

$timeTracker->setPoint('login'); // TODO ------------------------------------- TIMER

$service = new YouTube($client);

$responseLiveChatID = $service->videos->listVideos('liveStreamingDetails', ['id' => 'Eb_CKBVVelc']); // TODO ---------- add auto parse

$liveChatID = $responseLiveChatID['items'][0]['liveStreamingDetails']['activeLiveChatId'] ?? null;

if ($liveChatID === null) {
    throw new Exception('Error response with live chat ID');
}

$lastMessageID = null;

// for ($i = 0; $i < 1; $i++) {
    $responseMessageList = $service->liveChatMessages->listLiveChatMessages($liveChatID, 'snippet', ['maxResults' => 100]);

    $messageList = $responseMessageList['items'];
    $currentMessages = [];
    $writeMod = ($lastMessageID === null);

    foreach ($messageList as $mess) {
        if ($writeMod) {
            $responseChannelID = $service->channels->listChannels('snippet', ['id' => $mess['snippet']['authorChannelId']]);

            $currentMessages[] = [
                'id' => $mess['id'],
                'authorId' => $mess['snippet']['authorChannelId'],
                'authorName' => $responseChannelID['items'][0]['snippet']['title'] ?? '',
                'message' => $mess['snippet']['displayMessage'],
                'published' => $mess['snippet']['publishedAt'],
            ];
        }

        if ($mess['id'] === $lastMessageID) {
            $writeMod = true;
        }
    }

    if (! empty($currentMessages)) {
        $lastMessageID = $currentMessages[count($currentMessages) - 1]['id'];
    }

    // var_dump($currentMessages);


    $liveChatMessage = new Google\Service\YouTube\LiveChatMessage();
    $liveChatMessageSnippet = new Google\Service\YouTube\LiveChatMessageSnippet();
    $liveChatTextMessageDetails = new Google\Service\YouTube\LiveChatTextMessageDetails();

    $liveChatTextMessageDetails->setMessageText('well, I must go, bb, good game');

    $liveChatMessageSnippet->setLiveChatId($liveChatID);
    $liveChatMessageSnippet->setType('textMessageEvent');
    $liveChatMessageSnippet->setTextMessageDetails($liveChatTextMessageDetails);

    $liveChatMessage->setSnippet($liveChatMessageSnippet);

    try {
        $responseSendMess = $service->liveChatMessages->insert('snippet', $liveChatMessage);
        var_dump($responseSendMess);
    } catch (Google\Service\Exception $error) {
        var_dump($error->getMessage());
    }


    Timer::setSleep(10, 1);
// }

// file_put_contents('test-mess.json', json_encode($currentMessages, JSON_FORCE_OBJECT));

// foreach ($response['items'] as $item) {
//     var_dump($item);
// }

$timeTracker->setPoint('youtube'); // TODO ------------------------------------- TIMER

var_dump($timeTracker->getStatistic()); // TODO ------------------------------------- TIMER
