<?php

namespace Drupal\KernelTests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Provides special routing services for tests.
 */
class TestServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

  /**
   * @var \Drupal\simpletest\TestBase
   */
  public static $currentTest;

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    if (static::$currentTest && method_exists(static::$currentTest, 'containerBuild')) {
      static::$currentTest->containerBuild($container);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if (static::$currentTest instanceof KernelTestBase) {
      static::addRouteProvider($container);
    }
  }

  /**
   * Add the on demand rebuild route provider service.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   */
  public static function addRouteProvider(ContainerBuilder $container) {
    $route_provider_service_name = 'router.route_provider';
    // While $container->get() does a recursive resolve, getDefinition() does
    // not, so do it ourselves.
    $id = $route_provider_service_name;
    while ($container->hasAlias($id)) {
      $id = (string) $container->getAlias($id);
    }
    $definition = $container->getDefinition($id);
    $definition->clearTag('needs_destruction');
    $container->setDefinition("simpletest.$route_provider_service_name", $definition);
    $container->setDefinition($id, new Definition(RouteProvider::class));
  }

}
