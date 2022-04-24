<?php

require_once __DIR__ . '/src/vendor/autoload.php';

if (! file_exists(OAUTH_TOKEN_JSON)) {
    echo 'Create file with oAuth tokken';
    die;
}

if (! file_exists(API_KEY_JSON)) {
    echo 'Create file with api key for youtube';
    die;
}

use Anet\Bots\Helpers\TimeTracker;
use Google\Client;
use Google\Service\YouTube;

$timeTracker = new TimeTracker(); // TODO ------------------------------------- TIMER

$client = new Client();
$client->setApplicationName('Yamato-Chat-Bot');

$client->setAuthConfig(CLIENT_SECRET_JSON);
$client->setAccessType('offline');

$client->setScopes([
    YouTube::YOUTUBE_FORCE_SSL,
    YouTube::YOUTUBE_READONLY,
]);
$client->setLoginHint('alexan9610@gmail.com');
$client->setAccessToken(json_decode(file_get_contents(OAUTH_TOKEN_JSON), true));

$timeTracker->setPoint('login'); // TODO ------------------------------------- TIMER

$queryString = json_decode(file_get_contents(API_KEY_JSON), true);

if (! is_array($queryString)) {
    throw new \Exception('Incorrect api key json');
}

$queryString['id'] = 'HkjV2ZmFHMg';
$queryString['part'] = 'liveStreamingDetails';

$videoUrlApi = 'https://www.googleapis.com/youtube/v3/videos';
$videoUrlApi .= '?' . http_build_query($queryString);

$curl = curl_init();

curl_setopt($curl, CURLOPT_URL, $videoUrlApi);
curl_setopt($curl, CURLOPT_USERAGENT, DEFAULT_USER_AGENT);
curl_setopt($curl, CURLOPT_HTTPGET, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

if (($response = curl_exec($curl)) === false) {
    throw new Exception('Error request for current URL');
}

$timeTracker->setPoint('curl_response'); // TODO ------------------------------------- TIMER

$response = json_decode($response, true);

$liveChatID = $response['items'][0]['liveStreamingDetails']['activeLiveChatId'] ?? null;

if ($liveChatID === null) {
    throw new Exception('Error response with live chat ID');
}

$service = new YouTube($client);

$queryParams = [
    'maxResults' => 100,
];


$response = $service->liveChatMessages->listLiveChatMessages($liveChatID, 'snippet', $queryParams);
echo '<pre>';
print_r($response['items']);
echo '</pre>';

$timeTracker->setPoint('youtube'); // TODO ------------------------------------- TIMER

var_dump($timeTracker->getStatistic()); // TODO ------------------------------------- TIMER
