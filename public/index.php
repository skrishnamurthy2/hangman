<?php

require __DIR__ . '/../vendor/autoload.php';

use Hangman\Factory;

$factory = new Factory();

$app = $factory->getApp();
$factory->getRoutes();
$app->run();