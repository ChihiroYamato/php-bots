<?php

namespace Anet\App\Bots;

use Anet\App\Helpers\Timer;
use Anet\App\Helpers\TimeTracker;
use Anet\App\Helpers\Traits\UrlHelper;
use Google\Client;
use Google\Service\YouTube;
use Google\Service\Exception;
use Google\Service\YouTube\LiveChatMessage;
use Google\Service\YouTube\LiveChatMessageSnippet;
use Google\Service\YouTube\LiveChatTextMessageDetails;

final class YouTubeBot extends ChatBot
{
    use UrlHelper;

    private YouTube $youtubeService;
    private TimeTracker $timeTracker;
    private string $youtubeURL;
    private string $videoID;
    private string $botUserEmail;
    private string $botUserName;
    private string $liveChatID;
    private ?string $lastChatMessageID;
    private array $errorList;
    private int $errorCount;

    public function __construct(string $youtubeURL)
    {
        if (! file_exists(OAUTH_TOKEN_JSON)) {
            throw new Exception('Create file with oAuth tokken');
        }

        $this->timeTracker = new TimeTracker();
        $this->errorList = [];
        $this->errorCount = 0;
        $this->youtubeURL = $this->validateYoutubeURL($youtubeURL);
        $this->videoID = $this->getVideoID($this->youtubeURL);
        $this->botUserEmail = APP_EMAIL;
        $this->botUserName = APP_USER_NAME;

        $client = self::createGoogleClient();

        $client->setAccessToken(json_decode(file_get_contents(OAUTH_TOKEN_JSON), true));

        $this->timeTracker->setPoint('login');

        $this->youtubeService = new YouTube($client);

        $this->liveChatID = $this->getLiveChatID($this->videoID);
        $this->lastChatMessageID = null;

        $this->timeTracker->setPoint('getChatID');
    }

    private static function createGoogleClient(bool $setRedirectUrl = false) : Client
    {
        $client = new Client();

        $client->setApplicationName(APP_NAME);
        $client->setAuthConfig(CLIENT_SECRET_JSON);
        $client->setAccessType('offline');

        if ($setRedirectUrl) {
            $client->setRedirectUri(self::getCurrentUrl());
        }

        $client->setScopes([
            YouTube::YOUTUBE_FORCE_SSL,
            YouTube::YOUTUBE_READONLY,
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
            throw new Exception('Error response with live chat ID');
        }

        return $liveChatID;
    }

    private function getChatList() : array
    {
        try {
            $response = $this->youtubeService->liveChatMessages->listLiveChatMessages($this->liveChatID, 'snippet', ['maxResults' => 100]);

            $chatList = $response['items'];
            $actualChat = [];
            $writeMod = ($this->lastChatMessageID === null);

            foreach ($chatList as $mess) {
                if ($writeMod) {
                    $responseChannelID = $this->youtubeService->channels->listChannels('snippet', ['id' => $mess['snippet']['authorChannelId']]);

                    $actualChat[] = [
                        'id' => $mess['id'],
                        'authorId' => $mess['snippet']['authorChannelId'],
                        'authorName' => $responseChannelID['items'][0]['snippet']['title'] ?? '',
                        'message' => $mess['snippet']['displayMessage'],
                        'published' => $mess['snippet']['publishedAt'],
                    ];
                }

                if ($mess['id'] === $this->lastChatMessageID) {
                    $writeMod = true;
                }
            }

            if (empty($actualChat)) {
                return [];
            }

            $this->lastChatMessageID = $actualChat[count($actualChat) - 1]['id'];

            return $actualChat;
        } catch (Exception $error) {
            $this->errorList['getChatList'][] = $error->getMessage();
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
            if (mb_stripos($mess['message'], "@{$this->botUserName}")) {
                match (mb_strtolower(preg_replace("/@{$this->botUserName} */", '', $mess['message']))) {
                    'help', 'помощь' => $sendingList[] = '@' . $mess['authorName'] . ' Приветствую, в настоящий момент функционал дорабатывается, список команд будет доступен позднее',
                    default => true, // TODO =================================================================
                };
            } elseif ($mess['authorName'] === 'no') {

            } else {
                // match (mb_strtolower($mess['message'])) {
                //     'да' => $sendingList[] = "@{$mess['authorName']} пизда",
                //     'нет' => $sendingList[] = "@{$mess['authorName']} пидора ответ",
                // };
            }
        }

        if (! empty($sendingList)) {
            foreach ($sendingList as $sending) {
                $sendCount += $this->sendMessage($sending);
            }
        }

        return $sendCount;
    }

    protected function sendMessage(string $message) : bool
    {
        try {
            $liveChatMessage = new LiveChatMessage();
            $liveChatMessageSnippet = new LiveChatMessageSnippet();
            $liveChatTextMessageDetails = new LiveChatTextMessageDetails();

            $liveChatTextMessageDetails->setMessageText($message);

            $liveChatMessageSnippet->setLiveChatId($this->liveChatID);
            $liveChatMessageSnippet->setType('textMessageEvent');
            $liveChatMessageSnippet->setTextMessageDetails($liveChatTextMessageDetails);

            $liveChatMessage->setSnippet($liveChatMessageSnippet);

            $this->youtubeService->liveChatMessages->insert('snippet', $liveChatMessage);

            return true;
        } catch (Exception $error) {
            $this->errorList['sendMessage'][] = $error->getMessage();
            $this->errorCount++;

            return false;
        }
    }

    private function validateYoutubeURL(string $url) : string
    {
        if (! preg_match('/https:\/\/www\.youtube\.com.*/', $url)) {
            throw new Exception('Incorrect YouTube url');
        }

        return $url;
    }

    private function getVideoID(string $url) : string
    {
        preg_match('/youtube\.com\/watch\?.*v=([^&]+)/',  $url, $matches);

        if (empty($matches[1])) {
            throw new Exception('Incorrect YouTube video ID');
        }

        return $matches[1];
    }

    public function listen(int $interval) : void
    {
        while ($this->errorCount < 5) {
            $this->timeTracker->setPoint('prepare');

            $chatList = $this->getChatList();

            $this->timeTracker->setPoint('getChatList');

            $sendingCount = $this->prepareMessages($chatList); // TODO =================================================================

            $this->timeTracker->setPoint('sendingMessage');

            Timer::setSleep($interval);
        }

        var_dump($this->errorList);
    }
}
