<?php

namespace Anet\App\YouTubeHelpers;

/**
 * **MessageSpotterTrait** -- trait for spotter message before sending by Youtube API
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 1.0
 */
trait MessageSpotterTrait
{
    /**
     * @var string[] $_searchChars chars that need be replace (ru)
     */
    private array $_searchChars = ['а', 'А', 'о', 'О', 'е', 'Е', 'р', 'Р', 'у', 'У', 'к', 'К', 'х', 'Х', 'с', 'C',];
    /**
     * @var string[] $_replaceChars chars for replace (en)
     */
    private array $_replaceChars = ['a', 'A', 'o', 'O', 'e', 'E', 'p', 'P', 'y', 'Y', 'k', 'K', 'x', 'X', 'c', 'C',];

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
