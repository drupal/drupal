<?php

declare(strict_types=1);

namespace Drupal\shortcut;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Defines a service provider for the Shortcut module.
 *
 * @internal
 */
final class ShortcutServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    // Register the navigation lazy builder if navigation module is enabled.
    if ($container->has('navigation.renderer')) {
      $container
        ->register('navigation.shortcut_lazy_builder', ShortcutNavigationLazyBuilder::class)
        ->addArgument(new Reference('shortcut.lazy_builders'));
    }
  }

}
