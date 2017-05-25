<?php

namespace Drupal\Driver\Cores;

use Drupal\Core\DrupalKernel;
use Drupal\Driver\Exception\BootstrapException;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Drupal 8 core.
 */
class Drupal8 extends AbstractCore {

  /**
   * {@inheritdoc}
   */
  public function bootstrap() {
    // Validate, and prepare environment for Drupal bootstrap.
    if (!defined('DRUPAL_ROOT')) {
      define('DRUPAL_ROOT', $this->drupalRoot);
    }

    // Bootstrap Drupal.
    chdir(DRUPAL_ROOT);
    $autoloader = require DRUPAL_ROOT . '/autoload.php';
    require_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';
    $this->validateDrupalSite();

    $request = Request::createFromGlobals();
    $kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
    $kernel->boot();
    $kernel->prepareLegacyRequest($request);

    // Initialise an anonymous session. required for the bootstrap.
    \Drupal::service('session_manager')->start();
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache() {
    // Need to change into the Drupal root directory or the registry explodes.
    drupal_flush_all_caches();
  }

  /**
   * {@inheritdoc}
   */
  public function nodeCreate($node) {
    // Throw an exception if the node type is missing or does not exist.
    if (!isset($node->type) || !$node->type) {
      throw new \Exception("Cannot create content because it is missing the required property 'type'.");
    }
    $bundles = \Drupal::entityManager()->getBundleInfo('node');
    if (!in_array($node->type, array_keys($bundles))) {
      throw new \Exception("Cannot create content because provided content type '$node->type' does not exist.");
    }
    // Default status to 1 if not set.
    if (!isset($node->status)) {
      $node->status = 1;
    }
    // If 'author' is set, remap it to 'uid'.
    if (isset($node->author)) {
      $user = user_load_by_name($node->author);
      if ($user) {
        $node->uid = $user->id();
      }
    }
    $this->expandEntityFields('node', $node);
    $entity = entity_create('node', (array) $node);
    $entity->save();

    $node->nid = $entity->id();

    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function nodeDelete($node) {
    $node = $node instanceof NodeInterface ? $node : Node::load($node->nid);
    if ($node instanceof NodeInterface) {
      $node->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function runCron() {
    return \Drupal::service('cron')->run();
  }

  /**
   * {@inheritdoc}
   */
  public function userCreate(\stdClass $user) {
    $this->validateDrupalSite();

    // Default status to TRUE if not explicitly creating a blocked user.
    if (!isset($user->status)) {
      $user->status = 1;
    }

    // Clone user object, otherwise user_save() changes the password to the
    // hashed password.
    $this->expandEntityFields('user', $user);
    $account = entity_create('user', (array) $user);
    $account->save();

    // Store UID.
    $user->uid = $account->id();
  }

  /**
   * {@inheritdoc}
   */
  public function roleCreate(array $permissions) {
    // Generate a random, lowercase machine name.
    $rid = strtolower($this->random->name(8, TRUE));

    // Generate a random label.
    $name = trim($this->random->name(8, TRUE));

    // Convert labels to machine names.
    $this->convertPermissions($permissions);

    // Check the all the permissions strings are valid.
    $this->checkPermissions($permissions);

    // Create new role.
    $role = entity_create('user_role', array(
      'id' => $rid,
      'label' => $name,
    ));
    $result = $role->save();

    if ($result === SAVED_NEW) {
      // Grant the specified permissions to the role, if any.
      if (!empty($permissions)) {
        user_role_grant_permissions($role->id(), $permissions);
      }
      return $role->id();
    }

    throw new \RuntimeException(sprintf('Failed to create a role with "%s" permission(s).', implode(', ', $permissions)));
  }

  /**
   * {@inheritdoc}
   */
  public function roleDelete($role_name) {
    $role = user_role_load($role_name);

    if (!$role) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $role_name));
    }

    $role->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function processBatch() {
    $this->validateDrupalSite();
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    batch_process();
  }

  /**
   * Retrieve all permissions.
   *
   * @return array
   *   Array of all defined permissions.
   */
  protected function getAllPermissions() {
    $permissions = &drupal_static(__FUNCTION__);

    if (!isset($permissions)) {
      $permissions = \Drupal::service('user.permissions')->getPermissions();
    }

    return $permissions;
  }

  /**
   * Convert any permission labels to machine name.
   *
   * @param array &$permissions
   *   Array of permission names.
   */
  protected function convertPermissions(array &$permissions) {
    $all_permissions = $this->getAllPermissions();

    foreach ($all_permissions as $name => $definition) {
      $key = array_search($definition['title'], $permissions);
      if (FALSE !== $key) {
        $permissions[$key] = $name;
      }
    }
  }

  /**
   * Check to make sure that the array of permissions are valid.
   *
   * @param array $permissions
   *   Permissions to check.
   */
  protected function checkPermissions(array &$permissions) {
    $available = array_keys($this->getAllPermissions());

    foreach ($permissions as $permission) {
      if (!in_array($permission, $available)) {
        throw new \RuntimeException(sprintf('Invalid permission "%s".', $permission));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function userDelete(\stdClass $user) {
    user_cancel(array(), $user->uid, 'user_cancel_delete');
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(\stdClass $user, $role_name) {
    // Allow both machine and human role names.
    $roles = user_role_names();
    $id = array_search($role_name, $roles);
    if (FALSE !== $id) {
      $role_name = $id;
    }

    if (!$role = user_role_load($role_name)) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $role_name));
    }

    $account = \user_load($user->uid);
    $account->addRole($role->id());
    $account->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrupalSite() {
    if ('default' !== $this->uri) {
      // Fake the necessary HTTP headers that Drupal needs:
      $drupal_base_url = parse_url($this->uri);
      // If there's no url scheme set, add http:// and re-parse the url
      // so the host and path values are set accurately.
      if (!array_key_exists('scheme', $drupal_base_url)) {
        $drupal_base_url = parse_url($this->uri);
      }
      // Fill in defaults.
      $drupal_base_url += array(
        'path' => NULL,
        'host' => NULL,
        'port' => NULL,
      );
      $_SERVER['HTTP_HOST'] = $drupal_base_url['host'];

      if ($drupal_base_url['port']) {
        $_SERVER['HTTP_HOST'] .= ':' . $drupal_base_url['port'];
      }
      $_SERVER['SERVER_PORT'] = $drupal_base_url['port'];

      if (array_key_exists('path', $drupal_base_url)) {
        $_SERVER['PHP_SELF'] = $drupal_base_url['path'] . '/index.php';
      }
      else {
        $_SERVER['PHP_SELF'] = '/index.php';
      }
    }
    else {
      $_SERVER['HTTP_HOST'] = 'default';
      $_SERVER['PHP_SELF'] = '/index.php';
    }

    $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'];
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['REQUEST_METHOD']  = NULL;

    $_SERVER['SERVER_SOFTWARE'] = NULL;
    $_SERVER['HTTP_USER_AGENT'] = NULL;

    $conf_path = DrupalKernel::findSitePath(Request::createFromGlobals());
    $conf_file = $this->drupalRoot . "/$conf_path/settings.php";
    if (!file_exists($conf_file)) {
      throw new BootstrapException(sprintf('Could not find a Drupal settings.php file at "%s"', $conf_file));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function termCreate(\stdClass $term) {
    $term->vid = $term->vocabulary_machine_name;
    $this->expandEntityFields('taxonomy_term', $term);
    $entity = Term::create((array) $term);
    $entity->save();

    $term->tid = $entity->id();
    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(\stdClass $term) {
    $term = $term instanceof TermInterface ? $term : Term::load($term->tid);
    if ($term instanceof TermInterface) {
      $term->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleList() {
    return array_keys(\Drupal::moduleHandler()->getModuleList());
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionPathList() {
    $paths = array();

    // Get enabled modules.
    foreach (\Drupal::moduleHandler()->getModuleList() as $module) {
      $paths[] = $this->drupalRoot . DIRECTORY_SEPARATOR . $module->getPath();
    }

    return $paths;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFieldTypes($entity_type) {
    $return = array();
    $fields = \Drupal::entityManager()->getFieldStorageDefinitions($entity_type);
    foreach ($fields as $field_name => $field) {
      if ($this->isField($entity_type, $field_name)) {
        $return[$field_name] = $field->getType();
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function isField($entity_type, $field_name) {
    $fields = \Drupal::entityManager()->getFieldStorageDefinitions($entity_type);
    return (isset($fields[$field_name]) && $fields[$field_name] instanceof FieldStorageConfig);
  }

  /**
   * {@inheritdoc}
   */
  public function languageCreate(\stdClass $language) {
    $langcode = $language->langcode;

    // Enable a language only if it has not been enabled already.
    if (!ConfigurableLanguage::load($langcode)) {
      $created_language = ConfigurableLanguage::createFromLangcode($language->langcode);
      if (!$created_language) {
        throw new InvalidArgumentException("There is no predefined language with langcode '{$langcode}'.");
      }
      $created_language->save();
      return $language;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function languageDelete(\stdClass $language) {
    $configurable_language = ConfigurableLanguage::load($language->langcode);
    $configurable_language->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCaches() {
    drupal_static_reset();
    \Drupal::service('cache_tags.invalidator')->resetChecksums();
  }

  /**
   * {@inheritdoc}
   */
  public function configGet($name, $key = '') {
    return \Drupal::config($name)->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function configSet($name, $key, $value) {
    \Drupal::configFactory()->getEditable($name)
      ->set($key, $value)
      ->save();
  }

}
