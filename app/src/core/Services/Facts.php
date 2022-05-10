<?php

namespace App\Anet\Services;

use App\Anet\DB;

final class Facts
{
    private const CATEGORY_NAME = 'facts';

    public static function fetchRandFact() : string
    {
        $request = DB\DataBase::fetchRandText(self::CATEGORY_NAME);

        if (empty($request)) {
            $request = 'Сорри, что-то пошло не так, факт будет в другой раз:(';
        }

        return $request;
    }
}
