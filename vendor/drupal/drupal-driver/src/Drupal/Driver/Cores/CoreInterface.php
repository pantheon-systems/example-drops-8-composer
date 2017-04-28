<?php

namespace Drupal\Driver\Cores;

use Drupal\Component\Utility\Random;

/**
 * Drupal core interface.
 */
interface CoreInterface {

  /**
   * Instantiate the core interface.
   *
   * @param string $drupal_root
   *   The path to the Drupal root folder.
   * @param string $uri
   *   URI that is accessing Drupal. Defaults to 'default'.
   * @param \Drupal\Component\Utility\Random $random
   *   Random string generator.
   */
  public function __construct($drupal_root, $uri = 'default', Random $random = NULL);

  /**
   * Return random generator.
   */
  public function getRandom();

  /**
   * Bootstrap Drupal.
   */
  public function bootstrap();

  /**
   * Get module list.
   */
  public function getModuleList();

  /**
   * Returns a list of all extension absolute paths.
   *
   * @return array
   *   An array of absolute paths to enabled extensions.
   */
  public function getExtensionPathList();

  /**
   * Clear caches.
   */
  public function clearCache();

  /**
   * Run cron.
   *
   * @return bool
   *   True if cron runs, otherwise false.
   */
  public function runCron();

  /**
   * Create a node.
   */
  public function nodeCreate($node);

  /**
   * Delete a node.
   */
  public function nodeDelete($node);

  /**
   * Create a user.
   */
  public function userCreate(\stdClass $user);

  /**
   * Delete a user.
   */
  public function userDelete(\stdClass $user);

  /**
   * Add a role to a user.
   *
   * @param \stdClass $user
   *   The Drupal user object.
   * @param string $role_name
   *   The role name.
   */
  public function userAddRole(\stdClass $user, $role_name);

  /**
   * Validate, and prepare environment for Drupal bootstrap.
   *
   * @throws \Drupal\Driver\Exception\BootstrapException
   *   Thrown when the Drupal site cannot be bootstrapped.
   *
   * @see _drush_bootstrap_drupal_site_validate()
   */
  public function validateDrupalSite();

  /**
   * Processes a batch of actions.
   */
  public function processBatch();

  /**
   * Create a taxonomy term.
   */
  public function termCreate(\stdClass $term);

  /**
   * Deletes a taxonomy term.
   */
  public function termDelete(\stdClass $term);

  /**
   * Creates a role.
   *
   * @param array $permissions
   *   An array of permissions to create the role with.
   *
   * @return int
   *   The created role name.
   */
  public function roleCreate(array $permissions);

  /**
   * Deletes a role.
   *
   * @param string $role_name
   *   A role name to delete.
   */
  public function roleDelete($role_name);

  /**
   * Get FieldHandler class.
   *
   * @param string $entity_type
   *   Entity type machine name.
   * @param string $field_name
   *   Field machine name.
   *
   * @return \Drupal\Driver\Fields\FieldHandlerInterface
   *   The field handler.
   */
  public function getFieldHandler($entity, $entity_type, $field_name);

  /**
   * Check if the specified field is an actual Drupal field.
   *
   * @param string $entity_type
   *   The entity type to check.
   * @param string $field_name
   *   The field name to check.
   *
   * @return bool
   *   TRUE if the given field is a Drupal field, FALSE otherwise.
   */
  public function isField($entity_type, $field_name);

  /**
   * Returns array of field types for the specified entity.
   *
   * @param string $entity_type
   *   The entity type for which to return the field types.
   *
   * @return array
   *   An associative array of field types, keyed by field name.
   */
  public function getEntityFieldTypes($entity_type);

  /**
   * Creates a language.
   *
   * @param \stdClass $language
   *   An object with the following properties:
   *   - langcode: the langcode of the language to create.
   */
  public function languageCreate(\stdClass $language);

  /**
   * Deletes a language.
   *
   * @param \stdClass $language
   *   An object with the following properties:
   *   - langcode: the langcode of the language to delete.
   */
  public function languageDelete(\stdClass $language);

  /**
   * Clears the static caches.
   */
  public function clearStaticCaches();

  /**
   * Returns a configuration item.
   *
   * @param string $name
   *   The name of the configuration object to retrieve.
   * @param string $key
   *   A string that maps to a key within the configuration data.
   *
   * @return mixed
   *   The data that was requested.
   */
  public function configGet($name, $key = '');

  /**
   * Sets a value in a configuration object.
   *
   * @param string $name
   *   The name of the configuration object.
   * @param string $key
   *   Identifier to store value in configuration.
   * @param mixed $value
   *   Value to associate with identifier.
   */
  public function configSet($name, $key, $value);

}
