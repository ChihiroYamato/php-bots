<?php

namespace Anet\App\Bots;

use Anet\App\Helpers;
use Anet\App\Helpers\Traits;
use Google;
use Google\Service;
use Google\Service\YouTube;

final class YouTubeBot extends ChatBotAbstract
{
    use Traits\UrlHelperTrait, Traits\ErrorHelperTrait; // TODO ========== LogHelperTrait

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
            $response = $this->youtubeService->liveChatMessages->listLiveChatMessages($this->liveChatID, 'snippet', ['maxResults' => 20]);

            $chatList = $response['items'];
            $actualChat = [];
            $writeMod = false;

            foreach ($chatList as $chatItem) {
                if ($chatItem['id'] === $this->lastChatMessageID) {
                    $writeMod = true;
                } elseif ($writeMod) {
                    $responseChannelID = $this->youtubeService->channels->listChannels('snippet', ['id' => $chatItem['snippet']['authorChannelId']]);

                    $actualChat[] = [
                        'id' => $chatItem['id'],
                        'authorId' => $chatItem['snippet']['authorChannelId'],
                        'authorName' => $responseChannelID['items'][0]['snippet']['title'] ?? '',
                        'message' => $chatItem['snippet']['displayMessage'],
                        'published' => $chatItem['snippet']['publishedAt'],
                    ];
                }
            }

            $this->lastChatMessageID = array_pop($chatList)['id'];

            return $actualChat;
        } catch (Service\Exception $error) {
            $this->addError('fetchChatList', $error->getMessage());

            return [];
        }
    }

    protected function prepareMessages(array $chatlist) : int // TODO ===========================================================
    {
        $sendingList = [];
        $sendingDetail = [];
        $sendCount = 0;
        $sending = '';

        if (empty($chatlist)) {
            return $sendCount;
        }

        foreach ($chatlist as $chatItem) {
            if ($chatItem['authorName'] !== $this->botUserName) {
                $sendingDetail = [
                    'author' => $chatItem['authorName'],
                    'message' => $chatItem['message'],
                    'published' => $chatItem['published'],
                ];
                $sending = "@{$chatItem['authorName']} ";

                $matches = explode(' ', trim(str_replace(['!', ',', '.', '?'], '', $chatItem['message'])));
                $lastWord = mb_strtolower(array_pop($matches));

                if (in_array($lastWord, $this->getVocabulary()['dead_inside']['response'])) {
                    $sendingDetail['sending'] = $sending . "сколько будет {$lastWord}-7?";
                    $sendingList[] = $sendingDetail;
                } elseif (mb_stripos(mb_strtolower($chatItem['message']), $this->botUserName) !== false) {
                    $currentMessage = trim(mb_strtolower(preg_replace("/@?{$this->botUserName}/", '', $chatItem['message'])));

                    switch (true) {
                        case in_array($currentMessage, ['help', 'помощь']):
                            $sending .= 'приветствую, в настоящий момент функционал дорабатывается, список команд будет доступен позднее';
                            break;
                        default:
                            $sending .= $this->prepareSmartAnswer($currentMessage);
                            break;
                    }

                    $sendingDetail['sending'] = $sending;
                    $sendingList[] = $sendingDetail;
                } else {
                    foreach ($this->getVocabulary()['standart']['request'] as $category) {
                        foreach ($category as $option) {
                            if (mb_stripos(mb_strtolower($chatItem['message']), $option) !== false) {
                                $answer = $this->prepareSmartAnswer($option, false);

                                if (! empty($answer)) {
                                    $sendingDetail['sending'] = $sending . $answer;
                                    $sendingList[] = $sendingDetail;
                                }
                                break;
                            }
                        }
                    }

                    if (! array_key_exists('sending', $sendingDetail)) {
                        foreach ($this->getVocabulary()['another'] as $key => $item) {
                            if (in_array($key, ['hah', 'mmm', 'three'])) {
                                foreach ($item['request'] as $option) {
                                    if (mb_stripos(mb_strtolower($chatItem['message']), $option) !== false) {
                                        $sendingDetail['sending'] = $sending . $item['response'][rand(0, count($item['response']) - 1)];
                                        $sendingList[] = $sendingDetail;
                                        break;
                                    }
                                }
                            } elseif (in_array($chatItem['authorName'], USER_LISTEN_LIST) && in_array($lastWord, $item['request'])) {
                                $sendingDetail['sending'] = $sending . $item['response'][rand(0, count($item['response']) - 1)];
                                $sendingList[] = $sendingDetail;
                                break;
                            }
                        }
                    }

                    if (! array_key_exists('sending', $sendingDetail) && in_array($chatItem['authorName'], USER_LISTEN_LIST)) {
                        $answer = $this->prepareSmartAnswer($chatItem['message'], true);

                        if (! empty($answer)) {
                            $sendingDetail['sending'] = $sending . $answer;
                            $sendingList[] = $sendingDetail;
                        }
                    }
                }
            }
        }

        if (! empty($sendingList)) {
            var_dump($sendingList); // Todo =============================================
            foreach ($sendingList as $sendItem) {
                $sendCount += $this->sendMessage($sendItem['sending']);
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
            $this->addError('sendMessage', $error->getMessage());

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
        // $sendingCount += $this->sendMessage('Всем привет, хорошего дня/вечера/ночи/утра');

        while ($this->getErrorCount() < 5) {
            $this->timeTracker->setPoint('prepare');

            $chatList = $this->fetchChatList();
            $this->timeTracker->setPoint('fetchChatList');

            if (empty($chatList)) {
                if ($this->timeTracker->trackerState()) {
                    if ($this->timeTracker->trackerCheck(5 * 60)) { // todo Баг с постоянной отправкой по таймеру
                        $sendingCount += $this->sendMessage($this->getVocabulary()['dead_chat']['response'][rand(0, count($this->getVocabulary()['dead_chat']['response']) - 1)]);
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

        print_r($this->getErrors());
    }

    public function testConnect() : void
    {
        $this->fetchChatList();

        if ($this->lastChatMessageID !== null && empty($this->getErrors())) {
            echo 'Chat request tested successfully, current last mess ID:' . PHP_EOL;
            print_r($this->lastChatMessageID);
        } else {
            echo 'Testing Failed, Current Errors:' . PHP_EOL;
            print_r($this->getErrors());
        }
    }

    public function testSend() : void
    {
        $testing = $this->sendMessage('Прогрев чата');

        if ($testing) {
            echo 'Message sending test completed successfully' . PHP_EOL;
        } else {
            echo 'Testing Failed, Current Errors:' . PHP_EOL;
            print_r($this->getErrors());
        }
    }
}
