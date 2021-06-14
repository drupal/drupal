<?php

namespace Drupal\migrate_drupal;

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Provides field discovery for Drupal 6 & 7 migrations.
 */
interface FieldDiscoveryInterface {

  const DRUPAL_6 = '6';

  const DRUPAL_7 = '7';

  /**
   * Adds the field processes to a migration.
   *
   * This method is used in field migrations to execute the migration process
   * alter method specified by the 'field_plugin_method' key of the migration
   * for all field plugins applicable to this Drupal to Drupal migration. This
   * method is used internally for field, field instance, widget, and formatter
   * migrations to allow field plugins to alter the process for these
   * migrations.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to add process plugins to.
   *
   * @throws \InvalidArgumentException
   *
   * @internal
   */
  public function addAllFieldProcesses(MigrationInterface $migration);

  /**
   * Adds the field processes for an entity to a migration.
   *
   * This method is used in field migrations to execute the migration process
   * alter method specified by the 'field_plugin_method' key of the migration
   * for all field plugins applicable to this Drupal to Drupal migration. This
   * method is used internally for field, field instance, widget, and formatter
   * migrations to allow field plugins to alter the process for these
   * migrations.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to add processes to.
   * @param string $entity_type_id
   *   The legacy entity type to add processes for.
   *
   * @throws \InvalidArgumentException
   */
  public function addEntityFieldProcesses(MigrationInterface $migration, $entity_type_id);

  /**
   * Adds the field processes for a bundle to a migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to add processes to.
   * @param string $entity_type_id
   *   The legacy entity type to add processes for.
   * @param string $bundle
   *   The legacy bundle (or content_type) to add processes for.
   *
   * @throws \InvalidArgumentException
   */
  public function addBundleFieldProcesses(MigrationInterface $migration, $entity_type_id, $bundle);

}
