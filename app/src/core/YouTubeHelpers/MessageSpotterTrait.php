<?php

namespace Anet\App\YouTubeHelpers;

/**
 * **MessageSpotterTrait** -- trait for spotter message before sending by Youtube API
 */
trait MessageSpotterTrait
{
    private array $_searchChars = ['а', 'о', 'е', 'р', 'у', 'к', 'х', 'с'];
    private array $_replaceChars = ['a', 'o', 'e', 'p', 'y', 'k', 'x', 'c'];

    /**
     * **Method** change letters in word for youtube censorship
     * @param string $word default word
     * @return string correct word
     */
    protected function changeChars(string $word) : string
    {
        return str_replace($this->_searchChars, $this->_replaceChars, $word);
    }
}
