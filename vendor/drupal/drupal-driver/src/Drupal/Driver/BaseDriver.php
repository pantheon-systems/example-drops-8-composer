<?php

namespace Drupal\Driver;

use Drupal\Driver\Exception\UnsupportedDriverActionException;

/**
 * Implements DriverInterface.
 */
abstract class BaseDriver implements DriverInterface {

  /**
   * {@inheritdoc}
   */
  public function getRandom() {
    throw new UnsupportedDriverActionException($this->errorString('generate random'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function bootstrap() {
  }

  /**
   * {@inheritdoc}
   */
  public function isBootstrapped() {
  }

  /**
   * {@inheritdoc}
   */
  public function userCreate(\stdClass $user) {
    throw new UnsupportedDriverActionException($this->errorString('create users'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function userDelete(\stdClass $user) {
    throw new UnsupportedDriverActionException($this->errorString('delete users'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function processBatch() {
    throw new UnsupportedDriverActionException($this->errorString('process batch actions'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(\stdClass $user, $role) {
    throw new UnsupportedDriverActionException($this->errorString('add roles'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL) {
    throw new UnsupportedDriverActionException($this->errorString('access watchdog entries'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache($type = NULL) {
    throw new UnsupportedDriverActionException($this->errorString('clear Drupal caches'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCaches() {
    throw new UnsupportedDriverActionException($this->errorString('clear static caches'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function createNode($node) {
    throw new UnsupportedDriverActionException($this->errorString('create nodes'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function nodeDelete($node) {
    throw new UnsupportedDriverActionException($this->errorString('delete nodes'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function runCron() {
    throw new UnsupportedDriverActionException($this->errorString('run cron'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function createTerm(\stdClass $term) {
    throw new UnsupportedDriverActionException($this->errorString('create terms'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(\stdClass $term) {
    throw new UnsupportedDriverActionException($this->errorString('delete terms'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function roleCreate(array $permissions) {
    throw new UnsupportedDriverActionException($this->errorString('create roles'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function roleDelete($rid) {
    throw new UnsupportedDriverActionException($this->errorString('delete roles'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function isField($entity_type, $field_name) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function configGet($name, $key) {
    throw new UnsupportedDriverActionException($this->errorString('config get'), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function configSet($name, $key, $value) {
    throw new UnsupportedDriverActionException($this->errorString('config set'), $this);
  }

  /**
   * Error printing exception.
   *
   * @param string $error
   *   The term, node, user or permission.
   *
   * @return string
   *   A formatted string reminding people to use an API driver.
   */
  private function errorString($error) {
    return sprintf('No ability to %s in %%s. Put `@api` into your feature and add an API driver (ex: `api_driver: drupal`) in behat.yml.', $error);
  }

}
