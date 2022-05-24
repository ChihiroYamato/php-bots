<?php

namespace App\Anet\Helpers;

/**
 * **FileSystemTrait** -- contains any methods for work with file system og project
 */
trait FileSystemTrait
{
    /**
     * Method create new directory by path if it isn't exist
     * @param string $directory path to new directory
     * @return void
     * @throw `\Exception`
     */
    protected static function makeDirectory(string $directory) : void
    {
        if (! empty($directory) && ! is_dir($directory) && ! mkdir($directory, 0777, true)) {
            throw new \Exception("Can't create dir: $directory\n");
        }
    }

    /**
     * Method add postfix to name of file that means file is already worked out
     * as well as checked if new file name occupied
     * @param string $fileName absolute path to file
     * @param string $postfix postfix to file
     * @return bool return false if fileName isn't correct file name of has postfix otherwise return true
     * @throw `\Exception`
     */
    protected static function archiveFile(string $fileName, string $postfix) : bool
    {
        if (! is_file($fileName) || mb_stripos($fileName, $postfix) !== false) {
            return false;
        }

        $newFileName = self::getFreeFileName($fileName, $postfix);

        if (! rename($fileName, $newFileName)) {
            throw new \Exception("Can't rename file $fileName to $newFileName\n");
        }

        return true;
    }

    /**
     * Method get new file name that isn't occupied
     * @param string $fileName old file name
     * @param string $prefix `[optional]` prefix to new file name
     * @param string $iterationPrefix `[optional]` separator of iteration existing files
     * @return string new file name
     */
    protected static function getFreeFileName(string $fileName, string $prefix = '', string $iterationPrefix = '-') : string
    {
        preg_match('/(?<base>[^\.]+)(?<ext>\.[^\.]+)?$/', $fileName, $parseName);
        $baseName = $parseName['base'];
        $extension = $parseName['ext'] ?? '';

        $iteration = 0;

        do {
            $modPrefix = $iterationPrefix . $iteration++;
        } while (file_exists("{$baseName}{$prefix}{$modPrefix}{$extension}"));

        return "{$baseName}{$prefix}{$modPrefix}{$extension}";
    }
}
