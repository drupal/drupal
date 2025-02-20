<?php

declare(strict_types=1);

namespace Drupal\error_service_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * The service provider for testing bedlam in container rebuilds.
 */
class ErrorServiceTestServiceProvider implements ServiceModifierInterface {

  /**
   * The in-situ container builder.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  public static $containerBuilder;

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    static::$containerBuilder = $container;
  }

}
