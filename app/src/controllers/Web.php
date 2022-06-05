<?php

namespace Anet\Controllers;

use Anet\App\Bots;
use Anet\App\YouTubeHelpers;
use Anet\Views;

/**
 * **Web** class controller for web requests
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 1.0
 */
final class Web
{
    /**
     * **Method** handle request by URL
     * @param string $url url of request
     * @param string $method name of method for handle request
     * @return void
     */
    public static function route(string $url, string $method) : void
    {
        if ($_SERVER['REQUEST_URI'] === $url && method_exists(self::class, 'getYoutubeAuth')) {
            self::$method();
            exit;
        }
    }

    /**
     * **Method** handle for YoutubeAuth
     * @return void
     */
    public static function getYoutubeAuth()
    {
        if (! empty($_POST['project'])) {
            $_SESSION['project'] = $_POST['project'];
        }

        if (! empty($_SESSION['project'])) {
            switch (true) {
                case $_SESSION['project'] === 'main' || ! defined('YOUTUBE_RESERVE'):
                    $connectParams = new YouTubeHelpers\ConnectParams(
                        YOUTUBE_APP_NAME,
                        YOUTUBE_CLIENT_SECRET_JSON,
                        YOUTUBE_OAUTH_TOKEN_JSON
                    );
                    break;
                case $_SESSION['project'] === 'fallback':
                    $connectParams = new YouTubeHelpers\ConnectParams(
                        YOUTUBE_APP_NAME_RESERVE,
                        YOUTUBE_CLIENT_SECRET_JSON_RESERVE,
                        YOUTUBE_OAUTH_TOKEN_JSON_RESERVE
                    );
                    break;
                default:
                    echo 'Incorrect POST data' . PHP_EOL;
                    return;
            }

            if (Bots\YouTube::createAuthTokken($connectParams)) {
                echo 'oAuth tokken saved successful' . PHP_EOL;
            } else {
                echo 'file with oAuth tokken is already exist' . PHP_EOL;
            }

            return;
        }

        Views\View::getTemplate('youtube_auth_form');
    }
}
