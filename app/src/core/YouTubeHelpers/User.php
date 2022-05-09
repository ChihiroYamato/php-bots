<?php

namespace App\Anet\YouTubeHelpers;

final class User
{
    private string $id;
    private string $name;
    private string $channelUrl;
    private \DateTime $lastPublished;
    private \DateTime $registration;
    private \DateTime $lastUpdate;
    private ?int $subscribers;
    private ?int $videos;
    private ?int $views;
    private int $messages;
    private int $rating;
    private bool $isActive;
    private bool $isAdmin;

    public function __construct(string $id, array $params, bool $isAdmin = false, bool $isActive = true)
    {
        $this->id = $id;
        $this->name = $params['name'];
        $this->channelUrl = 'https://www.youtube.com/channel/' . $id;
        $this->lastPublished = new \DateTime($params['last_published'] ?? 'now');
        $this->registration = new \DateTime($params['registation_date'] ?? 'now');
        $this->lastUpdate = new \DateTime($params['last_update'] ?? 'now');
        $this->subscribers = $params['subscriber_count'] ?? null;
        $this->videos = $params['video_count'] ?? null;
        $this->views = $params['view_count'] ?? null;
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

    public function getLastPublished() : \DateTime
    {
        return $this->lastPublished;
    }

    public function getLastUpdate() : \DateTime
    {
        return $this->lastUpdate;
    }

    public function getRegistration() : \DateTime
    {
        return $this->registration;
    }

    public function getSubscribers() : ?int
    {
        return $this->subscribers;
    }

    public function getVideos() : ?int
    {
        return $this->videos;
    }

    public function getViews() : ?int
    {
        return $this->views;
    }

    public function getMessages() : int
    {
        return $this->messages;
    }

    public function getRating() : int
    {
        return $this->rating;
    }

    public function getChannelUrl() : string
    {
        return $this->channelUrl;
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

    public function updateName(string $name) : User
    {
        $this->name = $name;

        return $this;
    }

    public function updateSubscribers(int $subscribers) : User
    {
        $this->subscribers = $subscribers;

        return $this;
    }

    public function updateVideos(int $videos) : User
    {
        $this->videos = $videos;

        return $this;
    }

    public function updateViews(int $views) : User
    {
        $this->views = $views;

        return $this;
    }

    public function commit() : void
    {
        $this->lastUpdate->setTimestamp(time());
    }
}
