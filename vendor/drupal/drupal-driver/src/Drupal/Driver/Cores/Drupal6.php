<?php

namespace Drupal\Driver\Cores;

use Drupal\Driver\Exception\BootstrapException;

/**
 * Drupal 6 core.
 */
class Drupal6 extends AbstractCore {

  /**
   * The available permissions.
   *
   * @var array
   */
  protected $availablePermissons;

  /**
   * {@inheritdoc}
   */
  public function bootstrap() {
    // Validate, and prepare environment for Drupal bootstrap.
    if (!defined('DRUPAL_ROOT')) {
      define('DRUPAL_ROOT', $this->drupalRoot);
      require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
      $this->validateDrupalSite();
    }

    // Bootstrap Drupal.
    $current_path = getcwd();
    chdir(DRUPAL_ROOT);
    drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);
    if (empty($GLOBALS['db_url'])) {
      throw new BootstrapException('Missing database setting, verify the database configuration in settings.php.');
    }
    drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
    chdir($current_path);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache() {
    // Need to change into the Drupal root directory or the registry explodes.
    $current_path = getcwd();
    chdir(DRUPAL_ROOT);
    drupal_flush_all_caches();
    chdir($current_path);
  }

  /**
   * {@inheritdoc}
   */
  public function nodeCreate($node) {
    $current_path = getcwd();
    chdir(DRUPAL_ROOT);

    // Set original if not set.
    if (!isset($node->original)) {
      $node->original = clone $node;
    }

    // Assign authorship if none exists and `author` is passed.
    if (!isset($node->uid) && !empty($node->author) && ($user = user_load(array('name' => $node->author)))) {
      $node->uid = $user->uid;
    }

    // Convert properties to expected structure.
    $this->expandEntityProperties($node);

    // Attempt to decipher any fields that may be specified.
    $this->expandEntityFields('node', $node);

    // Set defaults that haven't already been set.
    $defaults = clone $node;
    module_load_include('inc', 'node', 'node.pages');
    node_object_prepare($defaults);
    $node = (object) array_merge((array) $defaults, (array) $node);

    node_save($node);

    chdir($current_path);
    return $node;

  }

  /**
   * {@inheritdoc}
   */
  public function nodeDelete($node) {
    node_delete($node->nid);
  }

  /**
   * Implements CoreInterface::runCron().
   */
  public function runCron() {
    return drupal_cron_run();
  }

  /**
   * {@inheritdoc}
   */
  public function userCreate(\stdClass $user) {
    // Default status to TRUE if not explicitly creating a blocked user.
    if (!isset($user->status)) {
      $user->status = 1;
    }

    // Clone user object, otherwise user_save() changes the password to the
    // hashed password.
    $account = clone $user;
    // Convert role array to a keyed array.
    if (isset($user->roles)) {
      $roles = array();
      foreach ($user->roles as $rid) {
        $roles[$rid] = $rid;
      }
      $user->roles = $roles;
    }
    $account = user_save((array) $account, (array) $account);
    // Store the UID.
    $user->uid = $account->uid;
    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function userDelete(\stdClass $user) {
    $current_path = getcwd();
    chdir(DRUPAL_ROOT);
    user_delete((array) $user, $user->uid);
    chdir($current_path);
  }

  /**
   * {@inheritdoc}
   */
  public function processBatch() {
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(\stdClass $user, $role_name) {
    $roles = array_flip(user_roles());
    $role = $roles[$role_name];
    if (!$role) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $role_name));
    }
    user_multiple_role_edit(array($user->uid), 'add_role', $role);
  }

  /**
   * Fetches a user role by role name.
   *
   * @param string $role_name
   *   A string representing the role name.
   *
   * @return object
   *   A fully-loaded role object if a role with the given name exists, or FALSE
   *   otherwise.
   *
   * @see user_role_load()
   */
  protected function userRoleLoadByName($role_name) {
    $result = db_query('SELECT * FROM {role} WHERE name = "%s"', $role_name);
    return db_fetch_object($result);
  }

  /**
   * Check to make sure that the array of permissions are valid.
   *
   * @param array $permissions
   *   Permissions to check.
   * @param bool $reset
   *   Reset cached available permissions.
   *
   * @return bool
   *   TRUE or FALSE depending on whether the permissions are valid.
   */
  protected function checkPermissions(array $permissions, $reset = FALSE) {

    if (!isset($this->availablePermissons) || $reset) {
      $this->availablePermissons = array_keys(module_invoke_all('permission'));
    }

    $valid = TRUE;
    foreach ($permissions as $permission) {
      if (!in_array($permission, $this->availablePermissons)) {
        $valid = FALSE;
      }
    }
    return $valid;
  }

  /**
   * {@inheritdoc}
   */
  public function roleCreate(array $permissions) {
    // Verify permissions exist.
    $all_permissions = module_invoke_all('perm');
    foreach ($permissions as $name) {
      $search = array_search($name, $all_permissions);
      if (!$search) {
        throw new \RuntimeException(sprintf("No permission '%s' exists.", $name));
      }
    }
    // Create new role.
    $name = $this->random->name(8);
    db_query("INSERT INTO {role} SET name = '%s'", $name);
    // Add permissions to role.
    $rid = db_last_insert_id('role', 'rid');
    db_query("INSERT INTO {permission} (rid, perm) VALUES (%d, '%s')", $rid, implode(', ', $permissions));
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function roleDelete($role_name) {
    $roles = array_flip(user_roles());
    $rid = $roles[$role_name];
    db_query('DELETE FROM {role} WHERE rid = %d', $rid);
    if (!db_affected_rows()) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $rid));
    }
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

    $conf_path = conf_path(TRUE, TRUE);
    $conf_file = $this->drupalRoot . "/$conf_path/settings.php";
    if (!file_exists($conf_file)) {
      throw new BootstrapException(sprintf('Could not find a Drupal settings.php file at "%s"', $conf_file));
    }
    $drushrc_file = $this->drupalRoot . "/$conf_path/drushrc.php";
    if (file_exists($drushrc_file)) {
      require_once $drushrc_file;
    }
  }

  /**
   * Expands properties on the given entity object to the expected structure.
   *
   * @param \stdClass $entity
   *   The entity object.
   */
  protected function expandEntityProperties(\stdClass $entity) {
    // The created field may come in as a readable date, rather than a
    // timestamp.
    if (isset($entity->created) && !is_numeric($entity->created)) {
      $entity->created = strtotime($entity->created);
    }

    // Map human-readable node types to machine node types.
    $types = node_get_types();
    foreach ($types as $type) {
      if ($entity->type == $type->name) {
        $entity->type = $type->type;
        continue;
      }
    }
  }

  /**
   * Load vocabularies, optional by VIDs.
   *
   * @param array $vids
   *   The vids to load.
   *
   * @return array
   *   An array of vocabulary objects
   */
  protected function taxonomyVocabularyLoadMultiple($vids = array()) {
    $vocabularies = taxonomy_get_vocabularies();
    if ($vids) {
      return array_intersect_key($vocabularies, array_flip($vids));
    }
    return $vocabularies;
  }

  /**
   * {@inheritdoc}
   */
  public function termCreate(\stdClass $term) {
    // Map vocabulary names to vid, these take precedence over machine names.
    if (!isset($term->vid)) {
      $vocabularies = \taxonomy_get_vocabularies();
      foreach ($vocabularies as $vid => $vocabulary) {
        if ($vocabulary->name == $term->vocabulary_machine_name) {
          $term->vid = $vocabulary->vid;
        }
      }
    }

    if (!isset($term->vid)) {

      // Try to load vocabulary by machine name.
      $vocabularies = $this->taxonomyVocabularyLoadMultiple(array($term->vid));
      if (!empty($vocabularies)) {
        $vids = array_keys($vocabularies);
        $term->vid = reset($vids);
      }
    }

    // If `parent` is set, look up a term in this vocab with that name.
    if (isset($term->parent)) {
      $parent = \taxonomy_get_term_by_name($term->parent);
      if (!empty($parent)) {
        $parent = reset($parent);
        $term->parent = $parent->tid;
      }
    }

    if (empty($term->vid)) {
      throw new \Exception(sprintf('No "%s" vocabulary found.'));
    }

    // Attempt to decipher any fields that may be specified.
    $this->expandEntityFields('taxonomy_term', $term);

    // Protect against a failure from hook_taxonomy_term_insert() in pathauto.
    $current_path = getcwd();
    chdir(DRUPAL_ROOT);
    $term_array = (array) $term;
    \taxonomy_save_term($term_array);
    chdir($current_path);

    // Loading a term by name returns an array of term objects, but there should
    // only be one matching term in a testing context, so take the first match
    // by reset()'ing $matches.
    $matches = \taxonomy_get_term_by_name($term->name);
    $saved_term = reset($matches);

    return $saved_term;
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(\stdClass $term) {
    $status = 0;
    if (isset($term->tid)) {
      $status = \taxonomy_del_term($term->tid);
    }
    // Will be SAVED_DELETED (3) on success.
    return $status;
  }

  /**
   * Helper function to get all permissions.
   *
   * @return array
   *   Array keyed by permission name, with the human-readable title as the
   *   value.
   */
  protected function getAllPermissions() {
    $permissions = array();
    foreach (module_invoke_all('permission') as $name => $permission) {
      $permissions[$name] = $permission['title'];
    }
    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleList() {
    return module_list();
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionPathList() {
    $paths = array();

    // Get enabled modules.
    $modules = $this->getModuleList();
    foreach ($modules as $module) {
      $paths[] = $this->drupalRoot . DIRECTORY_SEPARATOR . \drupal_get_path('module', $module);
    }

    return $paths;
  }

  /**
   * {@inheritdoc}
   */
  protected function expandEntityFields($entity_type, \stdClass $entity) {
    return parent::expandEntityFields($entity_type, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFieldTypes($entity_type) {
    $taxonomy_fields = array('taxonomy' => 'taxonomy');
    if (!module_exists('content')) {
      return $taxonomy_fields;
    }
    $return = array();
    $fields = content_fields();
    foreach ($fields as $field_name => $field) {
      if ($this->isField($entity_type, $field_name)) {
        $return[$field_name] = $field['type'];
      }
    }

    $return += $taxonomy_fields;

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function isField($entity_type, $field_name) {
    if ($field_name === 'taxonomy') {
      return TRUE;
    }
    if (!module_exists('content')) {
      return FALSE;
    }
    $map = content_fields();
    return isset($map[$field_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function languageCreate(\stdClass $language) {
    throw new \Exception('Creating languages is not yet implemented for Drupal 6.');
  }

  /**
   * {@inheritdoc}
   */
  public function languageDelete(\stdClass $language) {
    throw new \Exception('Deleting languages is not yet implemented for Drupal 6.');
  }

  /**
   * {@inheritdoc}
   */
  public function configGet($name, $key = '') {
    throw new \Exception('Getting config is not yet implemented for Drupal 6.');
  }

  /**
   * {@inheritdoc}
   */
  public function configSet($name, $key, $value) {
    throw new \Exception('Setting config is not yet implemented for Drupal 6.');
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCaches() {
    // Drupal 6 doesn't have a way of clearing all static caches.
  }

}
