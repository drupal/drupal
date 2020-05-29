<?php

namespace Drupal\Core\Installer;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Service provider for the non early installer environment.
 */
class NormalInstallerServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Use performance-optimized extension lists.
    $container->getDefinition('extension.list.module')->setClass('Drupal\Core\Installer\InstallerModuleExtensionList');
    $container->getDefinition('extension.list.theme')->setClass('Drupal\Core\Installer\InstallerThemeExtensionList');
    $container->getDefinition('extension.list.theme_engine')->setClass('Drupal\Core\Installer\InstallerThemeEngineExtensionList');
  }

}
