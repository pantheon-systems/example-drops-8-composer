<?php

use Drupal\Driver\BlackboxDriver;
use Drupal\Driver\Exception\UnsupportedDriverActionException;

...

$driver = new BlackboxDriver($alias);

try {
  // Create a node.
  $node = (object) array(
    'type' => 'article',
    'uid' => 1,
    'title' => $driver->getRandom()->name(),
  );
  $driver->createNode($node);
}
catch (UnsupportedDriverActionException $e) {
  // Mark test as skipped.
}

