<?php

namespace Drupal\migrate_drupal\Plugin;

@trigger_error('MigrateCckFieldPluginManagerInterface is deprecated in Drupal 8.3.x
and will be removed before Drupal 9.0.x. Use \Drupal\migrate_drupal\Annotation\MigrateFieldPluginManagerInterface
instead.', E_USER_DEPRECATED);

/**
 * Provides an interface for cck field plugin manager.
 *
 * @deprecated in Drupal 8.3.x, to be removed before Drupal 9.0.x. Use
 *   \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface instead.
 */
interface MigrateCckFieldPluginManagerInterface extends MigrateFieldPluginManagerInterface { }
