<?php

declare(strict_types=1);

namespace Drupal\package_manager_bypass;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Site\Settings;
use Drupal\package_manager\PathLocator;
use PhpTuf\ComposerStager\API\Core\StagerInterface;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Defines services to bypass Package Manager's core functionality.
 *
 * @internal
 */
final class PackageManagerBypassServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    parent::alter($container);

    // By default, \Drupal\package_manager_bypass\NoOpStager is applied, except
    // when a test opts out by setting this setting to FALSE.
    // @see \Drupal\package_manager_bypass\NoOpStager::setLockFileShouldChange()
    if (Settings::get('package_manager_bypass_composer_stager', TRUE)) {
      $container->register(NoOpStager::class)
        ->setClass(NoOpStager::class)
        ->setPublic(FALSE)
        ->setAutowired(TRUE)
        ->setDecoratedService(StagerInterface::class);
    }

    $container->getDefinition(PathLocator::class)
      ->setClass(MockPathLocator::class)
      ->setAutowired(FALSE)
      ->setArguments([
        new Reference('state'),
        new Parameter('app.root'),
        new Reference('config.factory'),
        new Reference('file_system'),
      ]);
  }

}
