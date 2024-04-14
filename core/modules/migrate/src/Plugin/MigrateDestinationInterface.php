<?php

namespace Drupal\migrate\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\migrate\Row;

/**
 * Defines an interface for Migration Destination classes.
 *
 * Destinations are responsible for persisting source data into the destination
 * Drupal.
 *
 * @see \Drupal\migrate\Plugin\migrate\destination\DestinationBase
 * @see \Drupal\migrate\Plugin\MigrateDestinationPluginManager
 * @see \Drupal\migrate\Attribute\MigrateDestination
 * @see plugin_api
 *
 * @ingroup migration
 */
interface MigrateDestinationInterface extends PluginInspectionInterface {

  /**
   * Gets the destination IDs.
   *
   * To support MigrateIdMap maps, derived destination classes should return
   * field definition(s) corresponding to the primary key of the destination
   * being implemented. These are used to construct the destination key fields
   * of the map table for a migration using this destination.
   *
   * @return array[]
   *   An associative array of field definitions keyed by field ID. Values are
   *   associative arrays with a structure that contains the field type ('type'
   *   key). The other keys are the field storage settings as they are returned
   *   by FieldStorageDefinitionInterface::getSettings(). As an example, for a
   *   composite destination primary key that is defined by an integer and a
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
   *   definition, can be passed in definitions:
   *   @code
   *     return [
   *       'nid' => [
   *         'type' => 'integer',
   *         'custom_setting' => 'some_value',
   *       ],
   *     ];
   *   @endcode
   *
   * @see \Drupal\Core\Field\FieldStorageDefinitionInterface::getSettings()
   * @see \Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem
   * @see \Drupal\Core\Field\Plugin\Field\FieldType\StringItem
   * @see \Drupal\text\Plugin\Field\FieldType\TextItem
   */
  public function getIds();

  /**
   * Returns an array of destination fields.
   *
   * Derived classes must implement fields(), returning a list of available
   * destination fields.
   *
   * @return array
   *   - Keys: machine names of the fields
   *   - Values: Human-friendly descriptions of the fields.
   */
  public function fields();

  /**
   * Import the row.
   *
   * Derived classes must implement import(), to construct one new object
   * (pre-populated) using ID mappings in the Migration.
   *
   * @param \Drupal\migrate\Row $row
   *   The row object.
   * @param array $old_destination_id_values
   *   (optional) The destination IDs from the previous import of this source
   *   row. This is empty the first time a source row is migrated. Defaults to
   *   an empty array.
   *
   * @return array|bool
   *   An indexed array of destination IDs in the same order as defined in the
   *   plugin's getIds() method if the plugin wants to save the IDs to the ID
   *   map, TRUE to indicate success without saving IDs to the ID map, or
   *   FALSE to indicate a failure.
   *
   * @throws \Drupal\migrate\MigrateException
   *   Throws an exception if there is a problem importing the row. By default,
   *   this causes the migration system to treat this row as having failed;
   *   however, any \Drupal\migrate\Plugin\MigrateIdMapInterface status constant
   *   can be set using the $status parameter of
   *   \Drupal\migrate\MigrateException, such as
   *   \Drupal\migrate\Plugin\MigrateIdMapInterface::STATUS_IGNORED.
   */
  public function import(Row $row, array $old_destination_id_values = []);

  /**
   * Delete the specified destination object from the target Drupal.
   *
   * @param array $destination_identifier
   *   The ID of the destination object to delete.
   */
  public function rollback(array $destination_identifier);

  /**
   * Whether the destination can be rolled back or not.
   *
   * @return bool
   *   TRUE if rollback is supported, FALSE if not.
   */
  public function supportsRollback();

  /**
   * The rollback action for the last imported item.
   *
   * @return int
   *   The MigrateIdMapInterface::ROLLBACK_ constant indicating how an imported
   *   item should be handled on rollback.
   */
  public function rollbackAction();

  /**
   * Gets the destination module handling the destination data.
   *
   * @return string|null
   *   The destination module or NULL if not found.
   */
  public function getDestinationModule();

}
