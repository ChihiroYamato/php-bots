<?php

namespace App\Anet\GoogleModules;

use App\Anet\Helpers;
use Google\ApiCore;
use Google\Cloud\Dialogflow\V2;

final class SmallTalkModule
{
    use Helpers\ErrorHelperTrait;

    private ?string $smallTalkSession = null;
    private ?V2\SessionsClient $smallTalkClient = null;

    public function __construct()
    {
        $this->smallTalkSession = uniqid();
        $this->smallTalkClient = new V2\SessionsClient(['credentials' => SMALL_TALK_API_KEY]);
    }

    public function fetchAnswerFromSmallTalk(string $message) : string
    {
        try {
            $client = $this->smallTalkClient;
            $session = $client->sessionName(SMALL_TALK_ID, $this->smallTalkSession);

            $textInput = new V2\TextInput();
            $textInput->setText($message);
            $textInput->setLanguageCode('ru');

            $queryInput = new V2\QueryInput();
            $queryInput->setText($textInput);

            $response = $client->detectIntent($session, $queryInput);
            $queryResult = $response->getQueryResult();

            return $queryResult->getFulfillmentText();
        } catch (ApiCore\ApiException $error) {
            $this->addError(__FUNCTION__, $error->getMessage());
            Helpers\LogerHelper::logging($this->getErrors());
            return '';
        }
    }
}
