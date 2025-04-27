<?php

declare(strict_types=1);

namespace Drupal\package_manager_test_validation;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\package_manager\Validator\SandboxDatabaseUpdatesValidator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies container services for testing.
 */
class PackageManagerTestValidationServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    parent::alter($container);

    $service_id = SandboxDatabaseUpdatesValidator::class;
    if ($container->hasDefinition($service_id)) {
      $container->getDefinition($service_id)
        ->setClass(TestSandboxDatabaseUpdatesValidator::class)
        ->addMethodCall('setState', [
          new Reference('state'),
        ]);
    }
  }

}
