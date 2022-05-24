<?php

namespace App\Anet\DB;

use App\Anet\Helpers;

// TODO ======================== Переработать класс
final class DataBase
{
    private const LOGS_CATEGORY = 'database';
    private const YOUTUBE_USERS_PROPERTIES = ['name', 'active', 'isAdmin'];
    private const YOUTUBE_USER_STATISTIC_PROPERTIES = ['last_published', 'message_count', 'social_rating', 'registation_date', 'subscriber_count', 'video_count', 'view_count', 'last_update'];

    private static ?\PDO $connectPDO = null;

    private static function getConnect() : \PDO
    {
        if (self::$connectPDO === null) {
            try {
                self::$connectPDO = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_BASE, DB_USER_NAME, DB_PASSWORD);
            } catch (\PDOException $error) {
                Helpers\Logger::logging(self::LOGS_CATEGORY, [$error->getMessage()], 'error');
                exit;
            }
        }

        return self::$connectPDO;
    }

    public static function fetchVocabulary() : array
    {
        $sqlString = 'SELECT v.content, vc.name AS category, vt.name AS type FROM vocabulary AS v JOIN vocabulary_types AS vt ON vt.id=v.type_id JOIN vocabulary_categories AS vc ON vc.id=v.category_id ORDER BY category DESC';

        try {
            $request = self::getConnect()->prepare($sqlString);
            $request->execute();

            $result = $request->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $error) {
            Helpers\Logger::logging(self::LOGS_CATEGORY, [$error->getMessage()], 'error');
            exit;
        }

        return $result;
    }

    public static function insertYoutubeUser(array $params) : void
    {
        try {
            self::getConnect()->beginTransaction();

            $request = self::getConnect()->prepare('INSERT INTO youtube_users(`key`, `name`) VALUES (?, ?)');
            $request->execute([
                $params['key'],
                $params['name']
            ]);

            $request = self::getConnect()->prepare('INSERT INTO youtube_users_statisctics(`user_key`, `social_rating`, `registation_date`, `subscriber_count`, `video_count`, `view_count`) VALUES (?, ?, ?, ?, ?, ?)');
            $request->execute([
                $params['key'],
                $params['social_rating'],
                $params['registation_date'],
                $params['subscriber_count'],
                $params['video_count'],
                $params['view_count']
            ]);

            self::getConnect()->commit();
        }   catch (\PDOException $error) {
            self::getConnect()->rollBack();
            Helpers\Logger::logging(self::LOGS_CATEGORY, [$error->getMessage()], 'error');
        }
    }

    public static function fetchYouTubeUsers() : array
    {
        $sqlString = 'SELECT yu.key, yu.name, yu.active, yu.isAdmin, yus.last_published, yus.message_count, yus.social_rating, yus.registation_date, yus.subscriber_count, yus.video_count, yus.view_count, yus.last_update FROM youtube_users AS yu JOIN youtube_users_statisctics AS yus ON yus.user_key=yu.key';

        try {
            $request = self::getConnect()->prepare($sqlString);
            $request->execute();

            $result = $request->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $error) {
            Helpers\Logger::logging(self::LOGS_CATEGORY, [$error->getMessage()], 'error');
            exit;
        }

        return $result;
    }

    public static function updateYouTubeUser(string $id, array $newParams) : void
    {
        $queriesSQL = [
            'main' => [
                'table' => 'youtube_users',
                'prefix' => '',
                'stringSQL' => '',
                'params' => [],
            ],
            'statistic' => [
                'table' => 'youtube_users_statisctics',
                'prefix' => 'user_',
                'stringSQL' => '',
                'params' => [],
            ]
        ];

        foreach ($newParams as $prop => $item) {
            if (in_array($prop, self::YOUTUBE_USERS_PROPERTIES)) {
                $queriesSQL['main']['stringSQL'] .= " `$prop`=:$prop,";
                $queriesSQL['main']['params'][":$prop"] = $item;
            } elseif (in_array($prop, self::YOUTUBE_USER_STATISTIC_PROPERTIES)) {
                $queriesSQL['statistic']['stringSQL'] .= " `$prop`=:$prop,";
                $queriesSQL['statistic']['params'][":$prop"] = $item;
            }
        }

        try {
            self::getConnect()->beginTransaction();

            foreach ($queriesSQL as $query) {
                if ($query['stringSQL'] !== '') {
                    $query['stringSQL'] = trim($query['stringSQL'], " ,");

                    $request = self::getConnect()->prepare("UPDATE {$query['table']} SET {$query['stringSQL']} WHERE `{$query['prefix']}key`='$id'");
                    $request->execute($query['params']);
                }
            }
            self::getConnect()->commit();
        } catch (\PDOException $error) {
            self::getConnect()->rollBack();
            Helpers\Logger::logging(self::LOGS_CATEGORY, [$error->getMessage()], 'error');
        }
    }

    public static function deleteYouTubeUser(string $id) : void
    {
        try {
            self::getConnect()->beginTransaction();

            $request = self::getConnect()->prepare("DELETE FROM youtube_users WHERE `key`='$id'");
            $request->execute();

            self::getConnect()->commit();
        } catch (\PDOException $error) {
            self::getConnect()->rollBack();
            Helpers\Logger::logging(self::LOGS_CATEGORY, [$error->getMessage()], 'error');
        }
    }

    public static function fetchRandText(string $category) : string
    {
        try {
            $sqlString = "SELECT t.content FROM texts AS t JOIN text_categories AS tc ON tc.id = t.category_id WHERE tc.name LIKE '$category' ORDER BY RAND() LIMIT 1";

            $request = self::getConnect()->prepare($sqlString);
            $request->execute();

            return $request->fetch(\PDO::FETCH_ASSOC)['content'] ?? '';
        } catch (\PDOException $error) {
            Helpers\Logger::logging(self::LOGS_CATEGORY, [$error->getMessage()], 'error');
            return '';
        }
    }

    public static function saveTextByCategory(string $category, array $content) : void
    {
        try {
            self::getConnect()->beginTransaction();

            $request = self::getConnect()->prepare("SELECT `id` FROM text_categories WHERE `name` LIKE '$category' LIMIT 1");
            $request->execute();

            $id = $request->fetch(\PDO::FETCH_ASSOC)['id'];

            $request = self::getConnect()->prepare("INSERT INTO texts(`content`, `category_id`) VALUES (:content, $id)");

            foreach ($content as $item) {
                $request->execute([':content' => $item]);
            }

            self::getConnect()->commit();
        } catch (\PDOException $error) {
            self::getConnect()->rollBack();
            Helpers\Logger::logging(self::LOGS_CATEGORY, [$error->getMessage()], 'error');
        }
    }

    /**
     * @deprecated use saveByTableName
     */
    public static function saveGameStatistic(array $params) : void
    {
        try {
            $request = self::getConnect()->prepare('INSERT INTO games_statistic(`user_key`, `game`, `score`, `date`) VALUES (:user_key, :game, :score, :date)');
            $request->execute($params);
        } catch (\PDOException $error) {
            Helpers\Logger::logging(self::LOGS_CATEGORY, [$error->getMessage()], 'error');
        }
    }

    public static function saveCities(array $cities) : void
    {
        try {
            $sqlQuery = 'INSERT INTO cities(`name`) VALUES';

            foreach ($cities as $city) {
                $sqlQuery .= " (\"$city\"),";
            }

            $sqlQuery = rtrim($sqlQuery, ',');

            $request = self::getConnect()->prepare($sqlQuery);
            $request->execute();
        } catch (\PDOException $error) {
            Helpers\Logger::logging(self::LOGS_CATEGORY, [$error->getMessage()], 'error');
        }
    }

    public static function getRandCityByLetter(string $letter) : string
    {
        try {

            $request = self::getConnect()->prepare("SELECT `name` FROM `cities` WHERE `name` LIKE '$letter%' ORDER BY RAND() LIMIT 1");
            $request->execute();

            return $request->fetch(\PDO::FETCH_ASSOC)['name'] ?? '';
        } catch (\PDOException $error) {
            Helpers\Logger::logging(self::LOGS_CATEGORY, [$error->getMessage()], 'error');
            return '';
        }
    }

    public static function getCityByName(string $city) : array
    {
        try {

            $request = self::getConnect()->prepare("SELECT * FROM cities WHERE `name` LIKE :city LIMIT 1");
            $request->execute([':city' => $city]);

            $result = $request->fetch(\PDO::FETCH_ASSOC);

            if (is_array($result)) {
                return $result;
            }

            return [];
        } catch (\PDOException $error) {
            Helpers\Logger::logging(self::LOGS_CATEGORY, [$error->getMessage()], 'error');
            return [];
        }
    }

    public static function saveBotStatistic(array $data) : void
    {
        try {
            $inserts = '';
            $values = '';
            $requestData = [];

            foreach ($data as $key => $value) {
                $inserts .= "`$key`,";
                $values .= ":$key,";
                $requestData[":$key"] = $value;
            }

            $inserts = rtrim($inserts, ",");
            $values = rtrim($values, ",");

            $request = self::getConnect()->prepare("INSERT INTO bots_statisctic($inserts) VALUES ($values)");
            $request->execute($requestData);

        } catch (\PDOException $error) {
            Helpers\Logger::logging(self::LOGS_CATEGORY, [$error->getMessage()], 'error');
        }
    }

    public static function saveByTableName(string $table, array $data) : void
    {
        if (empty($data) || empty($data[0]) || ! is_array($data[0])) {
            return;
        }

        try {
            $inserts = '';
            $values = '';

            foreach ($data[0] as $key => $value) {
                $inserts .= "`$key`,";
            }

            foreach ($data as $notes) {
                $value = '';
                foreach ($notes as $item) {
                    $value .= '\'' . str_replace('\'', ' ', preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $item)) . '\',';
                }
                $value = rtrim($value, ",");
                $values .= "($value),";
            }

            $inserts = rtrim($inserts, ",");
            $values = rtrim($values, ",");

            $request = self::getConnect()->prepare("INSERT INTO $table($inserts) VALUES $values");
            $request->execute();
        } catch (\PDOException $error) {
            Helpers\Logger::logging(self::LOGS_CATEGORY, [$error->getMessage()], 'error');
        }
    }
}
