<?php

namespace Anet\App\Contents;

use Anet\App;
use Anet\App\DB;
use PHPHtmlParser;

class Cities extends App\Contents
{
    /**
     * @var array `public` vocabulary of base pagination
     */
    public const VOCABULARY = ['А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Э', 'Ю', 'Я',];

    protected function fetchContent(array $selectorsParam) : void
    {
        if (! array_key_exists('tag', $selectorsParam)) {
            throw new \Exception('Invalid selectorsParam, need key "tag"');
        }

        try {
            $this->DomDocument->loadStr($this->pageBody);

            $writeFlag = false;

            foreach ($this->DomDocument->find($selectorsParam['tag']) as $content) {
                if ($writeFlag) {
                    $cities = explode(' ', trim($content->text));
                    $this->buffer = array_merge($this->buffer, $cities);
                    break;
                }
                if (mb_ereg_match('\(в списке \d+ наименований\)', $content->text)) {
                    $writeFlag = true;
                }
            }
        } catch (PHPHtmlParser\Exceptions\EmptyCollectionException) {
            return;
        }
    }

    public function setPagination(array $paginationParams) : ?Cities
    {
        if (! array_key_exists('prefix', $paginationParams)) {
            return null;
        }

        $this->pagination['prefix'] = $paginationParams['prefix'];
        $this->pagination['page'] = $paginationParams['selector'] ?? self::VOCABULARY;

        return $this;
    }

    public function saveToDB() : void
    {
        DB\DataBase::saveCities(array_unique($this->buffer));
    }

    /**
     * **Method** checked if city is exist in DB by name
     * @param string $city name of city
     * @return bool return true if city is exist else - false
     */
    public static function validate(string $city) : bool
    {
        return (! empty(DB\DataBase::getCityByName($city)));
    }

    /**
     * **Method** get from DB rand city by first letter
     * @param string $letter first letter of city
     * @return string name of city
     */
    public static function getRandByLetter(string $letter) : string
    {
        return DB\DataBase::getRandCityByLetter($letter);
    }
}
