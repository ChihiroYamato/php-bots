<?php

namespace App\Anet\YouTubeHelpers;

use Google\Service;

final class YoutubeProps
{
    private string $youtubeURL;
    private string $videoID;
    private string $liveChatID;

    public function __construct(string $youtubeURL)
    {
        $this->youtubeURL = $this->validateYoutubeURL($youtubeURL);
        $this->videoID = $this->fetchVideoID($this->youtubeURL);
    }

    public function setLiveChatID(string $liveChatID)
    {
        $this->liveChatID = $liveChatID;
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
