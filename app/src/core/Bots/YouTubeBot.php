<?php

namespace App\Anet\Bots;

use Google;
use Google\Service;
use Google\Service\YouTube;
use App\Anet\Services;
use App\Anet\YouTubeHelpers;
use App\Anet\Helpers;
use App\Anet\Games;

final class YouTubeBot extends ChatBotAbstract
{
    private Service\YouTube $youtubeService;
    private Helpers\TimeTracker $timeTracker;
    private YouTubeHelpers\VideoProperties $video;
    private YouTubeHelpers\UserStorage $users;
    private string $botUserEmail;
    private string $botUserName;
    private ?string $lastChatMessageID;
    private array $usersListerning;

    public function __construct(string $youtubeURL)
    {
        if (! file_exists(OAUTH_TOKEN_JSON)) {
            throw new Service\Exception('Create file with oAuth tokken');
        }
        parent::__construct();

        $this->timeTracker = new Helpers\TimeTracker();
        $this->botUserEmail = APP_EMAIL;
        $this->botUserName = APP_USER_NAME;

        $client = self::createGoogleClient();

        $client->setAccessToken(json_decode(file_get_contents(OAUTH_TOKEN_JSON), true));

        $this->youtubeService = new Service\YouTube($client);
        $this->video = new YouTubeHelpers\VideoProperties($this->youtubeService, $youtubeURL);
        $this->users = new YouTubeHelpers\UserStorage($this->youtubeService);

        $this->lastChatMessageID = null;
        $this->usersListerning = USER_LISTEN_LIST;

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

    private function fetchChatList() : array
    {
        try {
            $response = $this->youtubeService->liveChatMessages->listLiveChatMessages($this->video->getLiveChatID(), 'snippet', ['maxResults' => 100]); // todo

            $chatList = $response['items'];
            $actualChat = [];
            $writeMod = false;

            foreach ($chatList as $chatItem) {
                if ($chatItem['id'] === $this->lastChatMessageID) {
                    $writeMod = true;
                } elseif ($writeMod) {
                    $currentUser = $this->users->fetch($chatItem['snippet']['authorChannelId']);

                    if ($currentUser->getName() === $this->botUserName) {
                        continue;
                    }

                    $currentUser->incrementMessage();

                    $actualChat[] = [
                        'id' => $chatItem['id'],
                        'authorId' => $chatItem['snippet']['authorChannelId'],
                        'authorName' => $currentUser->getName(),
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
            if ($chatItem['authorName'] === $this->botUserName) {
                continue;
            }

            $this->buffer->add('messageList', $chatItem); // todo

            $sendingDetail = [
                'author' => $chatItem['authorName'],
                'message' => $chatItem['message'],
                'published' => $chatItem['published'],
            ];
            $sending = "@{$chatItem['authorName']} ";
            $largeSending = [];

            $matches = explode(' ', trim(str_replace(['!', ',', '.', '?'], '', $chatItem['message'])));
            $lastWord = mb_strtolower(array_pop($matches));

            if ($this->users->get($chatItem['authorId'])->checkAdmin()) {
                switch (true) {
                    case $lastWord === '/help-admin':
                        $sendingDetail['sending'] = $sending . '</stop> —— завершить скрипт; </insert user> —— добавить юзера в список слежения ; </drop user> —— удалить юзера из списка слежения; </show-listern> —— список слежения;';
                        break;
                    case $lastWord === '/stop':
                        $sendingDetail['sending'] = $sending . 'Завершаю свою работу.';
                        $sendingList[] = $sendingDetail;
                        $this->listeningFlag = false;
                        break 2;
                    case mb_stripos($chatItem['message'], '/insert') !== false:
                        $listernUser = preg_replace('/\/insert /', '', $chatItem['message']);
                        $this->usersListerning[$listernUser] = $listernUser;

                        $sendingDetail['sending'] = $sending . "Начинаю слушать пользователя <$listernUser>";
                        break;
                    case mb_stripos($chatItem['message'], '/drop') !== false:
                        $listernUser = preg_replace('/\/drop /', '', $chatItem['message']);
                        unset($this->usersListerning[$listernUser]);

                        $sendingDetail['sending'] = $sending . "Прекращаю слушать пользователя <$listernUser>";
                        break;
                    case mb_stripos($chatItem['message'], '/show-listern') !== false:
                        $sendingDetail['sending'] = $sending . 'Список прослушиваемых: ' . implode(',', $this->usersListerning);
                        break;
                }

                if (array_key_exists('sending', $sendingDetail)) {
                    $sendingList[] = $sendingDetail;
                    continue;
                }
            }

            if ($this->games->checkUserActiveSession($chatItem['authorId'])) {
                $sendingDetail['sending'] = $this->games->checkGame($chatItem['authorId'], $lastWord);
                $sendingList[] = $sendingDetail;
                continue;
            }

            switch (true) {
                case in_array($chatItem['message'], ['/help', '/справка']):
                    $largeSending[] = $sending . 'приветствую, в данном чате доступны следующие команды: </stat (/стата) "@user"> получить статистику по себе (или по указанному юзеру); —— </joke (/шутка)> получить баянистый анекдот;';
                    $largeSending[] = '—— </fact (/факт)> получить забавный (или не очень) факт; —— </stream (/стрим)> получить информацию о стриме; —— </play> раздел игр';
                    break;
                case mb_ereg_match('.*(\/stat|\/стата) @', $chatItem['message']):
                    $largeSending = $this->users->showUserStatistic(mb_ereg_replace('.*(\/stat|\/стата) @', '', $chatItem['message']));
                    break;
                case in_array($chatItem['message'], ['/stat', '/стата']):
                    $largeSending = $this->users->showUserStatistic($chatItem['authorName']);
                    break;
                case in_array($chatItem['message'], ['/stream', '/стрим']):
                    $largeSending = $this->video->showStatistic();
                    break;
                case in_array($chatItem['message'], ['/факт', '/fact']):
                    $largeSending = Services\Facts::fetchRand();
                    break;
                case in_array($chatItem['message'], ['/шутка', '/joke']):
                    $largeSending = Services\Jokes::fetchRand();
                    break;
                case mb_strpos($chatItem['message'], '/play') !== false:
                    // TODO =========== игры
                    $currentUser = $this->users->get($chatItem['authorId']);

                    switch (true) {
                        case $chatItem['message'] === '/play roul':
                            $largeSending[] = '—— GAME ' . Games\Roulette::GAME_NAME . ' —— правила: игроку предлагается угадать число от 1 до 6, в случае выигрыша - соц рейтинг удваивается, в случае проигрыша - уполовинивается';
                            $largeSending[] = '—— старт: введите </play roul s>. Внимание! - каждое следующее сообщение игрока засчитывается как число, на игру отведено 2 минуты, по истечению засчитывается проигрыш';
                            break;
                        case $chatItem['message'] === '/play casino':
                            $largeSending[] = '—— GAME ' . Games\Сasino::GAME_NAME . ' —— правила: игроку предлагается поставить на номер фишки от 1 до '. Games\Сasino::BOARD_SIZE . ', у каждой фишки есть сумма очков от ' . Games\Сasino::MIN_POINT . ' до ' . Games\Сasino::MAX_POINT . ', но половина фишек - выйгрышные, половина - проигрышные';
                            $largeSending[] = '—— старт: введите </play casino s>. Внимание! - каждое следующее сообщение игрока засчитывается как число, на игру отведено 2 минуты, по истечению засчитывается проигрыш с максимумом очков';
                            break;
                        case $chatItem['message'] === '/play roul s':
                            $largeSending = $this->games->validateAndStarting(new Games\Roulette($currentUser), $currentUser, 180);
                            break;
                        case $chatItem['message'] === '/play casino s':
                            $largeSending = $this->games->validateAndStarting(new Games\Сasino($currentUser), $currentUser, 300, 400);
                            break;
                        default:
                            $largeSending[] = 'В настоящее время доступны следующие игры: —— русская рулетка </play roul> —— казино </play casino>';
                            break;
                    }
                    break;
            }

            if (! empty($largeSending)) {
                $this->users->get($chatItem['authorId'])->incrementRaiting(rand(0, 4) * 5);

                foreach ($largeSending as $item) {
                    $sendingDetail['sending'] = $item;
                    $sendingList[] = $sendingDetail;
                }

                continue;
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

            if (in_array($lastWord, $this->vocabulary->getCategoryType('dead_inside', 'request'))) {
                $sendingDetail['sending'] = $sending . "сколько будет {$lastWord}-7?";
                $sendingList[] = $sendingDetail;
                continue;
            }

            if (mb_stripos(mb_strtolower($chatItem['message']), $this->botUserName) !== false) {
                $this->users->get($chatItem['authorId'])->incrementRaiting(rand(0, 4) * 5);

                $currentMessage = trim(mb_strtolower(preg_replace("/@?{$this->botUserName}/", '', $chatItem['message'])));
                $sending = $sending . $this->prepareSmartAnswer($currentMessage);

                $sendingDetail['sending'] = $sending;
                $sendingList[] = $sendingDetail;
                continue;
            }

            foreach ($this->vocabulary->getCategoriesGroup('another', ['say_yes', 'say_no', 'say_haha', 'say_foul', 'say_three']) as $key => $item) {
                if (in_array($key, ['say_haha', 'say_foul', 'say_three'])) {
                    foreach ($item['request'] as $option) {
                        if (mb_stripos(mb_strtolower($chatItem['message']), $option) !== false) {
                            if ($key === 'say_foul') {
                                $this->users->get($chatItem['authorId'])->incrementRaiting(rand(0, 2) * (-5));
                            }
                            $sendingDetail['sending'] = $sending . $this->vocabulary->getRandItem($key);
                            $sendingList[] = $sendingDetail;
                            continue 3;
                        }
                    }
                } elseif (in_array($lastWord, $item['request'])) {
                    $sendingDetail['sending'] = $sending . $this->vocabulary->getRandItem($key);
                    $sendingList[] = $sendingDetail;
                    continue 2;
                }
            }



            if (in_array($chatItem['authorName'], $this->usersListerning)) {
                $answer = $this->prepareSmartAnswer($chatItem['message'], true);

                if (! empty($answer)) {
                    $sendingDetail['sending'] = $sending . $answer;
                    $sendingList[] = $sendingDetail;
                    continue;
                }
            }
        }

        $sendingDetail = [
            'author' => 'System',
            'message' => 'none',
            'published' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        $gamesReport = $this->games->checkSessionsTimeOut();

        if (! empty($gamesReport)) {
            foreach ($gamesReport as $systemMess) {
                $sendingDetail['sending'] = $systemMess;
                $sendingList[] = $sendingDetail;
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

            $liveChatMessageSnippet->setLiveChatId($this->video->getLiveChatID());
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

            $sendingCount += $this->sendingMessages($this->prepareSendings($chatList));
            $sendingCount += $this->checkingMessageSendEvent($sendingCount < 1 && $this->totalIterations > 1, 10 * 60, 'no_care');
            $sendingCount += $this->checkingMessageSendEvent(empty($chatList), 15 * 60, 'dead_chat');

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
            'YouTubeURL' => $this->video->getYoutubeURL(),
            'VideoID' => $this->video->getVideoID(),
            'VideoStarting' => $this->video->getVideoStarting(),
            'BotUserName' => $this->botUserName,
            'BotUserEmail' => $this->botUserEmail,
        ];
    }
}
