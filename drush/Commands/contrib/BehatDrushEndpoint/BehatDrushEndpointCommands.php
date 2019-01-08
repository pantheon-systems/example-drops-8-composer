<?php

// In Drush 9.5.0, this may be behat_drush_endpoint instead. Prior to Drush
// 9.5.0, it is necessary to re-map the namespace in composer.json if the
// Composer project name contains `-`s.
namespace Drush\Commands\BehatDrushEndpoint;

use Drush\Commands\DrushCommands;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * A Drush commandfile.
 *
 * Contains Behat Drush commands, for use by the Behat Drush Extension.
 * These commands are specifically for Drush 9
 */
class BehatDrushEndpointCommands implements LoggerAwareInterface
{
  use LoggerAwareTrait;

  public function __construct() {
    include __DIR__ . '/behat.d8.drush.inc';
  }

  /**
   * Behat Drush endpoint. Serves as an entrypoint for Behat to make remote calls into the Drupal site being tested.
   *
   * @param $operation
   *   Behat operation, e.g. create-node.
   * @param $data
   *   Operation data in json format.
   * @usage drush behat create-node '{"title":"Example page","type":"page"}'
   *   Create a page with the title "Example page".
   *
   * @bootstrap full
   * @command behat
   */
  public function behat($operation, $data, $options = ['format' => 'json']) {
    $obj = json_decode($data);

    // Dispatch if the operation exists.
    $fn = 'drush_behat_op_' . strtr($operation, '-', '_');
    if (function_exists($fn)) {
      return $fn($obj);
    }
    else {
      throw new \Exception(dt("Operation '!op' unknown", array('!op' => $operation)));
    }
  }
}
