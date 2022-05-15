<?php

namespace App\Anet\Services;

use GuzzleHttp;
use PHPHtmlParser;

abstract class AbstractContent
{
    protected GuzzleHttp\Client $client;
    protected PHPHtmlParser\Dom $DomDocument;
    protected array $buffer;
    protected array $pagination;
    protected ?string $pageBody;

    abstract protected function fetchContent(array $selectorsParam) : void;

    abstract public function setPagination(array $paginationParams) : ?AbstractContent;

    abstract public function saveToDB() : void;

    public function __construct(string $baseUrl)
    {
        $this->client = new GuzzleHttp\Client(['base_uri' => $baseUrl]);
        $this->DomDocument = new PHPHtmlParser\Dom();
        $this->buffer = [];
        $this->pagination = [];
        $this->pageBody = null;
    }

    public function parseContentByPagination(string $url, array $selectorsParam) : AbstractContent
    {
        if (! (array_key_exists('page', $this->pagination) && array_key_exists('prefix', $this->pagination))) {
            throw new \Exception('pagination params is\'t set');
        }

        foreach ($this->pagination['page'] as $page) {
            $this->parsePageBody("$url{$this->pagination['prefix']}$page")?->fetchContent($selectorsParam);
        }

        return $this;
    }

    public function parsePageBody(string $url) : ?AbstractContent
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

        $this->pageBody = $response->getBody();

        return $this;
    }
}
