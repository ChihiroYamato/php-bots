<?php

namespace Anet\Views;

final class View
{
    private static string $viewDirectory = __DIR__;

    public static function getTemplate(string $name) : void
    {
        if (file_exists(self::$viewDirectory . "/templates/$name.php")) {
            require_once self::$viewDirectory . "/templates/$name.php";
        }
    }
}
