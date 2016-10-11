<?php

namespace Drupal\migrate_drupal\Plugin\migrate;

/**
 * Migration plugin class for migrations dealing with field values.
 *
 * @deprecated in Drupal 8.2.x, to be removed before Drupal 9.0.x. Use
 * \Drupal\migrate_drupal\Plugin\migrate\FieldMigration instead.
 */
class CckMigration extends FieldMigration {

  /**
   * {@inheritdoc}
   */
  const PLUGIN_METHOD = 'cck_plugin_method';

}
