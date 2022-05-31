<?php

namespace App\Anet\Bots;

use Google;
use Google\Service;
use App\Anet\Contents;
use App\Anet\YouTubeHelpers;
use App\Anet\Helpers;
use App\Anet\Games;

/**
 * Realization of YouTube ChatBot.
 * To work you need to setup Google API project https://console.cloud.google.com/apis/api/youtube.googleapis.com
 *
 * And set constant with oAuth tokens for connect to youtube servers
 *
 * Basic usage means create instance of class with youtube video link and call listen() method

 * For more information about working and examples read README.md
 *
 * @author Mironov Alexander https://github.com/ChihiroYamato
 */
final class YouTube extends ChatBotAbstract
{
    use YouTubeHelpers\MessageSpotterTrait;
    /**
     * @var \Google\Service\YouTube $youtubeService `private` instance of Youtube Servise class
     */
    private Service\YouTube $youtubeService;
    /**
     * @var \App\Anet\YouTubeHelpers\VideoProperties $video `private` instance of VideoProperties class
     */
    private YouTubeHelpers\VideoProperties $video;
    /**
     * @var \App\Anet\YouTubeHelpers\UserStorage $users `private` instance of UserStorage class
     */
    private YouTubeHelpers\UserStorage $users;
    /**
     * @var string $botUserEmail `private` email of current bot user
     */
    private string $botUserEmail;
    /**
     * @var string $botUserName `private` name of current bot user
     */
    private string $botUserName;
    /**
     * @var null|string $lastChatMessageID `private` id of last reading message
     */
    private ?string $lastChatMessageID;
    /**
     * @var array $usersListening `private` list of users which are listened
     */
    private array $usersListening;

    /**
     * Initialize YouTube ChatBot
     * @param \App\Anet\YouTubeHelpers\ConnectParams $connect object with connection params
     * @param string $youtubeURL link to active youtube video
     * @return void
     */
    public function __construct(YouTubeHelpers\ConnectParams $connect, string $youtubeURL)
    {
        if (! file_exists($connect->oAuthJSON)) {
            throw new Service\Exception('Create file with oAuth tokken');
        }
        parent::__construct();

        $this->botUserEmail = YOUTUBE_APP_EMAIL;
        $this->botUserName = YOUTUBE_APP_USER_NAME;

        $client = self::createGoogleClient($connect);

        $client->setAccessToken(json_decode(file_get_contents($connect->oAuthJSON), true));

        $this->youtubeService = new Service\YouTube($client);
        $this->video = new YouTubeHelpers\VideoProperties($this->youtubeService, $youtubeURL);
        $this->users = new YouTubeHelpers\UserStorage($this->youtubeService);

        $this->lastChatMessageID = null;
        $this->usersListening = USER_LISTEN_LIST;
    }

    /**
     * Destruct class with all logging poccesses and saving statistics to DB
     */
    public function __destruct()
    {
        Helpers\Logger::loggingProccess($this->className, $this->getStatistics(), $this->buffer->fetch('sendings'));
        Helpers\Logger::logging($this->className, $this->buffer->fetch('messageList'), 'message');

        Helpers\Logger::saveProccessToDB($this->className);
        Helpers\Logger::saveToDB($this->className, 'message', 'youtube_messages');

        Helpers\Logger::archiveLogsByCategory($this->className);

        Helpers\Logger::print($this->className, 'Force termination of a script');
    }

    /**
     * **Method** is main proccess of bot working: execute parsing, analyzing and sending data
     * from and to youtube server
     *
     * During the working - save logs at a certain interval and send system message under certain conditions
     * @param int $interval interval for script sleeping
     * @return void
     */
    public function listen(int $interval) : void
    {
        $sendingCount = 0;
        $sendingCount += $this->sendMessage('Всем привет, хорошего дня/вечера/ночи/утра'); // todo
        Helpers\Logger::print($this->className, 'Starting proccess');

        while ($this->getErrorCount() < 5 && $this->listeningFlag) {
            if ($this->timeTracker->trackerState('loggingProccess')) {
                if ($this->timeTracker->trackerCheck('loggingProccess', 60 * 3)) {
                    $this->timeTracker->trackerStop('loggingProccess');

                    Helpers\Logger::loggingProccess($this->className, $this->getStatistics(), $this->buffer->fetch('sendings'));
                    Helpers\Logger::logging($this->className, $this->buffer->fetch('messageList'), 'message');

                    $this->timeTracker->clearPoints();
                    Helpers\Logger::print($this->className, sprintf('Logs saved. Current iteration is: %d Proccessing duration: %s', $this->totalIterations, $this->timeTracker->getDuration()));
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
            Helpers\Logger::logging($this->className, $this->getErrors(), 'error');
        }
    }

    public function testConnect() : void
    {
        $this->fetchChatList();

        if ($this->lastChatMessageID !== null && empty($this->getErrors())) {
            Helpers\Logger::print($this->className, 'Chat request tested successfully, current last mess ID :' . $this->lastChatMessageID);
        } else {
            Helpers\Logger::print($this->className, "Testing Failed, Current Errors:\n" . print_r($this->getErrors(), true));
        }
    }

    public function testSend() : void
    {
        $testing = $this->sendMessage('Прогрев чата');

        if ($testing) {
            Helpers\Logger::print($this->className, 'Message sending test completed successfully');
        } else {
            Helpers\Logger::print($this->className, "Testing Failed, Current Errors:\n" . print_r($this->getErrors(), true));
        }
    }

    public function getStatistics() : array
    {
        $result = parent::getStatistics();
        $result['youTubeURL'] = $this->video->getYoutubeURL();
        $result['videoID'] = $this->video->getVideoID();
        $result['videoStarting'] = $this->video->getVideoStarting();
        $result['botUserName'] = $this->botUserName;
        $result['botUserEmail'] = $this->botUserEmail;

        return $result;
    }

    /**
     * **Method** create Google auth token for connect to server
     * need to execute in isolated page including to google oAuth list
     * @param \App\Anet\YouTubeHelpers\ConnectParams $connect object with connection params
     * @return bool true if token is creating succussful
     */
    public static function createAuthTokken(YouTubeHelpers\ConnectParams $connect) : bool
    {
        if (file_exists($connect->oAuthJSON)) {
            return false;
        }

        $client = self::createGoogleClient($connect, true);

        if (! isset($_GET['code'])) {
            header('Location: ' . $client->createAuthUrl());

            return false;
        }

        $client->fetchAccessTokenWithAuthCode($_GET['code']);
        file_put_contents($connect->oAuthJSON, json_encode($client->getAccessToken(), JSON_FORCE_OBJECT));

        return true;
    }

    /**
     * **Method** create Google API client connection with specified params
     * @param \App\Anet\YouTubeHelpers\ConnectParams $connect object with connection params
     * @param bool $setRedirectUrl set true if needed to setup reditect url for oAuth token, default false
     * @return \Google\Client instance of client connection
     */
    private static function createGoogleClient(YouTubeHelpers\ConnectParams $connect, bool $setRedirectUrl = false) : Google\Client
    {
        $client = new Google\Client();

        $client->setApplicationName($connect->appName);
        $client->setAuthConfig($connect->secretKeyJSON);
        $client->setAccessType('offline');

        if ($setRedirectUrl) {
            $client->setRedirectUri(self::fetchCurrentUrl());
        }

        $client->setScopes([
            Service\YouTube::YOUTUBE_FORCE_SSL,
            Service\YouTube::YOUTUBE_READONLY,
        ]);
        $client->setLoginHint(YOUTUBE_APP_EMAIL);

        return $client;
    }

    /**
     * **Method** fetch actual message list from video chat with request to youtube server.
     * for the first fetching method setup last message and controls the relevance of the chat with that param
     * @return array actual message list
     */
    private function fetchChatList() : array
    {
        try {
            $response = $this->youtubeService->liveChatMessages->listLiveChatMessages($this->video->getLiveChatID(), 'snippet', ['maxResults' => 100]);

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

                    $this->users->handler($currentUser->getId(), 'incrementMessage');

                    $actualChat[] = [
                        'id' => $chatItem['id'],
                        'authorId' => $currentUser->getId(),
                        'authorName' => $currentUser->getName(),
                        'message' => $chatItem['snippet']['displayMessage'],
                        'published' => $chatItem['snippet']['publishedAt'],
                    ];
                }
            }

            $this->lastChatMessageID = array_pop($chatList)['id'] ?? $this->lastChatMessageID;
            $this->totalMessageReading += count($actualChat);

            return $actualChat;
        } catch (Service\Exception $error) {
            $this->addError(__FUNCTION__, $error->getMessage());

            return [];
        }
    }

    protected function prepareSendings(array $chatlist) : array
    {
        // TODO ============ restruct by modules
        $usersList = [];
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

            $this->buffer->add('messageList', ['content' => $chatItem['message'], 'user_key' => $chatItem['authorId']]);

            $currentUser = $this->users->get($chatItem['authorId']);
            $sendingDetail = [
                'author' => $chatItem['authorName'],
                'message' => $chatItem['message'],
                'published' => $chatItem['published'],
            ];
            $sending = "@{$chatItem['authorName']} ";
            $largeSending = [];

            $matches = explode(' ', trim(str_replace(['!', ',', '.', '?'], '', $chatItem['message'])));
            $lastWord = mb_strtolower(array_pop($matches));

            $this->users->handler($currentUser->getId(), 'incrementRaitingRandom', 2, random_int(1, 2));

            if ($currentUser->checkAdmin()) {
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
                        $this->usersListening[$listernUser] = $listernUser;

                        $sendingDetail['sending'] = $sending . "Начинаю слушать пользователя <$listernUser>";
                        break;
                    case mb_stripos($chatItem['message'], '/drop') !== false:
                        $listernUser = preg_replace('/\/drop /', '', $chatItem['message']);
                        unset($this->usersListening[$listernUser]);

                        $sendingDetail['sending'] = $sending . "Прекращаю слушать пользователя <$listernUser>";
                        break;
                    case mb_stripos($chatItem['message'], '/show-listern') !== false:
                        $sendingDetail['sending'] = $sending . 'Список прослушиваемых: ' . implode(',', $this->usersListening);
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
                    $largeSending = Contents\Facts::fetchRand();
                    break;
                case in_array($chatItem['message'], ['/шутка', '/joke']):
                    $largeSending = Contents\Jokes::fetchRand();
                    break;
                case mb_strpos($chatItem['message'], '/play') !== false:
                    switch (true) {
                        case $chatItem['message'] === Games\Roulette::COMMAND_HELP:
                            $largeSending = Games\Roulette::getHelpMessage();
                            break;
                        case $chatItem['message'] === Games\Сasino::COMMAND_HELP:
                            $largeSending = Games\Сasino::getHelpMessage();
                            break;
                        case $chatItem['message'] === Games\Towns::COMMAND_HELP:
                            $largeSending = Games\Towns::getHelpMessage();
                            break;
                        case $chatItem['message'] === Games\Cows::COMMAND_HELP:
                            $largeSending = Games\Cows::getHelpMessage();
                            break;
                        case $chatItem['message'] === Games\Roulette::COMMAND_START:
                            $largeSending = $this->games->validateAndStarting(new Games\Roulette($currentUser), $currentUser, 180);
                            break;
                        case $chatItem['message'] === Games\Сasino::COMMAND_START:
                            $largeSending = $this->games->validateAndStarting(new Games\Сasino($currentUser), $currentUser, 300, 300);
                            break;
                        case $chatItem['message'] === Games\Towns::COMMAND_START:
                            $largeSending = $this->games->validateAndStarting(new Games\Towns($currentUser), $currentUser, 120, 55);
                            break;
                        case $chatItem['message'] === Games\Cows::COMMAND_START:
                            $largeSending = $this->games->validateAndStarting(new Games\Cows($currentUser), $currentUser, 120, 55);
                            break;
                        default:
                            $largeSending[] = 'В настоящее время доступны следующие игры: —— русская рулетка <' . Games\Roulette::COMMAND_HELP . '> —— казино <' . Games\Сasino::COMMAND_HELP . '> —— города <' . Games\Towns::COMMAND_HELP . '> —— быки и коровы <' . Games\Cows::COMMAND_HELP .  '>';
                            $largeSending[] = 'Внимание! - каждое следующее сообщение игрока после старта игры засчитывается как ход, на игру отводится определенное время, по истечению засчитывается проигрыш с максимумом очков';
                            break;
                    }
                    break;
            }

            if (! empty($largeSending)) {
                $this->users->handler($currentUser->getId(), 'incrementRaiting', rand(0, 4) * 5);

                foreach ($largeSending as $item) {
                    $sendingDetail['sending'] = $item;
                    $sendingList[] = $sendingDetail;
                }

                continue;
            }

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
                $this->users->handler($currentUser->getId(), 'incrementRaiting', rand(0, 4) * 5);

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
                                $this->users->handler($currentUser->getId(), 'incrementRaiting', rand(0, 2) * (-5));
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

            if (in_array($chatItem['authorName'], $this->usersListening)) {
                $answer = $this->prepareSmartAnswer($chatItem['message'], true);

                if (! empty($answer)) {
                    $sendingDetail['sending'] = $sending . $answer;
                    $sendingList[] = $sendingDetail;
                    continue;
                }
            }

            $usersList[] = $currentUser;
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

        if (! empty($usersList)) {
            foreach ($usersList as $user) {
                $lotery = $this->users->randomLottery($user, 5000);

                if (!empty($lotery)) {
                    $sendingDetail['sending'] = $lotery;
                    $sendingList[] = $sendingDetail;
                    break;
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
            $liveChatMessage = new Service\YouTube\LiveChatMessage();
            $liveChatMessageSnippet = new Service\YouTube\LiveChatMessageSnippet();
            $liveChatTextMessageDetails = new Service\YouTube\LiveChatTextMessageDetails();

            $liveChatTextMessageDetails->setMessageText($this->changeChars($message));

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

    /**
     * **Method** setup timer for any event and send message if timer is expired
     * @param bool $event any bool event
     * @param int $sec event expired in seconds
     * @param string $vocabularyKey key in vocabulary for set answer
     * @return bool
     */
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
}
