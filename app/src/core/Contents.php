<?php

namespace Anet\App;

use GuzzleHttp;
use PHPHtmlParser;

/**
 * **Contents** -- abstract class for parse content from web
 */
abstract class Contents
{
    /**
     * @var \GuzzleHttp\Client $client `protected` instance of Guzzle client class
     */
    protected GuzzleHttp\Client $client;
    /**
     * @var \PHPHtmlParser\Dom $DomDocument `protected` instance of Dom parser class
     */
    protected PHPHtmlParser\Dom $DomDocument;
    /**
     * @var array $buffer local buffer for web content
     */
    protected array $buffer;
    /**
     * @var array $pagination list of pagination pages
     */
    protected array $pagination;
    /**
     * @var null|string $pageBody body of current page
     */
    protected ?string $pageBody;

    /**
     * **Method** fetch needed content from page body by selectors and save to buffer
     * @param array $selectorsParam list of selectors params
     * @return void
     */
    abstract protected function fetchContent(array $selectorsParam) : void;

    /**
     * **Method** set pagination list and return instance of current class
     * @param array $paginationParams list of pagination params
     * @return null|\Anet\App\Contents instance of current class
     */
    abstract public function setPagination(array $paginationParams) : ?Contents;

    /**
     * **Method** save content from buffer to DB
     * @return void
     */
    abstract public function saveToDB() : void;

    /**
     * Initialize parser
     * @param string $baseUrl url to resurce for Guzzle client
     * @return void
     */
    public function __construct(string $baseUrl)
    {
        $this->client = new GuzzleHttp\Client(['base_uri' => $baseUrl]);
        $this->DomDocument = new PHPHtmlParser\Dom();
        $this->buffer = [];
        $this->pagination = [];
        $this->pageBody = null;
    }

    /**
     * **Method** parse content from all pages by paginations list
     * @param string $url url to resurce
     * @param array $selectorsParam list of selectors params
     * @return \Anet\App\Contents instance of current class
     */
    public function parseContentByPagination(string $url, array $selectorsParam) : Contents
    {
        if (! (array_key_exists('page', $this->pagination) && array_key_exists('prefix', $this->pagination))) {
            throw new \Exception('pagination params is\'t set');
        }

        foreach ($this->pagination['page'] as $page) {
            $this->parsePageBody("$url{$this->pagination['prefix']}$page")?->fetchContent($selectorsParam);
        }

        return $this;
    }

    /**
     * **Method** parse body from url and save to string
     * @param string $url url to resurce
     * @return null|\Anet\App\Contents instance of current class
     */
    public function parsePageBody(string $url) : ?Contents
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
