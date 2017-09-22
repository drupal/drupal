<?php

namespace Drupal\migrate\Plugin\migrate\process;

@trigger_error('The ' . __NAMESPACE__ . '\Migration is deprecated in
Drupal 8.4.0 and will be removed before Drupal 9.0.0. Instead, use ' . __NAMESPACE__ . '\MigrationLookup', E_USER_DEPRECATED);

/**
 * Calculates the value of a property based on a previous migration.
 *
 * @link https://www.drupal.org/node/2149801 Online handbook documentation for migration process plugin @endlink
 *
 * @MigrateProcessPlugin(
 *   id = "migration"
 * )
 *
 * @deprecated in Drupal 8.3.x and will be removed in Drupal 9.0.x.
 *  Use \Drupal\migrate\Plugin\migrate\process\MigrationLookup instead.
 */
class Migration extends MigrationLookup {}
