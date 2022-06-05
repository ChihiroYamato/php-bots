<?php

namespace Anet\App\YouTubeHelpers;

/**
 * **ConnectParams** -- class for seting params for connect to youtube server
 * @author Mironov Alexander <aleaxan9610@gmail.com>
 * @version 1.0
 */
final class ConnectParams
{
    /**
     * @var string $appName `public` name of Google Cloud Platform application
     */
    public string $appName;
    /**
     * @var string $secretKeyJSON `public` path to secret json key for oAuth
     */
    public string $secretKeyJSON;
    /**
     * @var string $oAuthJSON `public` path to client secret oAuth token
     */
    public string $oAuthJSON;

    /**
     * Set params for connect to youtube server
     * @param string $appName name of Google Cloud Platform application
     * @param string $secretKeyJSON path to secret json key for oAuth
     * @param string $oAuthJSON path to client secret oAuth token
     * @return void
     */
    public function __construct(string $appName, string $secretKeyJSON, string $oAuthJSON)
    {
        $this->appName = $appName;
        $this->secretKeyJSON = $secretKeyJSON;
        $this->oAuthJSON = $oAuthJSON;
    }
}
