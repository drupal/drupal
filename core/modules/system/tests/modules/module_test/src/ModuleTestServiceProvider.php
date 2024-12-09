<?php

declare(strict_types=1);

namespace Drupal\module_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Module test service provider.
 */
class ModuleTestServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    $container->addCompilerPass(new ModuleTestCompilerPass());
  }

}
