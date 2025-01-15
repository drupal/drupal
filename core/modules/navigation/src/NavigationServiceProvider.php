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
    // If shortcuts module service is available, register our own service.
    if ($container->has('shortcut.lazy_builders')) {
      $container
        ->register('navigation.shortcut_lazy_builder', ShortcutLazyBuilder::class)
        ->addArgument(new Reference('shortcut.lazy_builders'));
    }

    // If Workspaces module service is available, register our own service.
    if ($container->has('workspaces.lazy_builders')) {
      $container
        ->register('navigation.workspaces_lazy_builders', WorkspacesLazyBuilder::class)
        ->addArgument(new Reference('workspaces.manager'));
    }
  }

}
