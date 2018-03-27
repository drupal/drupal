<?php

namespace Drupal\simpletest;

<<<<<<< HEAD
use Drupal\KernelTests\TestServiceProvider as CoreTestServiceProvider;

/**
 * Provides special routing services for tests.
 *
 * @deprecated in 8.6.0 for removal before Drupal 9.0.0. Use
 *   Drupal\KernelTests\TestServiceProvider instead.
 *
 * @see https://www.drupal.org/node/2943146
 */
class TestServiceProvider extends CoreTestServiceProvider {
=======
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Definition;

class TestServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

  /**
   * @var \Drupal\simpletest\TestBase;
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
    foreach (['router.route_provider' => 'RouteProvider'] as $original_id => $class) {
      // While $container->get() does a recursive resolve, getDefinition() does
      // not, so do it ourselves.
      // @todo Make the code more readable in
      //   https://www.drupal.org/node/2911498.
      for ($id = $original_id; $container->hasAlias($id); $id = (string) $container->getAlias($id)) {
      }
      $definition = $container->getDefinition($id);
      $definition->clearTag('needs_destruction');
      $container->setDefinition("simpletest.$original_id", $definition);
      $container->setDefinition($id, new Definition('Drupal\simpletest\\' . $class));
    }
  }
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd

}
