<?php

namespace App\Anet\YouTubeHelpers;

use Google\Service;
use App\Anet\Helpers;
use App\Anet\DB;

final class UserStorage
{
    use Helpers\ErrorHelperTrait;

    private const DAYS_WITHOUT_UPDATE = 2;
    private const DAYS_WITHOUT_ACTIVE = 365;

    private Service\YouTube $youtube;
    private array $storage;

    public function __construct(Service\YouTube $youtube)
    {
        $this->youtube = $youtube;
        $this->downloadUsers();
    }

    public function __destruct()
    {
        $this->savedUsers();
        print_r("Users saved\n");
    }

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

        return $this->storage[$id];
    }

    public function get(string $id) : ?User
    {
        if (! array_key_exists($id, $this->storage)) {
            return null;
        }

        return $this->storage[$id];
    }

    public function showUserStatistic(string $name) : array
    {
        $mess = "Пользователь: $name —— ";
        $user = $this->findUserByName($name);

        if ($user === null) {
            return [$mess . 'в базе данных не найден.'];
        }

        return [
            $mess . 'Дата регистрации: ' . $user->getRegistration()->format('Y-m-d') . ' —— Последняя активность в чате: ' . $user->getLastPublished()->format('Y-m-d H:i:s') . ' —— Подписчиков: ' . $user->getSubscribers() . ' —— Видео на канале: ' . $user->getVideos() . ' —— Всего просмотров: ' . $user->getViews(),
            ' —— Отправлено сообщений: ' . $user->getMessages() . ' —— Социальный рейтинг чата: ' . $user->getRating() . ' —— ссылка на пользователя: ' . $user->getChannelUrl(),
        ];
    }

    private function findUserByName(string $name) : ?User
    {
        foreach ($this->storage as $user) {
            if ($user->getName() === $name) {
                return $user;
            }
        }

        return null;
    }

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

    private function delete(User $user) : void
    {
        DB\DataBase::deleteYouTubeUser($user->getId());
        unset($user);
    }

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
            Helpers\LogerHelper::logging($this->getErrors());
            return [];
        }
    }

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

            if ($user->getLastUpdate()->diff(new \DateTime())->days >= self::DAYS_WITHOUT_ACTIVE) {
                $this->delete($user);
                continue;
            }

            if ($user->getLastUpdate()->diff(new \DateTime())->days >= self::DAYS_WITHOUT_UPDATE) {
                $this->updateGlobal($user);
            }

            $this->storage[$item['key']] = $user;
        }
    }

    private function savedUsers() : void
    {
        foreach ($this->storage as $user) {
            $this->updateLocal($user);
        }
    }
}
