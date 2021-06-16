<?php

namespace Drupal\migrate_drupal;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Alters container services.
 */
class MigrateDrupalServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    parent::alter($container);

    $container->getDefinition('plugin.manager.migration')
      ->setClass(MigrationPluginManager::class)
      ->addArgument(new Reference('plugin.manager.migrate.source'))
      ->addArgument(new Reference('config.factory'));
  }

}
