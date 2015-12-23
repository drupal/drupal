<?php

/**
 * @file
 * Contains \Drupal\simpletest\TestServiceProvider.
 */

namespace Drupal\simpletest;

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
  function register(ContainerBuilder $container) {
    if (static::$currentTest && method_exists(static::$currentTest, 'containerBuild')) {
      static::$currentTest->containerBuild($container);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if (static::$currentTest instanceof KernelTestBase) {
      // While $container->get() does a recursive resolve, getDefinition() does
      // not, so do it ourselves.
      foreach (['router.route_provider' => 'RouteProvider'] as $original_id => $class) {
        for ($id = $original_id; $container->hasAlias($id); $id = (string) $container->getAlias($id));
        $definition = $container->getDefinition($id);
        $definition->clearTag('needs_destruction');
        $container->setDefinition("simpletest.$original_id", $definition);
        $container->setDefinition($id, new Definition('Drupal\simpletest\\' . $class));
      }
    }
  }
}
