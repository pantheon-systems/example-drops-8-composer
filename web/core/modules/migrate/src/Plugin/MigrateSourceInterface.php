<?php

namespace Drupal\migrate\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\migrate\Row;

/**
 * Defines an interface for migrate sources.
 *
 * @see \Drupal\migrate\Plugin\MigratePluginManager
 * @see \Drupal\migrate\Annotation\MigrateSource
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 * @see plugin_api
 *
 * @ingroup migration
 */
interface MigrateSourceInterface extends \Countable, \Iterator, PluginInspectionInterface {

  /**
   * Returns available fields on the source.
   *
   * @return array
   *   Available fields in the source, keys are the field machine names as used
   *   in field mappings, values are descriptions.
   */
  public function fields();

  /**
   * Adds additional data to the row.
   *
   * @param \Drupal\Migrate\Row $row
   *   The row object.
   *
   * @return bool
   *   FALSE if this row needs to be skipped.
   */
  public function prepareRow(Row $row);

  /**
   * Allows class to decide how it will react when it is treated like a string.
   */
  public function __toString();

  /**
   * Defines the source fields uniquely identifying a source row.
   *
   * None of these fields should contain a NULL value. If necessary, use
   * prepareRow() or hook_migrate_prepare_row() to rewrite NULL values to
   * appropriate empty values (such as '' or 0).
   *
   * @return array[]
   *   An associative array of field definitions keyed by field ID. Values are
   *   associative arrays with a structure that contains the field type ('type'
   *   key). The other keys are the field storage settings as they are returned
   *   by FieldStorageDefinitionInterface::getSettings(). As an example, for a
   *   composite source primary key that is defined by an integer and a
   *   string, the returned value might look like:
   *   @code
   *     return [
   *       'id' => [
   *         'type' => 'integer',
   *         'unsigned' => FALSE,
   *         'size' => 'big',
   *       ],
   *       'version' => [
   *         'type' => 'string',
   *         'max_length' => 64,
   *         'is_ascii' => TRUE,
   *       ],
   *     ];
   *   @endcode
   *   If 'type' points to a field plugin with multiple columns and needs to
   *   refer to a column different than 'value', the key of that column will be
   *   appended as a suffix to the plugin name, separated by dot ('.'). Example:
   *   @code
   *     return [
   *       'format' => [
   *         'type' => 'text.format',
   *       ],
   *     ];
   *   @endcode
   *   Additional custom keys/values, that are not part of field storage
   *   definition, can be passed in definitions. The most common setting, passed
   *   along the ID definition, is 'alias' used by SqlBase source plugin:
   *   @code
   *     return [
   *       'nid' => [
   *         'type' => 'integer',
   *         'alias' => 'n',
   *       ],
   *     ];
   *   @endcode
   *
   * @see \Drupal\Core\Field\FieldStorageDefinitionInterface::getSettings()
   * @see \Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem
   * @see \Drupal\Core\Field\Plugin\Field\FieldType\StringItem
   * @see \Drupal\text\Plugin\Field\FieldType\TextItem
   * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
   */
  public function getIds();

}
