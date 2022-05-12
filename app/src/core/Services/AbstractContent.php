<?php

namespace App\Anet\Services;

use App\Anet\DB;
use GuzzleHttp;
use PHPHtmlParser;

abstract class AbstractContent
{
    protected const CATEGORY_NAME = '%';
    protected const WARNING_MESSAGE = 'Сорри, что-то пошло не так';

    private GuzzleHttp\Client $client;
    private PHPHtmlParser\Dom $DomDocument;
    private array $buffer;

    public function __construct(string $baseUrl)
    {
        $this->client = new GuzzleHttp\Client(['base_uri' => $baseUrl]);
        $this->DomDocument = new PHPHtmlParser\Dom();
        $this->buffer = [];
    }

    public function fetchBySelector(string $url, string $contentSelector, string $paginationSelector) : ?AbstractContent
    {
        try {
            $response = $this->client->request('GET' , $url);
        } catch (GuzzleHttp\Exception\ConnectException) {
            print_r('Incorrect request to URL');
            return null;
        }

        if ($response->getStatusCode() !== 200) {
            print_r('Incorrect response status code');
            return null;
        }

        $body = $response->getBody();
        $pageCount = $this->fetchPagination($body, $paginationSelector);

        if ($pageCount === null) {
            $this->fetchSinglePage($body, $contentSelector);
        } else {
            $this->fetchMultiPage($url, $contentSelector, $pageCount);
        }

        print_r("Fetch $url - success\n");

        return $this;
    }

    public function saveToDB() : void
    {
        if (empty($this->buffer)) {
            return;
        }

        DB\DataBase::saveTextByCategory(static::CATEGORY_NAME, $this->buffer);
        $this->buffer = [];
        print_r("Save to DataBase - success\n");
    }

    private function fetchMultiPage(string $url, string $contentSelector, int $pageCount) : void
    {
        try {
            for ($pageStart = 1; $pageStart <= $pageCount; $pageStart++) {
                $response = $this->client->request('GET' , "$url/$pageStart");
                $this->fetchSinglePage($response->getBody(), $contentSelector);
            }
        } catch (GuzzleHttp\Exception\ConnectException) {
            print_r('Incorrect pagination');
            return;
        }
    }

    private function fetchSinglePage(string $body, string $contentSelector) : void
    {
        try {
            $this->DomDocument->loadStr($body);

            foreach ($this->DomDocument->find($contentSelector) as $content) {
                if (mb_strlen($content->text) < 300 && mb_stripos($content->text, '♦') === false) {
                    $this->buffer[] = $content->text;
                }
            }
        } catch (PHPHtmlParser\Exceptions\EmptyCollectionException) {
            return;
        }
    }

    private function fetchPagination(string $body, string $paginationSelector) : ?int
    {
        try {
            $this->DomDocument->loadStr($body);
            $pagination = $this->DomDocument->find($paginationSelector)->text;
            preg_match('/^\d+\/(?<result>\d+)$/', trim($pagination), $matches);

            return (array_key_exists('result', $matches)) ? (int) $matches['result'] : null;
        } catch (PHPHtmlParser\Exceptions\EmptyCollectionException) {
            return null;
        }
    }

    public static function fetchRandFact() : array
    {
        $request = DB\DataBase::fetchRandText(static::CATEGORY_NAME);

        if (empty($request)) {
            $request = static::WARNING_MESSAGE;
        }

        return (mb_strlen($request) > 190) ? static::shortenString($request) : [$request];
    }

    private static function shortenString(string $string, int $lenght = 150) : array
    {
        $result = [];
        $resultString = '';
        $stringParts = explode(' ', $string);

        $sumLenght = 0;

        foreach ($stringParts as $part) {
            $partLenght = mb_strlen($part);

            if ($sumLenght + $partLenght > $lenght) {
                $result[] = trim($resultString);
                $resultString = $part;
                $sumLenght = $partLenght;
            } else {
                $resultString .= " $part";
                $sumLenght += $partLenght + 1;
            }
        }

        $result[] = trim($resultString);

        return $result;
    }
}
