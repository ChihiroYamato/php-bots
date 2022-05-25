<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Anet\Bots;
use App\Anet\YouTubeHelpers;

session_start();

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
            return 1;
    }

    if (Bots\YouTube::createAuthTokken($connectParams)) {
        echo 'oAuth tokken saved successful' . PHP_EOL;
    } else {
        echo 'file with oAuth tokken is already exist' . PHP_EOL;
    }

    return 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="./youtube_auth.php" method="POST">
        <select name="project">
            <option disabled selected>select</option>
            <option value="main">main</option>
            <option value="fallback">fallback</option>
        </select>
        <button>Go</button>
    </form>
</body>
</html>
