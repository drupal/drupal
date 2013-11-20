<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\MigrateDestinationInterface.
 */

namespace Drupal\migrate\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Row;

/**
 * Destinations are responsible for persisting source data into the destination
 * Drupal.
 */
interface MigrateDestinationInterface extends PluginInspectionInterface {

  /**
   * To support MigrateIdMap maps, derived destination classes should return
   * schema field definition(s) corresponding to the primary key of the destination
   * being implemented. These are used to construct the destination key fields
   * of the map table for a migration using this destination.
   */
  public function getIdsSchema();

  /**
   * Derived classes must implement fields(), returning a list of available
   * destination fields.
   *
   * @todo Review the cases where we need the Migration parameter, can we avoid that?
   *
   * @param Migration $migration
   *   Optionally, the migration containing this destination.
   * @return array
   *  - Keys: machine names of the fields
   *  - Values: Human-friendly descriptions of the fields.
   */
  public function fields(Migration $migration = NULL);

  /**
   * Derived classes may implement preImport() and/or postImport(), to do any
   * processing they need done before or after looping over all source rows.
   * Similarly, preRollback() or postRollback() may be implemented.
   */
  public function preImport();
  public function preRollback();
  public function postImport();
  public function postRollback();

  /**
   * Derived classes must implement import(), to construct one new object (pre-populated
   * using ID mappings in the Migration).
   */
  public function import(Row $row);

  /**
   * Delete the specified IDs from the target Drupal.
   * @param array $destination_identifiers
   */
  public function rollbackMultiple(array $destination_identifiers);
}
