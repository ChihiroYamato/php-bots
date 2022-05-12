<?php

namespace App\Anet\Helpers;

/**
 *
 *
 * @method  makeDirectory :void string $directory
 */
trait FileSystemHelperTrait
{
    /**
     *
     * @param string $directory
     * @throw SimpleException
     */
    protected static function makeDirectory(string $directory) : void
    {
        if (! empty($directory) && ! is_dir($directory) && ! mkdir($directory)) {
            throw new \Exception("Can't create dir: $directory\n");
        }
    }

    protected static function archiveFile(string $fileName) : bool
    {
        if (! is_file($fileName) || mb_stripos($fileName, '-old') !== false) {
            return false;
        }

        $newFileName = self::getFreeFileName($fileName, '-old');

        if (! rename($fileName, $newFileName)) {
            throw new \Exception("Can't rename file $fileName to $newFileName\n");
        }

        return true;
    }

    protected static function getFreeFileName(string $fileName, string $prefix = '', string $iterationPrefix = '-') : string
    {
        preg_match('/(?<base>[^\.]+)(?<ext>\.[^\.]+)?$/', $fileName, $parseName);
        $baseName = $parseName['base'];
        $extension = $parseName['ext'] ?? '';

        $iteration = 0;
        $modPrefix = '';

        while (file_exists("{$baseName}{$prefix}{$modPrefix}{$extension}")) {
            $modPrefix = $iterationPrefix . ++$iteration;
        }

        return "{$baseName}{$prefix}{$modPrefix}{$extension}";
    }
}
