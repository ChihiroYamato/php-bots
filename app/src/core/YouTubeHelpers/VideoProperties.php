<?php

namespace App\Anet\YouTubeHelpers;

use Google\Service;
use App\Anet\Helpers;

final class VideoProperties
{
    public const TIME_ZONE = 'Europe/Moscow';

    private Service\YouTube $youtube;
    private Helpers\TimeTracker $timeTracker;
    private \DateTime $videoStarting;
    private string $youtubeURL;
    private string $videoID;
    private string $liveChatID;
    private ?string $totalViews;

    public function __construct(Service\YouTube $youtube, string $youtubeURL)
    {
        $this->youtube = $youtube;
        $this->timeTracker = new Helpers\TimeTracker();
        $this->youtubeURL = $this->validateYoutubeURL($youtubeURL);
        $this->totalViews = null;
        $this->videoID = $this->fetchVideoID($this->youtubeURL);
        $this->fetchProperties();

    }

    public function getYoutubeURL() : string
    {
        return $this->youtubeURL;
    }

    public function getVideoID() : string
    {
        return $this->videoID;
    }

    public function getLiveChatID() : string
    {
        return $this->liveChatID;
    }

    public function getVideoStarting(string $format = 'Y-m-d H:i:s') : string
    {
        return $this->videoStarting->format($format);
    }

    public function showStatistic() : array
    {
        $properties = $this->getStatistic();

        return ["Стрим начался в: {$properties['videoStarting']} —— Всего просмотров: {$properties['totalViews']}"];
    }

    public function getStatistic() : array
    {
        if (! $this->timeTracker->trackerState(__FUNCTION__) || $this->timeTracker->trackerCheck(__FUNCTION__, 60 * 10)) {
            $this->timeTracker->trackerStart(__FUNCTION__);

            $this->totalViews = $this->fetchViews();
        }

        return [
            'videoStarting' => $this->videoStarting->format('H:i:s'),
            'totalViews' => $this->totalViews,
        ];
    }

    private function fetchProperties() : void
    {
        $response = $this->youtube->videos->listVideos('liveStreamingDetails', ['id' => $this->videoID]);

        $liveChatID = $response['items'][0]['liveStreamingDetails']['activeLiveChatId'] ?? null;
        $videoStarting = $response['items'][0]['liveStreamingDetails']['actualStartTime'] ?? null;

        if ($liveChatID === null) {
            throw new Service\Exception('Error response with live chat ID');
        }

        if ($videoStarting === null) {
            throw new Service\Exception('Error response with actual Start Time of video');
        }

        $this->liveChatID = $liveChatID;
        $this->videoStarting = new \DateTime($videoStarting);
        $this->videoStarting->setTimezone(new \DateTimeZone(self::TIME_ZONE));
    }

    private function fetchViews() : string
    {
        $response = $this->youtube->videos->listVideos('statistics', ['id' => $this->videoID]);

        return $response['items'][0]['statistics']['viewCount'] ?? '';
    }

    private function validateYoutubeURL(string $url) : string
    {
        if (! preg_match('/https:\/\/www\.youtube\.com.*/', $url)) {
            throw new Service\Exception('Incorrect YouTube url');
        }

        return $url;
    }

    private function fetchVideoID(string $url) : string
    {
        preg_match('/youtube\.com\/watch\?.*v=([^&]+)/',  $url, $matches);

        if (empty($matches[1])) {
            throw new Service\Exception('Incorrect YouTube video ID');
        }

        return $matches[1];
    }
}
