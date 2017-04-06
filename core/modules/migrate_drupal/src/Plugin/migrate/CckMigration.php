<?php

namespace Drupal\migrate_drupal\Plugin\migrate;

@trigger_error('CckMigration is deprecated in Drupal 8.3.x and will be removed
before Drupal 9.0.x. Use \Drupal\migrate_drupal\Plugin\migrate\FieldMigration
instead.', E_USER_DEPRECATED);

/**
 * Migration plugin class for migrations dealing with CCK field values.
 *
 * @deprecated in Drupal 8.3.x, to be removed before Drupal 9.0.x. Use
 * \Drupal\migrate_drupal\Plugin\migrate\FieldMigration instead.
 */
class CckMigration extends FieldMigration {

  /**
   * {@inheritdoc}
   */
  const PLUGIN_METHOD = 'cck_plugin_method';

}
