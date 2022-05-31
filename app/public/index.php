<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Anet\Controllers;

session_start();

Controllers\Web::route('/youtube_auth', 'getYoutubeAuth');

http_response_code(404);
exit;
