<?php

namespace Anet\App\YouTubeHelpers;

use Google\Service;
use Anet\App\Helpers;

/**
 * **VideoProperties** -- class storage of youtube video properties
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 1.0
 */
final class VideoProperties
{
    /**
     * @var string current timezone
     */
    public const TIME_ZONE = 'Europe/Moscow';

    /**
     * @var \Google\Service\YouTube $youtube instance of Youtube Service class
     */
    private Service\YouTube $youtube;
    /**
     * @var \Anet\App\Helpers\TimeTracker $timeTracker instance of TimeTracker class
     */
    private Helpers\TimeTracker $timeTracker;
    /**
     * @var \DateTime $videoStarting timestamp of video starting
     */
    private \DateTime $videoStarting;
    /**
     * @var string $youtubeURL link to youtube video
     */
    private string $youtubeURL;
    /**
     * @var string $videoID youtube video id
     */
    private string $videoID;
    /**
     * @var string $liveChatID id of youtube video live chat
     */
    private string $liveChatID;
    /**
     * @var null|string $totalViews total count of current video view
     */
    private ?string $totalViews;

    /**
     * Initialized VideoProperties
     * @param \Google\Service\YouTube $youtube instance of YouTube Service class
     * @param string $youtubeURL link to youtube video
     * @return void
     */
    public function __construct(Service\YouTube $youtube, string $youtubeURL)
    {
        $this->youtube = $youtube;
        $this->timeTracker = new Helpers\TimeTracker();
        $this->youtubeURL = $this->validateYoutubeURL($youtubeURL);
        $this->totalViews = null;
        $this->videoID = $this->fetchVideoID($this->youtubeURL);
        $this->fetchProperties();
    }

    /**
     * **Method** get youtube video link
     * @return string youtube video link
     */
    public function getYoutubeURL() : string
    {
        return $this->youtubeURL;
    }

    /**
     * **Method** get youtube video id
     * @return string youtube video id
     */
    public function getVideoID() : string
    {
        return $this->videoID;
    }

    /**
     * **Method** get youtube video live chat id
     * @return string youtube video live chat id
     */
    public function getLiveChatID() : string
    {
        return $this->liveChatID;
    }

    /**
     * **Method** get formated timestamp of video starting
     * @param string $format `[optional]` format of timestamp
     * @return string formated timestamp of video starting
     */
    public function getVideoStarting(string $format = 'Y-m-d H:i:s') : string
    {
        return $this->videoStarting->format($format);
    }

    /**
     * **Method** get readble statistic of video
     * @return string[] list of message to show
     */
    public function showStatistic() : array
    {
        $properties = $this->getStatistic();

        return ["Стрим начался в: {$properties['videoStarting']} —— длительность: {$properties['duration']} —— Всего просмотров: {$properties['totalViews']}"];
    }

    /**
     * **Method** get statistic of video, fetch info from youtube server with interval of 10 minutes
     * @return string[] list of statistic properties
     */
    public function getStatistic() : array
    {
        if (! $this->timeTracker->trackerState(__FUNCTION__) || $this->timeTracker->trackerCheck(__FUNCTION__, 60 * 10)) {
            $this->timeTracker->trackerStart(__FUNCTION__);

            $this->totalViews = $this->fetchViews();
        }

        return [
            'videoStarting' => $this->videoStarting->format('H:i:s T'),
            'duration' => $this->videoStarting->diff(new \DateTime())->format('%H:%I:%S'),
            'totalViews' => $this->totalViews,
        ];
    }

    /**
     * **Method** fetch general properties of video from youtube server and setup to class vars
     * @return void
     */
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

    /**
     * **Method** fetch video views from youtube server
     * @return string video views
     */
    private function fetchViews() : string
    {
        $response = $this->youtube->videos->listVideos('statistics', ['id' => $this->videoID]);

        return $response['items'][0]['statistics']['viewCount'] ?? '';
    }

    /**
     * **Method** validate youtube url from params
     * @param string $url youtube url
     * @return string validated youtube url
     * @throw `Google\Service\Exception`
     */
    private function validateYoutubeURL(string $url) : string
    {
        if (! preg_match('/https:\/\/www\.youtube\.com.*/', $url)) {
            throw new Service\Exception('Incorrect YouTube url');
        }

        return $url;
    }

    /**
     * **Method** fetch video id from youtube video link
     * @param string $url youtube video link
     * @return string video id
     */
    private function fetchVideoID(string $url) : string
    {
        preg_match('/youtube\.com\/watch\?.*v=([^&]+)/',  $url, $matches);

        if (empty($matches[1])) {
            throw new Service\Exception('Incorrect YouTube video ID');
        }

        return $matches[1];
    }
}
