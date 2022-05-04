<?php

namespace App\Anet\Bots;

use App\Anet\Helpers;
use Google\ApiCore;
use Google\Cloud\Dialogflow\V2;

trait SmallTalkModuleTrait
{
    use Helpers\ErrorHelperTrait;

    private ?string $smallTalkSession = null;
    private ?V2\SessionsClient $smallTalkClient = null;

    protected function fetchAnswerFromSmallTalk(string $message) : string
    {
        try {
            $client = $this->getInstanceSmallTalkClient();
            $session = $client->sessionName(SMALL_TALK_ID, $this->getInstanceSmallTalkSession());

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
            var_dump($error->getMessage()); // Todo ============ logs?
            return '';
        }
    }

    protected function getInstanceSmallTalkClient() : V2\SessionsClient
    {
        if ($this->smallTalkClient === null) {
            $this->smallTalkClient = new V2\SessionsClient(['credentials' => SMALL_TALK_API_KEY]);
        }

        return $this->smallTalkClient;
    }

    protected function getInstanceSmallTalkSession() : string
    {
        if ($this->smallTalkSession === null) {
            $this->smallTalkSession = uniqid();
        }

        return $this->smallTalkSession;
    }
}
