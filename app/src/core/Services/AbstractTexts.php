<?php

namespace App\Anet\Services;

use App\Anet\DB;
use GuzzleHttp;
use PHPHtmlParser;

abstract class AbstractTexts extends AbstractContent
{
    protected const CATEGORY_NAME = '%';
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

    public function setPagination(array $paginationParams) : Jokes
    {
        try {
            $this->DomDocument->loadStr($this->pageBody);
            $pagination = $this->DomDocument->find($paginationParams['selector'])->text;
            preg_match('/^\d+\/(?<result>\d+)$/', trim($pagination), $matches);

            if (array_key_exists('result', $matches)) {
                $this->pagination[['page']] = range(1, (int) $matches['result']);
                $this->pagination[['prifix']] = $paginationParams['prefix'];
            }
        } catch (PHPHtmlParser\Exceptions\EmptyCollectionException) {
            return null;
        }

        return $this;
    }

    public static function fetchRand() : array
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
