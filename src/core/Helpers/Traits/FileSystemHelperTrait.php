<?php

namespace Anet\App\Helpers\Traits;

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
            throw new \Exception("Ошибка создания директории $directory");
        }
    }
}
