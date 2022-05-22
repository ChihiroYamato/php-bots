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

    public static function set(string $key, mixed $data) : string
    {
        do {
            $currentKey = $key . '_' . uniqid(more_entropy: true);
        } while (self::getConnect()->exists($currentKey));

        self::getConnect()->set($currentKey, json_encode($data, JSON_FORCE_OBJECT));

        return $currentKey;
    }

    public static function fetch(string $keys) : array
    {
        $keyList = self::getConnect()->keys($keys);

        if (empty($keyList)) {
            return [];
        }

        $result = self::getConnect()->mGet($keyList);
        $result = array_map(fn (string $item) => json_decode($item, true), $result);
        self::getConnect()->del($keyList);

        return $result;
    }
}
