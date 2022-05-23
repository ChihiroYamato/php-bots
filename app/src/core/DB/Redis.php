<?php

namespace App\Anet\DB;

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
     * @return string
     */
    public static function setObject(string $key, object $object) : string
    {
        self::getConnect()->set($key, serialize($object));

        return $key;
    }

    /**
     * **Method** fetch values with deleting from redis storage
     * @param string $keys key name, support mask 'name*' whitch return all suitable keys
     * @return array list of values
     */
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

    /**
     * **Method** fetch php object with deleting from redis storage
     * @param string $key key of object in storage
     * @return null|object if object exist - return object, else - null
     */
    public static function fetchObject(string $key) : ?object
    {
        if (! self::getConnect()->exists($key)) {
            return null;
        }

        $object = unserialize(self::getConnect()->get($key));
        self::getConnect()->del($key);

        return is_object($object) ? $object : null;
    }
}
