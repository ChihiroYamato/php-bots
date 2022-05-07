<?php

namespace App\Anet\DB;

final class DataBase
{
    private static ?\PDO $connectPDO = null;

    private static function getConnect() : \PDO
    {
        if (self::$connectPDO === null) {
            try {
                self::$connectPDO = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_BASE, DB_USER_NAME, DB_PASSWORD);
            } catch (\PDOException $error) {
                print_r($error->getMessage());
                exit;
            }
        }

        return self::$connectPDO;
    }

    public static function fetchVocabulary() : array
    {
        $result = [];
        $sqlString = 'SELECT v.content, vc.name AS category, vt.name AS type FROM vocabulary AS v JOIN vocabulary_types AS vt ON vt.id=v.type_id JOIN vocabulary_categories AS vc ON vc.id=v.category_id ORDER BY category DESC';

        try {
            $request = self::getConnect()->prepare($sqlString);
            $request->execute();

            foreach ($request as $response) {
                $result[] = $response;
            }
        } catch (\PDOException $error) {
            print_r($error->getMessage());
            exit;
        }

        return $result;
    }
}
