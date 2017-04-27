<?php

use Drupal\Driver\DrupalDriver;
use Drupal\Driver\Cores\Drupal8;

require 'vendor/autoload.php';

// Path to Drupal.
$path = './drupal-8';

// Host.
$uri = 'http://d8.devl';

$driver = new DrupalDriver($path, $uri);
$driver->setCoreFromVersion();

// Bootstrap Drupal.
$driver->bootstrap();

// Create a node.
$node = (object) array(
  'type' => 'article',
  'uid' => 1,
  'title' => $driver->getRandom()->name(),
);
$driver->createNode($node);
