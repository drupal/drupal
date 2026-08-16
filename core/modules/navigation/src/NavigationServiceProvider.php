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
    // If Workspaces module service is available, register our own service.
    if ($container->has('workspaces_ui.lazy_builders')) {
      $container
        ->register('navigation.workspaces_lazy_builders', WorkspacesLazyBuilder::class)
        ->addArgument(new Reference('workspaces.manager'));
    }
  }

}
