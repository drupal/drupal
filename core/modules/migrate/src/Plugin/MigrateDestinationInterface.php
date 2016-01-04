<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\MigrateDestinationInterface.
 */

namespace Drupal\migrate\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Defines an interface for Migration Destination classes.
 *
 * Destinations are responsible for persisting source data into the destination
 * Drupal.
 *
 * @see \Drupal\migrate\Plugin\destination\DestinationBase
 * @see \Drupal\migrate\Plugin\MigrateDestinationPluginManager
 * @see \Drupal\migrate\Annotation\MigrateDestination
 * @see plugin_api
 *
 * @ingroup migration
 */
interface MigrateDestinationInterface extends PluginInspectionInterface {

  /**
   * Get the destination IDs.
   *
   * To support MigrateIdMap maps, derived destination classes should return
   * schema field definition(s) corresponding to the primary key of the
   * destination being implemented. These are used to construct the destination
   * key fields of the map table for a migration using this destination.
   *
   * @return array
   *   An array of IDs.
   */
  public function getIds();

  /**
   * Returns an array of destination fields.
   *
   * Derived classes must implement fields(), returning a list of available
   * destination fields.
   *
   * @todo Review the cases where we need the Migration parameter, can we avoid
   *   that? To be resolved with https://www.drupal.org/node/2543568.
   *
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   (optional) The migration containing this destination. Defaults to NULL.
   *
   * @return array
   *   - Keys: machine names of the fields
   *   - Values: Human-friendly descriptions of the fields.
   */
  public function fields(MigrationInterface $migration = NULL);

  /**
   * Import the row.
   *
   * Derived classes must implement import(), to construct one new object
   * (pre-populated) using ID mappings in the Migration.
   *
   * @param \Drupal\migrate\Row $row
   *   The row object.
   * @param array $old_destination_id_values
   *   (optional) The old destination IDs. Defaults to an empty array.
   *
   * @return mixed
   *   The entity ID or an indication of success.
   */
  public function import(Row $row, array $old_destination_id_values = array());

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

}
