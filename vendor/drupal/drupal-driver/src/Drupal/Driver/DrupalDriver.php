<?php

namespace Drupal\Driver;

use Drupal\Driver\Exception\BootstrapException;

use Behat\Behat\Tester\Exception\PendingException;

/**
 * Fully bootstraps Drupal and uses native API calls.
 */
class DrupalDriver implements DriverInterface, SubDriverFinderInterface {

  /**
   * Track whether Drupal has been bootstrapped.
   *
   * @var bool
   */
  private $bootstrapped = FALSE;

  /**
   * Drupal core object.
   *
   * @var \Drupal\Driver\Cores\CoreInterface
   */
  public $core;

  /**
   * System path to the Drupal installation.
   *
   * @var string
   */
  private $drupalRoot;

  /**
   * URI for the Drupal installation.
   *
   * @var string
   */
  private $uri;

  /**
   * Drupal core version.
   *
   * @var integer
   */
  public $version;

  /**
   * Set Drupal root and URI.
   *
   * @param string $drupal_root
   *   The Drupal root path.
   * @param string $uri
   *   The URI for the Drupal installation.
   *
   * @throws BootstrapException
   *   Thrown when the Drupal installation is not found in the given root path.
   */
  public function __construct($drupal_root, $uri) {
    $this->drupalRoot = realpath($drupal_root);
    if (!$this->drupalRoot) {
      throw new BootstrapException(sprintf('No Drupal installation found at %s', $drupal_root));
    }
    $this->uri = $uri;
    $this->version = $this->getDrupalVersion();
  }

  /**
   * {@inheritdoc}
   */
  public function getRandom() {
    return $this->getCore()->getRandom();
  }

  /**
   * {@inheritdoc}
   */
  public function bootstrap() {
    $this->getCore()->bootstrap();
    $this->bootstrapped = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isBootstrapped() {
    // Assume the blackbox is always bootstrapped.
    return $this->bootstrapped;
  }

  /**
   * {@inheritdoc}
   */
  public function userCreate(\stdClass $user) {
    $this->getCore()->userCreate($user);
  }

  /**
   * {@inheritdoc}
   */
  public function userDelete(\stdClass $user) {
    $this->getCore()->userDelete($user);
  }

  /**
   * {@inheritdoc}
   */
  public function processBatch() {
    $this->getCore()->processBatch();
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(\stdClass $user, $role_name) {
    $this->getCore()->userAddRole($user, $role_name);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL) {
    throw new PendingException(sprintf('Currently no ability to access watchdog entries in %s', $this));
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache($type = NULL) {
    $this->getCore()->clearCache();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubDriverPaths() {
    // Ensure system is bootstrapped.
    if (!$this->isBootstrapped()) {
      $this->bootstrap();
    }

    return $this->getCore()->getExtensionPathList();
  }

  /**
   * Determine major Drupal version.
   *
   * @return int
   *   The major Drupal version.
   *
   * @throws \Drupal\Driver\Exception\BootstrapException
   *   Thrown when the Drupal version could not be determined.
   *
   * @see drush_drupal_version()
   */
  public function getDrupalVersion() {
    if (!isset($this->version)) {
      // Support 6, 7 and 8.
      $version_constant_paths = array(
        // Drupal 6.
        '/modules/system/system.module',
        // Drupal 7.
        '/includes/bootstrap.inc',
        // Drupal 8.
        '/autoload.php',
        '/core/includes/bootstrap.inc',
      );

      if ($this->drupalRoot === FALSE) {
        throw new BootstrapException('`drupal_root` parameter must be defined.');
      }

      foreach ($version_constant_paths as $path) {
        if (file_exists($this->drupalRoot . $path)) {
          require_once $this->drupalRoot . $path;
        }
      }
      if (defined('VERSION')) {
        $version = VERSION;
      }
      elseif (defined('\Drupal::VERSION')) {
        $version = \Drupal::VERSION;
      }
      else {
        throw new BootstrapException('Unable to determine Drupal core version. Supported versions are 6, 7, and 8.');
      }

      // Extract the major version from VERSION.
      $version_parts = explode('.', $version);
      if (is_numeric($version_parts[0])) {
        $this->version = (integer) $version_parts[0];
      }
      else {
        throw new BootstrapException(sprintf('Unable to extract major Drupal core version from version string %s.', $version));
      }
    }
    return $this->version;
  }

  /**
   * Instantiate and set Drupal core class.
   *
   * @param array $available_cores
   *   A major-version-keyed array of available core controllers.
   */
  public function setCore(array $available_cores) {
    if (!isset($available_cores[$this->version])) {
      throw new BootstrapException(sprintf('There is no available Drupal core controller for Drupal version %s.', $this->version));
    }
    $this->core = $available_cores[$this->version];
  }

  /**
   * Automatically set the core from the current version.
   */
  public function setCoreFromVersion() {
    $core = '\Drupal\Driver\Cores\Drupal' . $this->getDrupalVersion();
    $this->core = new $core($this->drupalRoot, $this->uri);
  }

  /**
   * Return current core.
   */
  public function getCore() {
    return $this->core;
  }

  /**
   * {@inheritdoc}
   */
  public function createNode($node) {
    return $this->getCore()->nodeCreate($node);
  }

  /**
   * {@inheritdoc}
   */
  public function nodeDelete($node) {
    return $this->getCore()->nodeDelete($node);
  }

  /**
   * {@inheritdoc}
   */
  public function runCron() {
    if (!$this->getCore()->runCron()) {
      throw new \Exception('Failed to run cron.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createTerm(\stdClass $term) {
    return $this->getCore()->termCreate($term);
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(\stdClass $term) {
    return $this->getCore()->termDelete($term);
  }

  /**
   * {@inheritdoc}
   */
  public function roleCreate(array $permissions) {
    return $this->getCore()->roleCreate($permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function roleDelete($rid) {
    $this->getCore()->roleDelete($rid);
  }

  /**
   * {@inheritdoc}
   */
  public function isField($entity_type, $field_name) {
    return $this->getCore()->isField($entity_type, $field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function languageCreate($language) {
    return $this->getCore()->languageCreate($language);
  }

  /**
   * {@inheritdoc}
   */
  public function languageDelete($language) {
    $this->getCore()->languageDelete($language);
  }

  /**
   * {@inheritdoc}
   */
  public function configGet($name, $key) {
    return $this->getCore()->configGet($name, $key);
  }

  /**
   * {@inheritdoc}
   */
  public function configSet($name, $key, $value) {
    $this->getCore()->configSet($name, $key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCaches() {
    $this->getCore()->clearStaticCaches();
  }

}
