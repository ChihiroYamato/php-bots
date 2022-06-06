<?php

namespace Anet\App\Bots;

use Google;
use Google\Service;
use Anet\App\Contents;
use Anet\App\YouTubeHelpers;
use Anet\App\Helpers;
use Anet\App\Games;

/**
 * Realization of YouTube ChatBot.
 * To work you need to setup Google API project https://console.cloud.google.com/apis/api/youtube.googleapis.com
 *
 * And set constant with oAuth tokens for connect to youtube servers
 *
 * Basic usage means create instance of class with youtube video link and call listen() method

 * For more information about working and examples read README.md
 *
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 0.7
 */
final class YouTube extends ChatBotAbstract
{
    use YouTubeHelpers\MessageSpotterTrait;
    /**
     * @var \Google\Service\YouTube $youtubeService instance of Youtube Servise class
     */
    private Service\YouTube $youtubeService;
    /**
     * @var \Anet\App\YouTubeHelpers\VideoProperties $video instance of VideoProperties class
     */
    private YouTubeHelpers\VideoProperties $video;
    /**
     * @var \Anet\App\YouTubeHelpers\UserStorage $users instance of UserStorage class
     */
    private YouTubeHelpers\UserStorage $users;
    /**
     * @var string $botUserEmail email of current bot user
     */
    private string $botUserEmail;
    /**
     * @var string $botUserName name of current bot user
     */
    private string $botUserName;
    /**
     * @var null|string $lastChatMessageID id of last reading message
     */
    private ?string $lastChatMessageID;
    /**
     * @var string[] $usersListening list of users which are listened
     */
    private array $usersListening;

    /**
     * @var \Anet\App\YouTubeHelpers\User[] $currentUsersList list of users on current iteration
     */
    private array $currentUsersList;

    /**
     * @var string[] $actionPrepareList list of action methods for prepareSendings()
     * for set method to action is needed: named like `actionPrepare...Sendings`,
     * accept param array with chat message
     * return array with formed sendings
     */
    private array $actionPrepareList;

    /**
     * Initialize YouTube ChatBot
     * @param \Anet\App\YouTubeHelpers\ConnectParams $connect object with connection params
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
        $this->currentUsersList = [];
        $this->actionPrepareList = array_filter(get_class_methods(self::class), fn ($method) => preg_match('/^actionPrepare\w*Sendings$/', $method));
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
     * @param \Anet\App\YouTubeHelpers\ConnectParams $connect object with connection params
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
     * @param \Anet\App\YouTubeHelpers\ConnectParams $connect object with connection params
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
     * @return string[][] actual message list
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
        $this->currentUsersList = [];
        $sendingList = [];
        $sendings = [];

        if (empty($chatlist)) {
            return [];
        }

        foreach ($chatlist as $chatItem) {
            if ($chatItem['authorName'] === $this->botUserName) {
                continue;
            }

            $this->buffer->add('messageList', ['content' => $chatItem['message'], 'user_key' => $chatItem['authorId']]);
            $this->users->handler($chatItem['authorId'], 'incrementRaitingRandom', 2, random_int(1, 2));
            $this->currentUsersList[] = $this->users->get($chatItem['authorId']);

            foreach ($this->actionPrepareList as $action) {
                $sendings = $this->{$action}($chatItem);
                if (! empty($sendings)) {
                    $sendingList = array_merge($sendingList, $sendings);

                    if (! $this->listeningFlag) {
                        break 2;
                    }

                    continue 2;
                }
            }
        }

        $sendingList = array_merge($sendingList, $this->prepareSystemSendings());

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
     * **Method** get detail for sending by current youtube chat message
     * @param string[] $chatItem current youtube chat message
     * @return string[] detail for sending
     */
    private function getSendingDetail(array $chatItem) : array
    {
        return [
            'author' => $chatItem['authorName'],
            'message' => $chatItem['message'],
            'published' => $chatItem['published'],
        ];
    }

    /**
     * **Method** prepare sendings from system
     * @return string[][] sendings from system
     */
    private function prepareSystemSendings() : array
    {
        $sendingDetail = [
            'author' => 'System',
            'message' => 'none',
            'published' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];
        $result = [];

        $result = array_merge($result, $this->prepareSystemGamesSendings($sendingDetail));
        $result = array_merge($result, $this->prepareSystemLotterySendings($sendingDetail));

        return $result;
    }

    /**
     * **Method** prepare system sendings from game module
     * @param string[] $sendingDetail system headers for sendings
     * @return string[][] system sendings from game module
     */
    private function prepareSystemGamesSendings(array $sendingDetail) : array
    {
        $result = [];

        foreach ($this->games->checkSessionsTimeOut() as $systemMess) {
            $sendingDetail['sending'] = $systemMess;
            $result[] = $sendingDetail;
        }

        return $result;
    }

    /**
     * **Method** prepare system sendings from lottery module
     * @param string[] $sendingDetail system headers for sendings
     * @return string[][] system sendings from lottery module
     */
    private function prepareSystemLotterySendings(array $sendingDetail) : array
    {
        $result = [];

        foreach ($this->currentUsersList as $user) {
            $lotery = $this->users->randomLottery($user, 5000);

            if (! empty($lotery)) {
                $sendingDetail['sending'] = $lotery;
                $result[] = $sendingDetail;
                break;
            }
        }

        return $result;
    }

    /**
     * **Method** [is action of prepareSendings] prepare sendings from admin commands module by current youtube chat message
     * @param string[] $chatItem current youtube chat message
     * @return string[][] sendings from admin commands module
     */
    private function actionPrepareAdminSendings(array $chatItem) : array
    {
        if (! $this->users->get($chatItem['authorId'])?->checkAdmin()) {
            return [];
        }

        $sendingDetail = $this->getSendingDetail($chatItem);
        $sendTo = "@{$chatItem['authorName']} ";

        switch (true) {
            case $chatItem['message'] === '/help-admin':
                $sendingDetail['sending'] = $sendTo . '</stop> —— завершить скрипт; </insert user> —— добавить юзера в список слежения ; </drop user> —— удалить юзера из списка слежения; </show-listern> —— список слежения;';
                break;
            case $chatItem['message'] === '/stop':
                $sendingDetail['sending'] = $sendTo . 'Завершаю свою работу.';
                $this->listeningFlag = false;
                break;
            case mb_stripos($chatItem['message'], '/insert') !== false:
                $listernUser = preg_replace('/\/insert /', '', $chatItem['message']);
                $this->usersListening[$listernUser] = $listernUser;

                $sendingDetail['sending'] = $sendTo . "Начинаю слушать пользователя <$listernUser>";
                break;
            case mb_stripos($chatItem['message'], '/drop') !== false:
                $listernUser = preg_replace('/\/drop /', '', $chatItem['message']);
                unset($this->usersListening[$listernUser]);

                $sendingDetail['sending'] = $sendTo . "Прекращаю слушать пользователя <$listernUser>";
                break;
            case mb_stripos($chatItem['message'], '/show-listern') !== false:
                $sendingDetail['sending'] = $sendTo . 'Список прослушиваемых: ' . implode(',', $this->usersListening);
                break;
        }

        if (! array_key_exists('sending', $sendingDetail)) {
            return [];
        }

        return [$sendingDetail];
    }

    /**
     * **Method** [is action of prepareSendings] prepare sendings from game module by current youtube chat message
     * @param string[] $chatItem current youtube chat message
     * @return string[][] sendings from game module
     */
    private function actionPrepareGamesSendings(array $chatItem) : array
    {
        if (! $this->games->checkUserActiveSession($chatItem['authorId'])) {
            return [];
        }

        $sendingDetail = $this->getSendingDetail($chatItem);
        $matches = explode(' ', trim(str_replace(['!', ',', '.', '?'], '', $chatItem['message'])));
        $lastWord = mb_strtolower(array_pop($matches));
        $sendingDetail['sending'] = $this->games->checkGame($chatItem['authorId'], $lastWord);

        return [$sendingDetail];
    }

    /**
     * **Method** [is action of prepareSendings] prepare sendings from default commands module by current youtube chat message
     * @param string[] $chatItem current youtube chat message
     * @return string[][] sendings from default commands module
     */
    private function actionPrepareCommandSendings(array $chatItem) : array
    {
        $user = $this->users->get($chatItem['authorId']);
        $sendingDetail = $this->getSendingDetail($chatItem);
        $sendTo = "@{$chatItem['authorName']} ";
        $sendings = [];
        $result = [];

        switch (true) {
            case in_array($chatItem['message'], ['/help', '/справка']):
                $sendings[] = $sendTo . 'приветствую, в данном чате доступны следующие команды: </stat (/стата) "@user"> получить статистику по себе (или по указанному юзеру); —— </joke (/шутка)> получить баянистый анекдот;';
                $sendings[] = '—— </fact (/факт)> получить забавный (или не очень) факт; —— </stream (/стрим)> получить информацию о стриме; —— </play> раздел игр';
                break;
            case mb_ereg_match('.*(\/stat|\/стата) @', $chatItem['message']):
                $sendings = $this->users->showUserStatistic(mb_ereg_replace('.*(\/stat|\/стата) @', '', $chatItem['message']));
                break;
            case in_array($chatItem['message'], ['/stat', '/стата']):
                $sendings = $this->users->showUserStatistic($chatItem['authorName']);
                break;
            case in_array($chatItem['message'], ['/stream', '/стрим']):
                $sendings = $this->video->showStatistic();
                break;
            case in_array($chatItem['message'], ['/факт', '/fact']):
                $sendings = Contents\Facts::fetchRand();
                break;
            case in_array($chatItem['message'], ['/шутка', '/joke']):
                $sendings = Contents\Jokes::fetchRand();
                break;
            case mb_strpos($chatItem['message'], '/play') !== false:
                switch (true) {
                    case $chatItem['message'] === Games\Roulette::COMMAND_HELP:
                        $sendings = Games\Roulette::getHelpMessage();
                        break;
                    case $chatItem['message'] === Games\Сasino::COMMAND_HELP:
                        $sendings = Games\Сasino::getHelpMessage();
                        break;
                    case $chatItem['message'] === Games\Towns::COMMAND_HELP:
                        $sendings = Games\Towns::getHelpMessage();
                        break;
                    case $chatItem['message'] === Games\Cows::COMMAND_HELP:
                        $sendings = Games\Cows::getHelpMessage();
                        break;
                    case $chatItem['message'] === Games\Roulette::COMMAND_START:
                        $sendings = $this->games->validateAndStarting(new Games\Roulette($user), $user, 180);
                        break;
                    case $chatItem['message'] === Games\Сasino::COMMAND_START:
                        $sendings = $this->games->validateAndStarting(new Games\Сasino($user), $user, 300, 300);
                        break;
                    case $chatItem['message'] === Games\Towns::COMMAND_START:
                        $sendings = $this->games->validateAndStarting(new Games\Towns($user), $user, 120, 55);
                        break;
                    case $chatItem['message'] === Games\Cows::COMMAND_START:
                        $sendings = $this->games->validateAndStarting(new Games\Cows($user), $user, 120, 55);
                        break;
                    default:
                        $sendings[] = sprintf('В настоящее время доступны следующие игры: —— русская рулетка <%s> —— казино <%s> —— города <%s> —— быки и коровы <%s>', Games\Roulette::COMMAND_HELP, Games\Сasino::COMMAND_HELP, Games\Towns::COMMAND_HELP, Games\Cows::COMMAND_HELP);
                        $sendings[] = 'Внимание! - каждое следующее сообщение игрока после старта игры засчитывается как ход, на игру отводится определенное время, по истечению засчитывается проигрыш с максимумом очков';
                        break;
                }
                break;
        }

        if (empty($sendings)) {
            return [];
        }

        $this->users->handler($user->getId(), 'incrementRaiting', rand(0, 4) * 5);

        foreach ($sendings as $item) {
            $sendingDetail['sending'] = $item;
            $result[] = $sendingDetail;
        }

        return $result;
    }

    /**
     * **Method** [is action of prepareSendings] prepare sendings from greeting and parting module by current youtube chat message
     * @param string[] $chatItem current youtube chat message
     * @return string[][] sendings from greeting and parting module
     */
    public function actionPrepareGreetingSendings(array $chatItem) : array
    {
        if (! $this->timeTracker->trackerState('standart_responce') || $this->timeTracker->trackerCheck('standart_responce', 30)) {
            $this->timeTracker->trackerStop('standart_responce');

            foreach ($this->vocabulary->getCategoriesGroup('standart', ['greetings', 'parting']) as $category) {
                foreach ($category['request'] as $option) {
                    if (mb_stripos(mb_strtolower($chatItem['message']), $option) !== false) {
                        $answer = $this->prepareSmartAnswer($option, false);

                        if (empty($answer)) {
                            return [];
                        }

                        $sendingDetail = $this->getSendingDetail($chatItem);
                        $sendingDetail['sending'] = "@{$chatItem['authorName']} $answer";
                        $this->timeTracker->trackerStart('standart_responce');

                        return [$sendingDetail];
                    }
                }
            }
        }

        return [];
    }

    /**
     * **Method** [is action of prepareSendings] prepare sendings from dead inside joke module by current youtube chat message
     * @param string[] $chatItem current youtube chat message
     * @return string[][] sendings from dead inside joke module
     */
    public function actionPrepareDeadInsideSendings(array $chatItem) : array
    {
        $matches = explode(' ', trim(str_replace(['!', ',', '.', '?'], '', $chatItem['message'])));
        $lastWord = mb_strtolower(array_pop($matches));

        if (! in_array($lastWord, $this->vocabulary->getCategoryType('dead_inside', 'request'))) {
            return [];
        }

        $sendingDetail = $this->getSendingDetail($chatItem);
        $sendingDetail['sending'] = "@{$chatItem['authorName']} сколько будет {$lastWord}-7?";

        return [$sendingDetail];
    }

    /**
     * **Method** [is action of prepareSendings] prepare sendings from base communication module by current youtube chat message
     * @param string[] $chatItem current youtube chat message
     * @return string[][] sendings from base communication module
     */
    public function actionPrepareBotCommunicationSendings(array $chatItem) : array
    {
        if (mb_stripos(mb_strtolower($chatItem['message']), $this->botUserName) === false) {
            return [];
        }

        $this->users->handler($chatItem['authorId'], 'incrementRaiting', rand(0, 4) * 5);

        $sendingDetail = $this->getSendingDetail($chatItem);
        $sendingDetail['sending'] = sprintf('@%s $s', $chatItem['authorName'], $this->prepareSmartAnswer(trim(mb_strtolower(preg_replace("/@?{$this->botUserName}/", '', $chatItem['message'])))));

        return [$sendingDetail];
    }

    /**
     * **Method** [is action of prepareSendings] prepare sendings from vocabulary module by current youtube chat message
     * @param string[] $chatItem current youtube chat message
     * @return string[][] sendings from vocabulary module
     */
    public function actionPrepareVocabularySendings(array $chatItem) : array
    {
        $sendingDetail = $this->getSendingDetail($chatItem);
        $matches = explode(' ', trim(str_replace(['!', ',', '.', '?'], '', $chatItem['message'])));
        $lastWord = mb_strtolower(array_pop($matches));

        foreach ($this->vocabulary->getCategoriesGroup('another', ['say_yes', 'say_no', 'say_haha', 'say_foul', 'say_three']) as $key => $item) {
            if (in_array($key, ['say_haha', 'say_foul', 'say_three'])) {
                foreach ($item['request'] as $option) {
                    if (mb_stripos(mb_strtolower($chatItem['message']), $option) !== false) {
                        if ($key === 'say_foul') {
                            $this->users->handler($chatItem['authorId'], 'incrementRaiting', rand(0, 2) * (-5));
                        }

                        $sendingDetail['sending'] = sprintf('@%s $s', $chatItem['authorName'], $this->vocabulary->getRandItem($key));

                        return [$sendingDetail];
                    }
                }
            } elseif (in_array($lastWord, $item['request'])) {
                $sendingDetail['sending'] = sprintf('@%s $s', $chatItem['authorName'], $this->vocabulary->getRandItem($key));

                return [$sendingDetail];
            }
        }

        return [];
    }

    /**
     * **Method** [is action of prepareSendings] prepare sendings from users listening module by current youtube chat message
     * @param string[] $chatItem current youtube chat message
     * @return string[][] sendings from users listening module
     */
    public function actionPrepareUsersListenSendings(array $chatItem) : array
    {
        if (! in_array($chatItem['authorName'], $this->usersListening)) {
            return [];
        }

        $answer = $this->prepareSmartAnswer($chatItem['message'], true);

        if (empty($answer)) {
            return [];
        }

        $sendingDetail = $this->getSendingDetail($chatItem);
        $sendingDetail['sending'] = "@{$chatItem['authorName']} $answer";

        return [$sendingDetail];
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
