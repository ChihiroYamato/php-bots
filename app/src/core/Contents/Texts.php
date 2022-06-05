<?php

namespace Anet\App\Contents;

use Anet\App;
use Anet\App\DB;
use PHPHtmlParser;

/**
 * **Texts** -- class realization of parsing content from web
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 1.0
 */
abstract class Texts extends App\Contents
{
    /**
     * @var string name of text category in DB
     */
    protected const CATEGORY_NAME = '%';
    /**
     * @var string emergency message
     */
    protected const WARNING_MESSAGE = 'Сорри, что-то пошло не так';

    public function saveToDB() : void
    {
        if (empty($this->buffer)) {
            return;
        }

        DB\DataBase::saveTextByCategory(static::CATEGORY_NAME, $this->buffer);
        $this->buffer = [];
        print_r("Save to DataBase - success\n");
    }

    protected function fetchContent(array $selectorsParam) : void
    {
        if (! array_key_exists('content', $selectorsParam)) {
            throw new \Exception('selector params is\'t set');
        }

        try {
            $this->DomDocument->loadStr($this->pageBody);

            foreach ($this->DomDocument->find($selectorsParam['content']) as $content) {
                if (mb_strlen($content->text) < 300 && mb_stripos($content->text, '♦') === false) {
                    $this->buffer[] = $content->text;
                }
            }
        } catch (PHPHtmlParser\Exceptions\EmptyCollectionException) {
            return;
        }
    }

    public function setPagination(array $paginationParams) : ?Jokes
    {
        if (! (array_key_exists('selector', $paginationParams) && array_key_exists('prefix', $paginationParams))) {
            return null;
        }

        try {
            $this->DomDocument->loadStr($this->pageBody);
            $pagination = $this->DomDocument->find($paginationParams['selector'])->text;
            preg_match('/^\d+\/(?<result>\d+)$/', trim($pagination), $matches);

            if (array_key_exists('result', $matches)) {
                $this->pagination[['page']] = range(1, (int) $matches['result']);
                $this->pagination[['prefix']] = $paginationParams['prefix'];
            }
        } catch (PHPHtmlParser\Exceptions\EmptyCollectionException) {
            return null;
        }

        return $this;
    }

    /**
     * **Method** get from DB rand text
     * @return string[] list of message
     */
    public static function fetchRand() : array
    {
        $request = DB\DataBase::fetchRandText(static::CATEGORY_NAME);

        if (empty($request)) {
            $request = static::WARNING_MESSAGE;
        }

        return (mb_strlen($request) > 190) ? static::shortenString($request) : [$request];
    }

    /**
     * **Method** divide string by lenght and return parts in array
     * @param string $string base string
     * @param int $lenght `[optional]` max lenght of string
     * @return string[] parts of base string
     */
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
