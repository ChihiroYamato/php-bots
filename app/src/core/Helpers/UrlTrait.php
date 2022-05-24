<?php

namespace App\Anet\Helpers;

/**
 * **UrlTrait** -- contains any methods for work with url
 */
trait UrlTrait
{
    /**
     * Method return url of current page cleared from query param
     * @return string url of current page
     */
    protected static function fetchCurrentUrl() : string
    {
        $protocol = strtolower(preg_replace('/\/.*/', '://', $_SERVER['SERVER_PROTOCOL']));
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);

        return $protocol . $host . $baseUrl;
    }
}
