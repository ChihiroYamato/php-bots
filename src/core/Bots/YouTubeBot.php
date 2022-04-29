<?php

namespace Anet\App\Bots;

use Anet\App\Helpers;
use Anet\App\Helpers\Traits;
use Google;
use Google\Service;
use Google\Service\YouTube;

final class YouTubeBot extends ChatBotAbstract
{
    use Traits\UrlHelperTrait; // TODO ========== ErrorHelperTrait, LogHelperTrait

    private Service\YouTube $youtubeService;
    private Helpers\TimeTracker $timeTracker;
    private string $youtubeURL;
    private string $videoID;
    private string $botUserEmail;
    private string $botUserName;
    private string $liveChatID;
    private ?string $lastChatMessageID;

    public function __construct(string $youtubeURL)
    {
        if (! file_exists(OAUTH_TOKEN_JSON)) {
            throw new Service\Exception('Create file with oAuth tokken');
        }

        $this->timeTracker = new Helpers\TimeTracker();
        $this->errorList = [];
        $this->errorCount = 0;
        $this->youtubeURL = $this->validateYoutubeURL($youtubeURL);
        $this->videoID = $this->getVideoID($this->youtubeURL);
        $this->botUserEmail = APP_EMAIL;
        $this->botUserName = APP_USER_NAME;

        $client = self::createGoogleClient();

        $client->setAccessToken(json_decode(file_get_contents(OAUTH_TOKEN_JSON), true));

        $this->timeTracker->setPoint('login');

        $this->youtubeService = new Service\YouTube($client);

        $this->liveChatID = $this->getLiveChatID($this->videoID);
        $this->lastChatMessageID = null;

        $this->timeTracker->setPoint('getChatID');
    }

    private static function createGoogleClient(bool $setRedirectUrl = false) : Google\Client
    {
        $client = new Google\Client();

        $client->setApplicationName(APP_NAME);
        $client->setAuthConfig(CLIENT_SECRET_JSON);
        $client->setAccessType('offline');

        if ($setRedirectUrl) {
            $client->setRedirectUri(self::fetchCurrentUrl());
        }

        $client->setScopes([
            Service\YouTube::YOUTUBE_FORCE_SSL,
            Service\YouTube::YOUTUBE_READONLY,
        ]);
        $client->setLoginHint(APP_EMAIL);

        return $client;
    }

    public static function createAuthTokken() : bool
    {
        if (file_exists(OAUTH_TOKEN_JSON)) {
            return false;
        }

        $client = self::createGoogleClient(true);

        if (! isset($_GET['code'])) {
            header('Location: ' . $client->createAuthUrl());

            return false;
        }

        $client->fetchAccessTokenWithAuthCode($_GET['code']);
        file_put_contents(OAUTH_TOKEN_JSON, json_encode($client->getAccessToken(), JSON_FORCE_OBJECT));

        return true;
    }

    private function getLiveChatID(string $videoID) : string
    {
        $response = $this->youtubeService->videos->listVideos('liveStreamingDetails', ['id' => $videoID]);

        $liveChatID = $response['items'][0]['liveStreamingDetails']['activeLiveChatId'] ?? null;

        if ($liveChatID === null) {
            throw new Service\Exception('Error response with live chat ID');
        }

        return $liveChatID;
    }

    private function fetchChatList() : array
    {
        try {
            $response = $this->youtubeService->liveChatMessages->listLiveChatMessages($this->liveChatID, 'snippet', ['maxResults' => 100]);

            $chatList = $response['items'];
            $actualChat = [];
            $writeMod = false;

            foreach ($chatList as $mess) {
                if ($mess['id'] === $this->lastChatMessageID) {
                    $writeMod = true;
                } elseif ($writeMod) {
                    $responseChannelID = $this->youtubeService->channels->listChannels('snippet', ['id' => $mess['snippet']['authorChannelId']]);

                    $actualChat[] = [
                        'id' => $mess['id'],
                        'authorId' => $mess['snippet']['authorChannelId'],
                        'authorName' => $responseChannelID['items'][0]['snippet']['title'] ?? '',
                        'message' => $mess['snippet']['displayMessage'],
                        'published' => $mess['snippet']['publishedAt'],
                    ];
                }
            }

            $this->lastChatMessageID = array_pop($chatList)['id'];

            return $actualChat;
        } catch (Service\Exception $error) {
            $this->errorList['fetchChatList'][] = $error->getMessage();
            $this->errorCount++;

            return [];
        }
    }

    protected function prepareMessages(array $chatlist) : int // TODO ===========================================================
    {
        $sendingList = [];
        $sendCount = 0;

        if (empty($chatlist)) {
            return $sendCount;
        }

        foreach ($chatlist as $mess) {
            if ($mess['authorName'] !== $this->botUserName) {
                if (mb_stripos($mess['message'], $this->botUserName) !== false) {
                    $currentMessage = trim(mb_strtolower(preg_replace("/@?{$this->botUserName}/", '', $mess['message'])));
                    match ($currentMessage) {
                        // TODO =========================== функционал команд
                        'help', 'помощь' => $sendingList[] = '@' . $mess['authorName'] . ' Приветствую, в настоящий момент функционал дорабатывается, список команд будет доступен позднее',
                        default => $sendingList[] = "@{$mess['authorName']} " . $this->prepareSmartAnswer($currentMessage),
                    };
                } elseif ($mess['authorName'] === '____') {
                    $answer = $this->prepareSmartAnswer($mess['message'], false);

                    if (! empty($answer)) {
                        $sendingList[] = "@{$mess['authorName']} $answer";
                    }
                } else {
                    $matches = explode(' ', trim(str_replace(['!', ',', '.', '?'], '', $mess['message'])));
                    $currentMessage = mb_strtolower(array_pop($matches));
                    switch ($currentMessage) {
                        case 'да':
                        case 'da':
                            $sendingList[] = "@{$mess['authorName']} пᴎᴣдa";
                            var_dump('response yes'); // Todo =============================================
                            break;
                        case 'нет':
                        case 'net':
                            $sendingList[] = "@{$mess['authorName']} пᴎдoрa oтвет";
                            var_dump('response no'); // Todo =============================================
                            break;
                        case 'иксди':
                            $sendingList[] = "@{$mess['authorName']} нaxyй пойди";
                            var_dump('response no'); // Todo =============================================
                            break;
                    };
                }
            }
        }

        if (! empty($sendingList)) {
            var_dump($sendingList); // Todo =============================================
            foreach ($sendingList as $sending) {
                $sendCount += $this->sendMessage($sending);
                sleep(1); // Todo =============================================
            }
        }

        return $sendCount;
    }

    protected function sendMessage(string $message) : bool
    {
        try {
            $liveChatMessage = new YouTube\LiveChatMessage();
            $liveChatMessageSnippet = new YouTube\LiveChatMessageSnippet();
            $liveChatTextMessageDetails = new YouTube\LiveChatTextMessageDetails();

            $liveChatTextMessageDetails->setMessageText($message);

            $liveChatMessageSnippet->setLiveChatId($this->liveChatID);
            $liveChatMessageSnippet->setType('textMessageEvent');
            $liveChatMessageSnippet->setTextMessageDetails($liveChatTextMessageDetails);

            $liveChatMessage->setSnippet($liveChatMessageSnippet);

            $this->youtubeService->liveChatMessages->insert('snippet', $liveChatMessage);

            return true;
        } catch (Service\Exception $error) {
            $this->errorList['sendMessage'][] = $error->getMessage();
            $this->errorCount++;

            return false;
        }
    }

    private function validateYoutubeURL(string $url) : string
    {
        if (! preg_match('/https:\/\/www\.youtube\.com.*/', $url)) {
            throw new Service\Exception('Incorrect YouTube url');
        }

        return $url;
    }

    private function getVideoID(string $url) : string
    {
        preg_match('/youtube\.com\/watch\?.*v=([^&]+)/',  $url, $matches);

        if (empty($matches[1])) {
            throw new Service\Exception('Incorrect YouTube video ID');
        }

        return $matches[1];
    }

    public function listen(int $interval) : void
    {
        $sendingCount = 0;
        $sendingCount += $this->sendMessage('Всем привет, хорошего дня/вечера/ночи/утра');

        while ($this->errorCount < 5) {
            $this->timeTracker->setPoint('prepare');

            $chatList = $this->fetchChatList();
            $this->timeTracker->setPoint('fetchChatList');

            if (empty($chatList)) {
                if ($this->timeTracker->trackerState()) {
                    if ($this->timeTracker->trackerCheck(5 * 60)) {
                        $sendingCount += $this->sendMessage('Dead Chat'); // todo Баг с постоянной отправкой по таймеру
                        $this->timeTracker->trackerStop();
                    }
                } else {
                    $this->timeTracker->trackerStart();
                }
            } else {
                $sendingCount += $this->prepareMessages($chatList); // TODO ===== Log
            }

            $this->timeTracker->setPoint('sendingMessage');

            $sendingCount = 0;
            Helpers\Timer::setSleep($interval);
        }

        print_r($this->errorList);
    }

    public function testConnect() : void
    {
        $this->fetchChatList();

        if ($this->lastChatMessageID !== null && empty($this->errorList)) {
            echo 'Chat request tested successfully, current last mess ID:' . PHP_EOL;
            print_r($this->lastChatMessageID);
        } else {
            echo 'Testing Failed, Current Errors:' . PHP_EOL;
            print_r($this->errorList);
        }
    }

    public function testSend() : void
    {
        $testing = $this->sendMessage('Прогрев чата');

        if ($testing) {
            echo 'Message sending test completed successfully' . PHP_EOL;
        } else {
            echo 'Testing Failed, Current Errors:' . PHP_EOL;
            print_r($this->errorList);
        }
    }
}
