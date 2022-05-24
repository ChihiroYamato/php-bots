<?php

namespace App\Anet\YouTubeHelpers;

use Google\Service;
use App\Anet\Helpers;
use App\Anet\DB;

/**
 * **UserStorage** class storage of User
 */
final class UserStorage
{
    use Helpers\ErrorTrait;

    /**
     * @var int `private` max days for users without update
     */
    private const DAYS_WITHOUT_UPDATE = 4;
    /**
     * @var int `private` max days for users without active
     */
    private const DAYS_WITHOUT_ACTIVE = 365;
    /**
     * @var string `private` prefix of redis storage keys
     */
    private const REDIS_PREFIX = 'youtube_user_';

    /**
     * @var \Google\Service\YouTube $youtube `private` instance of Yotube Service class
     */
    private Service\YouTube $youtube;
    /**
     * @var array $storage `private` storage of users
     */
    private array $storage;

    /**
     * Initialize users from DB
     * @param \Google\Service\YouTube $youtube instance of Yotube Service class
     * @return void
     */
    public function __construct(Service\YouTube $youtube)
    {
        $this->youtube = $youtube;
        $this->downloadUsers();
    }

    /**
     * Saving Users to DB
     */
    public function __destruct()
    {
        $this->savedUsers();
        Helpers\Loger::print('System', 'Users saved');
    }

    /**
     * **Method** get instance of user from storage by id (or if it doesn't exist - fetch from youtube server)
     * @param string $id id of needed user
     * @return null|\App\Anet\YouTubeHelpers\User instance of user or null if user doesn't exist and response from youtube server is empty
     */
    public function fetch(string $id) : ?User
    {
        if (array_key_exists($id, $this->storage)) {
            return $this->storage[$id];
        }

        $responseUser = $this->requestUser($id);
        if (empty($responseUser)) {
            return null;
        }

        $this->storage[$id] = new User($id, $responseUser);
        $this->insert($this->storage[$id]);
        DB\Redis::setObject(self::REDIS_PREFIX . $id, $this->storage[$id]);

        return $this->storage[$id];
    }

    /**
     * **Method** get instance of user only from local storage
     * @param string $id id of needed user
     * @return null|\App\Anet\YouTubeHelpers\User instance of user or null if user doesn't exist
     */
    public function get(string $id) : ?User
    {
        if (! array_key_exists($id, $this->storage)) {
            return null;
        }

        return $this->storage[$id];
    }

    /**
     * **Method** get readnle messages with user statistic by user name
     * @param string $name user name
     * @return array list of messages
     */
    public function showUserStatistic(string $name) : array
    {
        $mess = "Пользователь: $name —— ";
        $user = $this->findUserByName($name);

        if ($user === null) {
            return [$mess . 'в базе данных не найден.'];
        }

        return [
            $mess . 'Дата регистрации: ' . $user->getRegistration()->format('Y-m-d') . ' —— Последняя активность в чате: ' . $user->getLastPublished()->format('Y-m-d H:i:s') . ' —— Подписчиков: ' . $user->getSubscribers() . ' —— Видео на канале: ' . $user->getVideos() . ' —— Всего просмотров: ' . $user->getViews(),
            ' —— Отправлено сообщений: ' . $user->getMessages() . ' —— Социальный рейтинг чата: ' . $user->getRating(), // todo  . ' —— ссылка на пользователя: ' . $user->getChannelUrl(),
        ];
    }

    /**
     * **Method** handle specified User method with specified params in current user
     * with updating user to redis storage
     * @param string $id current user's id
     * @param string $method existing method of User class
     * @param mixed ...$params specified list of params for User method
     * @return void
     */
    public function handler(string $id, string $method, ...$params) : void
    {
        $user = $this->get($id);

        if ($user !== null && method_exists($user, $method)) {
            $user->{$method}(...$params);
            DB\Redis::setObject(self::REDIS_PREFIX . $id, $user);
        }
    }

    /**
     * **Method** checked user by insance for possibility to "win random" if success - return congratulation message
     * @param \App\Anet\YouTubeHelpers\User $user instance of current user
     * @param int $raiting rating which will increment current rating on success
     * @return string if success - congratulation message else - empty string
     */
    public function randomLottery(User $user, int $raiting) : string
    {
        if (random_int(0, 999999) !== 2022) {
            return '';
        }

        $this->handler($user->getId(), 'incrementRaiting', $raiting);

        return $user->getName() . " поздравляю! ты выбран случайным победителем приза в $raiting рейтинга! твой текущий рейтинг: " . $user->getRating();
    }

    /**
     * **Method** find user by name in storage
     * @param string $name user name
     * @return null|User instance of user if it's exist else - null
     */
    private function findUserByName(string $name) : ?User
    {
        foreach ($this->storage as $user) {
            if ($user->getName() === $name) {
                return $user;
            }
        }

        return null;
    }

    /**
     * **Method** add new user to DB
     * @param \App\Anet\YouTubeHelpers\User $user instance of user
     * @return void
     */
    private function insert(User $user) : void
    {
        $request = [
            'key' => $user->getId(),
            'name' => $user->getName(),
            'social_rating' => $user->getRating(),
            'registation_date' => $user->getRegistration()->format('Y-m-d'),
            'subscriber_count' => $user->getSubscribers(),
            'video_count' => $user->getVideos(),
            'view_count' => $user->getViews(),
        ];

        DB\DataBase::insertYoutubeUser($request);
    }

    /**
     * **Method** delete user from DB
     * @param \App\Anet\YouTubeHelpers\User $user instance of user
     * @return void
     */
    private function delete(User $user) : void
    {
        DB\DataBase::deleteYouTubeUser($user->getId());
        unset($user);
    }

    /**
     * **Method** update user to DB with general properties from request to youtube server
     * @param \App\Anet\YouTubeHelpers\User $user instance of current user
     * @return \App\Anet\YouTubeHelpers\User $user instance of current user
     */
    private function updateGlobal(User $user) : User
    {
        $responseUser = $this->requestUser($user->getId());

        if (empty($responseUser)) {
            return $user;
        }

        unset($responseUser['registation_date']);

        $user->updateName($responseUser['name'])
        ->updateSubscribers($responseUser['subscriber_count'])
        ->updateVideos($responseUser['video_count'])
        ->updateViews($responseUser['view_count'])
        ->commit();

        $responseUser['last_update'] = $user->getLastUpdate()->format('Y-m-d H:i:s');

        DB\DataBase::updateYouTubeUser($user->getId(), $responseUser);

        return $user;
    }

    /**
     * **Method** update user to DB with local properties
     * @param \App\Anet\YouTubeHelpers\User $user instance of current user
     * @return \App\Anet\YouTubeHelpers\User $user instance of current user
     */
    private function updateLocal(User $user) : User
    {
        $newParams = [
            'last_published' => $user->getLastPublished()->format('Y-m-d H:i:s'),
            'message_count' => $user->getMessages(),
            'social_rating' => $user->getRating(),
        ];

        DB\DataBase::updateYouTubeUser($user->getId(), $newParams);

        return $user;
    }

    /**
     * **Method** fetch user properties from youtube server
     * @param string $id user id
     * @return array list of user properties
     */
    private function requestUser(string $id) : array
    {
        try {
            $response = $this->youtube->channels->listChannels('snippet, statistics', ['id' => $id]);

            return [
                'name' => $response['items'][0]['snippet']['title'] ?? 'unknown',
                'registation_date' => $response['items'][0]['snippet']['publishedAt'] ?? null,
                'subscriber_count' => $response['items'][0]['statistics']['subscriberCount'] ?? null,
                'video_count' => $response['items'][0]['statistics']['videoCount'] ?? null,
                'view_count' => $response['items'][0]['statistics']['viewCount'] ?? null,
            ];
        } catch (Service\Exception $error) {
            $this->addError(__FUNCTION__, $error->getMessage());
            Helpers\Loger::logging('YouTube', $this->getErrors(), 'error');
            return [];
        }
    }

    /**
     * **Method** download users from DB with activity and publishing checks, save to local storage
     * @return void
     */
    private function downloadUsers() : void
    {
        $responseDB = DB\DataBase::fetchYouTubeUsers();

        foreach ($responseDB as $item) {
            $user = new User(
                $item['key'],
                [
                    'name' => $item['name'],
                    'registation_date' => $item['registation_date'],
                    'last_published' => $item['last_published'],
                    'subscriber_count' => $item['subscriber_count'],
                    'video_count' => $item['video_count'],
                    'view_count' => $item['view_count'],
                    'message_count' => $item['message_count'],
                    'social_rating' => $item['social_rating'],
                    'last_update' => $item['last_update'],
                ],
                $item['isAdmin'],
                $item['active']
            );

            if (! $user->checkAdmin() && $user->getLastUpdate()->diff(new \DateTime())->days >= self::DAYS_WITHOUT_ACTIVE) {
                $this->delete($user);
                continue;
            }

            if ($user->checkAdmin() || $user->getLastUpdate()->diff(new \DateTime())->days >= self::DAYS_WITHOUT_UPDATE) {
                $this->updateGlobal($user);
            }

            DB\Redis::setObject(self::REDIS_PREFIX . $user->getId(), $user);
            $this->storage[$user->getId()] = $user;
        }
    }

    /**
     * **Method** save all stored users to DB with local properties
     * @return void
     */
    private function savedUsers() : void
    {
        foreach (DB\Redis::fetch(self::REDIS_PREFIX . '*', true) as $user) {
            $this->updateLocal($user);
        }
    }
}
