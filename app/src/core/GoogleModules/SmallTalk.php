<?php

namespace Anet\App\GoogleModules;

use Anet\App\Helpers;
use Google\ApiCore;
use Google\Cloud\Dialogflow\V2;

/**
 * **SmallTalk** -- wrapper for Google Dialogflow module
 * https://dialogflow.cloud.google.com/
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 1.0
 */
final class SmallTalk
{
    use Helpers\ErrorTrait;

    /**
     * @var string $smallTalkSession session for connecting
     */
    private string $smallTalkSession;
    /**
     * @var \Google\Cloud\Dialogflow\V2\SessionsClient $smallTalkClient instance of Google\Cloud\Dialogflow\V2\SessionsClient
     */
    private V2\SessionsClient $smallTalkClient;

    /**
     * Session Initialization
     * @return void
     */
    public function __construct()
    {
        $this->smallTalkSession = uniqid();
        $this->smallTalkClient = new V2\SessionsClient(['credentials' => SMALL_TALK_API_KEY]);
    }

    /**
     * Method execute the request to the module with the specified message and return a response
     * @param string $message specified message for the request
     * @return string answer from dialogflow module
     */
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
            Helpers\Logger::logging('YouTube', $this->getErrors(), 'error');
            return '';
        }
    }
}
