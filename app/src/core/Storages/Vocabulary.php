<?php

namespace App\Anet\Storages;

use App\Anet\DB;

final class Vocabulary
{
    private array $storage;
    private array $groups;

    public function __construct()
    {
        $this->fetchVocabulary();
        $this->groups = [];
    }

    public function getRandItem(string $category, string $type = 'response') : string
    {
        if (empty($this->storage[$category][$type]) || ! is_array($this->storage[$category][$type])) {
            return '';
        }

        return $this->storage[$category][$type][random_int(0, count($this->storage[$category][$type]) - 1)];
    }

    public function getAll() : array
    {
        return $this->storage;
    }

    public function getCategories() : array
    {
        return array_keys($this->storage);
    }

    public function getCategory(string $category) : array
    {
        if (! array_key_exists($category, $this->storage)) {
            return [];
        }

        return $this->storage[$category];
    }

    public function getCategoryType(string $category, string $type) : array
    {
        if (! (array_key_exists($category, $this->storage) && array_key_exists($type, $this->storage[$category]))) {
            return [];
        }

        return $this->storage[$category][$type];
    }

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

    public function clearCategoriesGroup(string $group) : void
    {
        unset($this->groups[$group]);
    }

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
