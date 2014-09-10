<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\MigratePluginManager.
 */

namespace Drupal\migrate_drupal\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\migrate\Plugin\MigratePluginManager as BaseMigratePluginManager;

/**
 * Manages migrate_drupal plugins.
 *
 * @see plugin_api
 *
 * @ingroup migration
 */
class MigratePluginManager extends BaseMigratePluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, $annotation = 'Drupal\Component\Annotation\PluginID') {
    parent::__construct($type, $namespaces, $cache_backend, $module_handler, $annotation);

    $this->factory = new ContainerFactory($this, 'Drupal\migrate_drupal\Plugin\MigrateLoadInterface');
  }


}
