<?php

namespace App\Anet\Bots;

use Google;
use Google\Service;
use Google\Service\YouTube;
use App\Anet\YouTubeHelpers;
use App\Anet\Helpers;

final class YouTubeBot extends ChatBotAbstract
{
    private Service\YouTube $youtubeService;
    private Helpers\TimeTracker $timeTracker;
    private YouTubeHelpers\YoutubeProps $youtubeProps;
    private string $botUserEmail;
    private string $botUserName;
    private ?string $lastChatMessageID;

    public function __construct(string $youtubeURL)
    {
        if (! file_exists(OAUTH_TOKEN_JSON)) {
            throw new Service\Exception('Create file with oAuth tokken');
        }
        parent::__construct();

        $this->timeTracker = new Helpers\TimeTracker();
        $this->youtubeProps = new YouTubeHelpers\YoutubeProps($youtubeURL);
        $this->botUserEmail = APP_EMAIL;
        $this->botUserName = APP_USER_NAME;

        $client = self::createGoogleClient();

        $client->setAccessToken(json_decode(file_get_contents(OAUTH_TOKEN_JSON), true));

        $this->youtubeService = new Service\YouTube($client);

        $this->youtubeProps->setLiveChatID($this->getLiveChatID($this->youtubeProps->getVideoID()));
        $this->lastChatMessageID = null;

        Helpers\LogerHelper::archiveLogs();
    }

    public function __destruct()
    {
        Helpers\LogerHelper::loggingProccess($this->getStatistics(), $this->buffer->fetch('sendings'));
        Helpers\LogerHelper::logging($this->buffer->fetch('messageList'), 'message'); // todo

        print_r("Force termination of a script\n");
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
            $response = $this->youtubeService->liveChatMessages->listLiveChatMessages($this->youtubeProps->getLiveChatID(), 'snippet', ['maxResults' => 100]); // todo

            $chatList = $response['items'];
            $actualChat = [];
            $writeMod = false;

            foreach ($chatList as $chatItem) {
                if ($chatItem['id'] === $this->lastChatMessageID) {
                    $writeMod = true;
                } elseif ($writeMod) {
                    // todo ===================== Реализовать запрос и хранение юзеров в БД
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
            $this->totalMessageReading += count($actualChat);

            return $actualChat;
        } catch (Service\Exception $error) {
            $this->addError(__FUNCTION__, $error->getMessage());

            return [];
        }
    }

    protected function prepareSendings(array $chatlist) : array
    {
        $sendingList = [];
        $sendingDetail = [];
        $sending = '';

        if (empty($chatlist)) {
            return [];
        }

        foreach ($chatlist as $chatItem) {
            $this->buffer->add('messageList', $chatItem); // todo

            if ($chatItem['authorName'] === $this->botUserName) {
                continue;
            }

            $sendingDetail = [
                'author' => $chatItem['authorName'],
                'message' => $chatItem['message'],
                'published' => $chatItem['published'],
            ];
            $sending = "@{$chatItem['authorName']} ";

            $matches = explode(' ', trim(str_replace(['!', ',', '.', '?'], '', $chatItem['message'])));
            $lastWord = mb_strtolower(array_pop($matches));

            if ($chatItem['authorName'] === '大和千ひろ') { // todo == chech with DB
                switch ($lastWord) {
                    case '/stop':
                        $sendingDetail['sending'] = $sending . 'Завершаю свою работу.';
                        $sendingList[] = $sendingDetail;
                        $this->listeningFlag = false;
                        break 2;
                }
            }

            // todo ================= NEED TESTING
            if (! $this->timeTracker->trackerState('standart_responce') || $this->timeTracker->trackerCheck('standart_responce', 30)) {
                $this->timeTracker->trackerStop('standart_responce');

                foreach ($this->vocabulary->getCategoriesGroup('standart', ['greetings', 'parting']) as $category) {
                    foreach ($category['request'] as $option) {
                        if (mb_stripos(mb_strtolower($chatItem['message']), $option) !== false) {
                            $answer = $this->prepareSmartAnswer($option, false);

                            if (! empty($answer)) {
                                $sendingDetail['sending'] = $sending . $answer;
                                $sendingList[] = $sendingDetail;
                                $this->timeTracker->trackerStart('standart_responce');
                                continue 3;
                            }
                        }
                    }
                }
            }

            foreach ($this->vocabulary->getCategoriesGroup('another', ['say_yes', 'say_no', 'say_haha', 'say_foul', 'say_three']) as $key => $item) {
                if (in_array($key, ['say_haha', 'say_foul', 'say_three'])) {
                    foreach ($item['request'] as $option) {
                        if (mb_stripos(mb_strtolower($chatItem['message']), $option) !== false) {
                            $sendingDetail['sending'] = $sending . $this->vocabulary->getRandItem($key);
                            $sendingList[] = $sendingDetail;
                            continue 3;
                        }
                    }
                } elseif (in_array($chatItem['authorName'], USER_LISTEN_LIST) && in_array($lastWord, $item['request'])) {
                    $sendingDetail['sending'] = $sending . $this->vocabulary->getRandItem($key);
                    $sendingList[] = $sendingDetail;
                    continue 2;
                }
            }


            if (mb_stripos(mb_strtolower($chatItem['message']), $this->botUserName) !== false) {
                $currentMessage = trim(mb_strtolower(preg_replace("/@?{$this->botUserName}/", '', $chatItem['message'])));

                switch (true) {
                    case in_array($currentMessage, ['help', 'справка']):
                        $sending .= 'приветствую, в настоящий момент функционал дорабатывается, список команд будет доступен позднее';
                        break;
                    // TODO =========== анекдоты
                    // TODO =========== проверить корректность переноса блока с анализом адрессованных сообщений
                    default:
                        $sending .= $this->prepareSmartAnswer($currentMessage);
                        break;
                }

                $sendingDetail['sending'] = $sending;
                $sendingList[] = $sendingDetail;
                continue;
            }

            if (in_array($lastWord, $this->vocabulary->getCategoryType('dead_inside', 'request'))) {
                $sendingDetail['sending'] = $sending . "сколько будет {$lastWord}-7?";
                $sendingList[] = $sendingDetail;
                continue;
            }

            if (in_array($chatItem['authorName'], USER_LISTEN_LIST)) {
                $answer = $this->prepareSmartAnswer($chatItem['message'], true);

                if (! empty($answer)) {
                    $sendingDetail['sending'] = $sending . $answer;
                    $sendingList[] = $sendingDetail;
                    continue;
                }
            }
        }

        return $sendingList;
    }

    protected function sendingMessages(array $sending) : int
    {
        if (empty($sending)) {
            return 0;
        }

        $sendCount = 0;

        foreach ($sending as $sendItem) {
            $this->buffer->add('sendings', $sendItem);
            $sendCount += $this->sendMessage($sendItem['sending']);
            sleep(1);
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

            $liveChatMessageSnippet->setLiveChatId($this->youtubeProps->getLiveChatID());
            $liveChatMessageSnippet->setType('textMessageEvent');
            $liveChatMessageSnippet->setTextMessageDetails($liveChatTextMessageDetails);

            $liveChatMessage->setSnippet($liveChatMessageSnippet);

            $this->youtubeService->liveChatMessages->insert('snippet', $liveChatMessage);

            return true;
        } catch (Service\Exception $error) {
            $this->addError(__FUNCTION__, $error->getMessage());

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

    private function checkingMessageSendEvent(bool $event, int $sec, string $vocabularyKey) : bool
    {
        $sendStatus = false;

        if ($event) {
            if ($this->timeTracker->trackerState($vocabularyKey)) {
                if ($this->timeTracker->trackerCheck($vocabularyKey, $sec)) {
                    $this->timeTracker->trackerStop($vocabularyKey);

                    $sendStatus = $this->sendMessage($this->vocabulary->getRandItem($vocabularyKey));
                }
            } else {
                $this->timeTracker->trackerStart($vocabularyKey);
            }
        } else {
            $this->timeTracker->trackerStop($vocabularyKey);
        }

        return $sendStatus;
    }

    public function listen(int $interval) : void
    {
        $sendingCount = 0;
        $sendingCount += $this->sendMessage('Всем привет, хорошего дня/вечера/ночи/утра'); // todo

        while ($this->getErrorCount() < 5 && $this->listeningFlag) {
            if ($this->timeTracker->trackerState('loggingProccess')) {
                if ($this->timeTracker->trackerCheck('loggingProccess', 60 * 3)) {
                    $this->timeTracker->trackerStop('loggingProccess');

                    Helpers\LogerHelper::loggingProccess($this->getStatistics(), $this->buffer->fetch('sendings'));
                    Helpers\LogerHelper::logging($this->buffer->fetch('messageList'), 'message'); // todo

                    $this->timeTracker->clearPoints();

                    printf('Logs saved. Current iteration is: %d Proccessing duration: %s' . PHP_EOL, $this->totalIterations, $this->timeTracker->getDuration());
                }
            } else {
                $this->timeTracker->trackerStart('loggingProccess');
            }

            $this->timeTracker->startPointTracking();

            $chatList = $this->fetchChatList();
            $this->timeTracker->setPoint('fetchChatList');

            // if (empty($chatList)) {
            //     if ($this->timeTracker->trackerState('dead_chat')) {
            //         if ($this->timeTracker->trackerCheck('dead_chat', 5 * 60)) {
            //             $this->timeTracker->trackerStop('dead_chat');

            //             $sendingCount += $this->sendMessage($this->getVocabulary()['dead_chat']['response'][random_int(0, count($this->getVocabulary()['dead_chat']['response']) - 1)]);
            //         }
            //     } else {
            //         $this->timeTracker->trackerStart('dead_chat');
            //     }
            // } else {
            //     $this->timeTracker->trackerStop('dead_chat');
            // }
            $sendingCount += $this->checkingMessageSendEvent(empty($chatList), 5 * 60, 'dead_chat');
            $sendingCount += $this->sendingMessages($this->prepareSendings($chatList));
            $sendingCount += $this->checkingMessageSendEvent($sendingCount < 1 && $this->totalIterations > 1, 2 * 60, 'no_care');

            // if ($sendingCount < 1 && $this->totalIterations > 1) {
            //     if ($this->timeTracker->trackerState('no_care')) {
            //         if ($this->timeTracker->trackerCheck('no_care', 2* 60)) {
            //             $this->timeTracker->trackerStop('no_care');

            //             $sendingCount += $this->sendMessage($this->getVocabulary()['no_care']['response'][random_int(0, count($this->getVocabulary()['no_care']['response']) - 1)]);
            //         }
            //     } else {
            //         $this->timeTracker->trackerStart('no_care');
            //     }
            // } else {
            //     $this->timeTracker->trackerStop('no_care');
            // }

            $this->timeTracker->setPoint('sendingMessage');
            $this->timeTracker->finishPointTracking();

            $this->totalMessageSending += $sendingCount;
            $sendingCount = 0;
            $this->totalIterations++;
            Helpers\Timer::setSleep($interval);
        }

        if (! empty($this->getErrors())) {
            Helpers\LogerHelper::logging($this->getErrors());
        }
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

    public function getStatistics() : array
    {
        return [
            'TimeStarting' => $this->timeTracker->getTimeInit(),
            'TimeProccessing' => $this->timeTracker->getDuration(),
            'MessageReading' => $this->totalMessageReading,
            'MessageSending' => $this->totalMessageSending,
            'Iterations' => $this->totalIterations,
            'IterationAverageTime' => $this->timeTracker->sumPointsAverage(),
            'YouTubeURL' => $this->youtubeProps->getYoutubeURL(),
            'VideoID' => $this->youtubeProps->getVideoID(),
            'BotUserName' => $this->botUserName,
            'BotUserEmail' => $this->botUserEmail,
        ];
    }
}
