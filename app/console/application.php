<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Console;
use Anet\Controllers;

$application = new Console\Application('PHP Chatbots Project', '0.7');

$application->add(new Controllers\Cli());

$application->run();
