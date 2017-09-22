<?php

namespace Drupal\migrate_drupal\Plugin;

@trigger_error('MigrateCckFieldPluginManager is deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.x. Use \Drupal\migrate_drupal\Annotation\MigrateFieldPluginManager instead.', E_USER_DEPRECATED);

/**
 * Deprecated: Plugin manager for migrate field plugins.
 *
 * @deprecated in Drupal 8.3.x, to be removed before Drupal 9.0.x. Use
 *   \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManager instead.
 *
 * @see https://www.drupal.org/node/2751897
 *
 * @ingroup migration
 */
class MigrateCckFieldPluginManager extends MigrateFieldPluginManager implements MigrateCckFieldPluginManagerInterface {}
