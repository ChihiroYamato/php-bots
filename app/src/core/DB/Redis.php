<?php

namespace App\Anet\DB;

final class Redis
{
    private static ?\Redis $connect = null;

    public static function getConnect() : \Redis
    {
        if (self::$connect === null) {
            self::$connect = new \Redis();
            self::$connect->connect(REDIS_HOST, REDIS_PORT);
            self::$connect->auth(REDIS_PASS);
        }

        return self::$connect;
    }
}
