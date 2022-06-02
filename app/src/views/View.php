<?php

namespace Anet\Views;

/**
 * **View** -- handler class for views
 */
final class View
{
    /**
     * @var string $viewDirectory path of View class directory
     */
    private static string $viewDirectory = __DIR__;

    /**
     * **Method** require template by name
     * @param string $name template name
     * @return void
     */
    public static function getTemplate(string $name) : void
    {
        if (file_exists(self::$viewDirectory . "/templates/$name.php")) {
            require_once self::$viewDirectory . "/templates/$name.php";
        }
    }
}
