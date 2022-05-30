<?php

namespace App\Anet\YouTubeHelpers;

/**
 * **User** -- class wrapper of Youtube users
 */
final class User implements UserInterface
{
    /**
     * @var string $id `private` user id
     */
    private string $id;
    /**
     * @var string $name `private` user name
     */
    private string $name;
    /**
     * @var string $channelUrl `private` user channel url
     */
    private string $channelUrl;
    /**
     * @var \DateTime $lastPublished `private` user last published
     */
    private \DateTime $lastPublished;
    /**
     * @var \DateTime $registration `private` user registation date
     */
    private \DateTime $registration;
    /**
     * @var \DateTime $lastUpdate `private` user last update in DB
     */
    private \DateTime $lastUpdate;
    /**
     * @var null|int $subscribers `private` user subscribers count
     */
    private ?int $subscribers;
    /**
     * @var null|int $videos `private` user video count
     */
    private ?int $videos;
    /**
     * @var null|int $views `private` user views count
     */
    private ?int $views;
    /**
     * @var int $messages `private` user messages count
     */
    private int $messages;
    /**
     * @var int $rating `private` user current rating
     */
    private int $rating;
    /**
     * @var bool $isActive `private` user active status
     */
    private bool $isActive;
    /**
     * @var bool $isAdmin `private` user admin status
     */
    private bool $isAdmin;

    /**
     * Initialize User
     * @param string $id user id
     * @param array $params list of params, necessarily to be `'name'`, optional are:
     * `'last_published'`, `'registation_date'`, `'last_update'`, `'subscriber_count'`, `'video_count'`,
     * `'view_count'`, `'message_count'`, `'social_rating'`
     * @param bool $isAdmin `[optional]` user admin status, default false
     * @param bool $isActive `[optional]` user active status, default true
     * @return void
     */
    public function __construct(string $id, array $params, bool $isAdmin = false, bool $isActive = true)
    {
        $this->id = $id;
        $this->name = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $params['name']);
        $this->channelUrl = 'channel/' . $id;
        $this->lastPublished = new \DateTime($params['last_published'] ?? 'now');
        $this->registration = new \DateTime($params['registation_date'] ?? 'now');
        $this->lastUpdate = new \DateTime($params['last_update'] ?? 'now');
        $this->subscribers = $params['subscriber_count'] ?? 0;
        $this->videos = $params['video_count'] ?? 0;
        $this->views = $params['view_count'] ?? 0;
        $this->messages = $params['message_count'] ?? 0;
        $this->rating = $params['social_rating'] ?? 50;
        $this->isActive = $isActive;
        $this->isAdmin = $isAdmin;
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function checkActive(): bool
    {
        return $this->isActive;
    }

    public function checkAdmin() : bool
    {
        return $this->isAdmin;
    }

    public function getMessages() : int
    {
        return $this->messages;
    }

    public function getRating() : int
    {
        return $this->rating;
    }

    public function incrementMessage(int $count = 1) : void
    {
        $this->messages += $count;
        $this->lastPublished->setTimestamp(time());
    }

    public function incrementRaiting(int $count = 5) : void
    {
        $this->rating += $count;
    }

    /**
     * **Method** get timestamp of lust user published
     * @return \DateTime lust user published
     */
    public function getLastPublished() : \DateTime
    {
        return $this->lastPublished;
    }

    /**
     * **Method** get timestamp of lust user update in DB
     * @return \DateTime lust user date in DB
     */
    public function getLastUpdate() : \DateTime
    {
        return $this->lastUpdate;
    }

    /**
     *  **Method** get timestamp of user registation
     * @return \DateTime user registation
     */
    public function getRegistration() : \DateTime
    {
        return $this->registration;
    }

    /**
     * **Method** get user subscribers count
     * @return null|int subscribers count
     */
    public function getSubscribers() : int
    {
        return $this->subscribers;
    }

    /**
     * **Method** get user video count
     * @return null|int user video count
     */
    public function getVideos() : int
    {
        return $this->videos;
    }

    /**
     * **Method** get user views count
     * @return null|int user views count
     */
    public function getViews() : int
    {
        return $this->views;
    }

    /**
     * **Method* get user channel url
     * @return string user channel url
     */
    public function getChannelUrl() : string
    {
        return $this->channelUrl;
    }

    /**
     * **Method** increment rating by random circumstance
     * @param int $range max rand entropy
     * @param int $count value of rating increment
     * @return void
     */
    public function incrementRaitingRandom(int $range, int $count = 1) : void
    {
        if ($range > 0 && random_int(0, $range) === 0) {
            $this->rating += $count;
        }
    }

    /**
     * **Method** save new user name
     * @param string $name new user name
     * @return \App\Anet\YouTubeHelpers\User return instance of User
     */
    public function updateName(string $name) : User
    {
        $this->name = $name;

        return $this;
    }

    /**
     * **Method** save new subscribers count
     * @param null|int $subscribers new user subscribers count
     * @return \App\Anet\YouTubeHelpers\User return instance of User
     */
    public function updateSubscribers(int $subscribers) : User
    {
        $this->subscribers = $subscribers;

        return $this;
    }

    /**
     * **Method** save new videos count
     * @param null|int $videos new user videos count
     * @return \App\Anet\YouTubeHelpers\User return instance of User
     */
    public function updateVideos(int $videos) : User
    {
        $this->videos = $videos;

        return $this;
    }

    /**
     * **Method** save new views count
     * @param null|int $views new user views count
     * @return \App\Anet\YouTubeHelpers\User return instance of User
     */
    public function updateViews(int $views) : User
    {
        $this->views = $views;

        return $this;
    }

    /**
     * **Method** set new timestamp of current updating
     * @return void
     */
    public function commit() : void
    {
        $this->lastUpdate->setTimestamp(time());
    }
}
