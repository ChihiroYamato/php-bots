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

        preg_match('/(?<base>[^\.]+)(?<ext>\.[^\.]+)?$/', $fileName, $parseName);
        $baseName = $parseName['base'];
        $extension = $parseName['ext'];

        if (! rename($fileName, "$baseName-old$extension")) {
            throw new \Exception("Can't rename file $fileName to $baseName-old$extension\n");
        }

        return true;
    }
}
