<?php

namespace App\Anet\DB;

use App\Anet\YouTubeHelpers;

/**
 * **Redis** -- simple wrapper for Redis PHP library
 * https://github.com/phpredis/phpredis
 */
final class Redis
{
    /**
     * @var null|\Redis $connect `private` static connect to Redis storage
     */
    private static ?\Redis $connect = null;

    /**
     * @var array $serializeAllowed `private` list of allowed classes for unserialize
     */
    private static array $serializeAllowed = [
        YouTubeHelpers\User::class,
    ];

    /**
     * **Method** get instance of connect to redis storage
     * @return \Redis connect to redis storage
     */
    public static function getConnect() : \Redis
    {
        if (self::$connect === null) {
            self::$connect = new \Redis();
            self::$connect->connect(REDIS_HOST, REDIS_PORT);
            self::$connect->auth(REDIS_PASS);
        }

        return self::$connect;
    }

    /**
     * **Method** set json value to redis storage by key name with adding unique id posfix to key
     * @param string $key base name of redis key
     * @param mixed $data saving data
     * @return string redis key of current value
     */
    public static function set(string $key, mixed $data) : string
    {
        do {
            $currentKey = $key . '_' . uniqid(more_entropy: true);
        } while (self::getConnect()->exists($currentKey));

        self::getConnect()->set($currentKey, json_encode($data, JSON_FORCE_OBJECT));

        return $currentKey;
    }

    /**
     * **Method** set serialized PHP object to redis storage
     * @param string $key name of redis key
     * @param object $object saving PHP object
     * @return string redis key of current value
     */
    public static function setObject(string $key, object $object) : string
    {
        self::getConnect()->set($key, serialize($object));

        return $key;
    }

    /**
     * **Method** fetch values with deleting from redis storage
     * @param string $keys key name, support mask 'name*' whitch return all suitable keys
     * @param bool $isObjects `[optional]` set true if return values are objects and it's needed to unserialize, default false
     * @return array list of values
     */
    public static function fetch(string $keys, bool $isObjects = false) : array
    {
        $keyList = self::getConnect()->keys($keys);

        if (empty($keyList)) {
            return [];
        }

        $result = self::getConnect()->mGet($keyList);
        $result = array_map(fn (string $item) => $isObjects ? unserialize($item, ['allowed_classes' => self::$serializeAllowed]) : json_decode($item, true), $result);
        self::getConnect()->del($keyList);

        return $result;
    }
}
