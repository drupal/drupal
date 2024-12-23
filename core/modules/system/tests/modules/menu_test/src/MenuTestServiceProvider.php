<?php

declare(strict_types=1);

namespace Drupal\menu_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Decorate core's default path-based breadcrumb builder when it is available.
 */
class MenuTestServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    if ($container->has('system.breadcrumb.default')) {
      $container->register('menu_test.breadcrumb.default', SkippablePathBasedBreadcrumbBuilder::class)
        ->setDecoratedService('system.breadcrumb.default')
        ->addArgument(new Reference('menu_test.breadcrumb.default.inner'))
        ->addArgument(new Reference('request_stack'));
    }
  }

}
