<?php

namespace Drupal\Driver\Cores;

use Drupal\Driver\Exception\BootstrapException;

/**
 * Drupal 7 core.
 */
class Drupal7 extends AbstractCore {

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
    chdir(DRUPAL_ROOT);
    drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);
    if (empty($GLOBALS['databases'])) {
      throw new BootstrapException('Missing database setting, verify the database configuration in settings.php.');
    }
    drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache() {
    drupal_flush_all_caches();
  }

  /**
   * {@inheritdoc}
   */
  public function nodeCreate($node) {
    // Set original if not set.
    if (!isset($node->original)) {
      $node->original = clone $node;
    }

    // Assign authorship if none exists and `author` is passed.
    if (!isset($node->uid) && !empty($node->author) && ($user = user_load_by_name($node->author))) {
      $node->uid = $user->uid;
    }

    // Convert properties to expected structure.
    $this->expandEntityProperties($node);

    // Attempt to decipher any fields that may be specified.
    $this->expandEntityFields('node', $node);

    // Set defaults that haven't already been set.
    $defaults = clone $node;
    node_object_prepare($defaults);
    $node = (object) array_merge((array) $defaults, (array) $node);

    node_save($node);
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

    // Attempt to decipher any fields that may be specified.
    $this->expandEntityFields('user', $account);

    user_save($account, (array) $account);

    // Store UID.
    $user->uid = $account->uid;
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
  public function processBatch() {
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    batch_process();
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(\stdClass $user, $role_name) {
    $role = user_role_load_by_name($role_name);

    if (!$role) {
      throw new \RuntimeException(sprintf('No role "%s" exists.', $role_name));
    }

    user_multiple_role_edit(array($user->uid), 'add_role', $role->rid);
    $account = user_load($user->uid);
    $user->roles = $account->roles;

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
    $available = &drupal_static(__FUNCTION__);

    if (!isset($available) || $reset) {
      $available = array_keys(module_invoke_all('permission'));
    }

    $valid = TRUE;
    foreach ($permissions as $permission) {
      if (!in_array($permission, $available)) {
        $valid = FALSE;
      }
    }
    return $valid;
  }

  /**
   * {@inheritdoc}
   */
  public function roleCreate(array $permissions) {

    // Both machine name and permission title are allowed.
    $all_permissions = $this->getAllPermissions();

    foreach ($permissions as $key => $name) {
      if (!isset($all_permissions[$name])) {
        $search = array_search($name, $all_permissions);
        if (!$search) {
          throw new \RuntimeException(sprintf("No permission '%s' exists.", $name));
        }
        $permissions[$key] = $search;
      }
    }

    // Create new role.
    $role = new \stdClass();
    $role->name = $this->random->name(8);
    user_role_save($role);
    user_role_grant_permissions($role->rid, $permissions);

    if ($role && !empty($role->rid)) {
      return $role->name;
    }

    throw new \RuntimeException(sprintf('Failed to create a role with "" permission(s).', implode(', ', $permissions)));
  }

  /**
   * {@inheritdoc}
   */
  public function roleDelete($role_name) {
    $role = user_role_load_by_name($role_name);
    user_role_delete((int) $role->rid);
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
    $types = \node_type_get_types();
    foreach ($types as $type) {
      if ($entity->type == $type->name) {
        $entity->type = $type->type;
        continue;
      }
    }
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
      $vocabularies = \taxonomy_vocabulary_load_multiple(FALSE, array(
        'machine_name' => $term->vocabulary_machine_name,
      ));
      if (!empty($vocabularies)) {
        $vids = array_keys($vocabularies);
        $term->vid = reset($vids);
      }
    }

    // If `parent` is set, look up a term in this vocab with that name.
    if (isset($term->parent)) {
      $parent = \taxonomy_get_term_by_name($term->parent, $term->vocabulary_machine_name);
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

    \taxonomy_term_save($term);

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(\stdClass $term) {
    $status = 0;
    if (isset($term->tid)) {
      $status = \taxonomy_term_delete($term->tid);
    }
    // Will be SAVED_DELETED (3) on success.
    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function languageCreate(\stdClass $language) {
    if (!module_exists('locale')) {
      throw new \Exception(sprintf("%s::%s line %s: This driver requires the 'locale' module be enabled in order to create languages", get_class($this), __FUNCTION__, __LINE__));
    }
    include_once DRUPAL_ROOT . '/includes/iso.inc';
    include_once DRUPAL_ROOT . '/includes/locale.inc';

    // Get all predefined languages, regardless if they are enabled or not.
    $predefined_languages = _locale_get_predefined_list();

    // If the language code is not valid then throw an InvalidArgumentException.
    if (!isset($predefined_languages[$language->langcode])) {
      throw new InvalidArgumentException("There is no predefined language with langcode '{$language->langcode}'.");
    }

    // Enable a language only if it has not been enabled already.
    $enabled_languages = locale_language_list();
    if (!isset($enabled_languages[$language->langcode])) {
      locale_add_language($language->langcode);
      return $language;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function languageDelete(\stdClass $language) {
    $langcode = $language->langcode;
    // Do not remove English or the default language.
    if (!in_array($langcode, array(language_default('language'), 'en'))) {
      // @see locale_languages_delete_form_submit().
      $languages = language_list();
      if (isset($languages[$langcode])) {
        // Remove translations first.
        db_delete('locales_target')
          ->condition('language', $langcode)
          ->execute();
        cache_clear_all('locale:' . $langcode, 'cache');
        // With no translations, this removes existing JavaScript translations
        // file.
        _locale_rebuild_js($langcode);
        // Remove the language.
        db_delete('languages')
          ->condition('language', $langcode)
          ->execute();
        db_update('node')
          ->fields(array('language' => ''))
          ->condition('language', $langcode)
          ->execute();
        if ($languages[$langcode]->enabled) {
          variable_set('language_count', variable_get('language_count', 1) - 1);
        }
        module_invoke_all('multilingual_settings_changed');
        drupal_static_reset('language_list');
      }

      // Changing the language settings impacts the interface:
      cache_clear_all('*', 'cache_page', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configGet($name, $key = '') {
    throw new \Exception('Getting config is not yet implemented for Drupal 7.');
  }

  /**
   * {@inheritdoc}
   */
  public function configSet($name, $key, $value) {
    throw new \Exception('Setting config is not yet implemented for Drupal 7.');
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
  public function getEntityFieldTypes($entity_type) {
    $return = array();
    $fields = field_info_field_map();
    foreach ($fields as $field_name => $field) {
      if (array_key_exists($entity_type, $field['bundles'])) {
        $return[$field_name] = $field['type'];
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function isField($entity_type, $field_name) {
    $map = field_info_field_map();
    return !empty($map[$field_name]) && array_key_exists($entity_type, $map[$field_name]['bundles']);
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCaches() {
    drupal_static_reset();
  }

}
