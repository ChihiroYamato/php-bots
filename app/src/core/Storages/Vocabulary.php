<?php

namespace Anet\App\Storages;

use Anet\App\DB;

/**
 * **Vocabulary** -- class for store vocabulary from DB on script runtime
 */
final class Vocabulary
{
    /**
     * @var array $storage `private` storage of vocabulary phrases
     */
    private array $storage;
    /**
     * @var array $groups `private` storage of custom grouping phrases
     */
    private array $groups;

    /**
     * Ititialize vocabulary storage
     * @return void
     */
    public function __construct()
    {
        $this->fetchVocabulary();
        $this->groups = [];
    }

    /**
     * **Method** get random phrase from storage by category and type
     * @param string $category category of phrase
     * @param string $type `[optional]` type of phrase, default *'response'*
     * @return string return random phrase from storage
     */
    public function getRandItem(string $category, string $type = 'response') : string
    {
        if (empty($this->storage[$category][$type]) || ! is_array($this->storage[$category][$type])) {
            return '';
        }

        return $this->storage[$category][$type][random_int(0, count($this->storage[$category][$type]) - 1)];
    }

    /**
     * **Method** return vocabulary storage
     * @return array vocabulary storage
     */
    public function getAll() : array
    {
        return $this->storage;
    }

    /**
     * **Method** get all categories from storage
     * @return array categories from storage
     */
    public function getCategories() : array
    {
        return array_keys($this->storage);
    }

    /**
     * **Method** get all phrases from storage by category
     * @param string $category name of phrases category
     * @return array phrases from storage by category or empty array if category name is incorrect
     */
    public function getCategory(string $category) : array
    {
        if (! array_key_exists($category, $this->storage)) {
            return [];
        }

        return $this->storage[$category];
    }

    /**
     * **Method** get all phrases from storage by category and type
     * @param string $category name of phrases category
     * @param string $type name of phrases type
     * @return array phrases from storage by params or empty array if params are incorrect
     */
    public function getCategoryType(string $category, string $type) : array
    {
        if (! (array_key_exists($category, $this->storage) && array_key_exists($type, $this->storage[$category]))) {
            return [];
        }

        return $this->storage[$category][$type];
    }

    /**
     * **Method** groped vocabulary categories and return group (only return group if it's exists)
     * @param string $group name of group
     * @param array $categories list of grouping categories
     * @return array return group of categories
     */
    public function getCategoriesGroup(string $group, array $categories) : array
    {
        if (array_key_exists($group, $this->groups)) {
            return $this->groups[$group];
        }

        foreach ($categories as $category) {
            if (array_key_exists($category, $this->storage)) {
                $this->groups[$group][$category] = $this->storage[$category];
            }
        }

        return $this->groups[$group];
    }

    /**
     * **Method** refresh exist group by name
     * @param string $group name of group
     * @return void
     */
    public function clearCategoriesGroup(string $group) : void
    {
        unset($this->groups[$group]);
    }

    /**
     * **Method** fetch vocabulary from DB and others modules and save to local storage
     * @return void
     */
    private function fetchVocabulary() : void
    {
        $response = DB\DataBase::fetchVocabulary();

        foreach ($response as $item) {
            $this->storage[$item['category']][$item['type']][] = $item['content'];
        }

        $this->storage['dead_inside']['request'] = call_user_func(function () {
            $result = [];

            for ($i = 1000; $i > 0; $i -=7) {
                $result[] = (string) $i;
            }

            return $result;
        });
    }
}
