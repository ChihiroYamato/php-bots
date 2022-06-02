<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Anet\Controllers;
use Symfony\Component\Console\Application;

$application = new Application('PHP Chatbots Project', '0.7');

$application->add(new Controllers\Cli());

$application->run();
