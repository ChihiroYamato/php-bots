<?php

namespace Anet\App\Helpers\Traits;

trait UrlHelperTrait
{
    private static function fetchCurrentUrl() : string
    {
        $protocol = strtolower(preg_replace('/\/.*/', '://', $_SERVER['SERVER_PROTOCOL']));
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);

        return $protocol . $host . $baseUrl;
    }
}
