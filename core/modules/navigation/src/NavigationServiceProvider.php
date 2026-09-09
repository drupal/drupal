<?php

declare(strict_types=1);

namespace Drupal\navigation;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Defines a service provider for the Navigation module.
 *
 * @internal
 */
final class NavigationServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    // The workspaces navigation item needs Workspaces UI, so only register
    // its lazy builder when that module is installed.
    if (isset($container->getParameter('container.modules')['workspaces_ui'])) {
      $container
        ->register('navigation.workspaces_lazy_builders', WorkspacesLazyBuilder::class)
        ->addArgument(new Reference('workspaces.manager'));
    }
  }

}
