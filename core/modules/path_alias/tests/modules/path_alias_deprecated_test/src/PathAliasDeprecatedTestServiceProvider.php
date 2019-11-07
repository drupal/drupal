<?php

namespace Drupal\path_alias_deprecated_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Test service provider to test path alias deprecated services BC logic.
 */
class PathAliasDeprecatedTestServiceProvider implements ServiceModifierInterface {

  /**
   * The name of the new implementation class for the alias manager.
   *
   * @var string
   */
  public static $newClass;

  /**
   * Whether to use a decorator to wrap the alias manager implementation.
   *
   * @var bool
   */
  public static $useDecorator = FALSE;

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if (isset(static::$newClass)) {
      $definition = $container->getDefinition('path.alias_manager');
      $definition->setClass(static::$newClass);
    }

    if (!static::$useDecorator) {
      $decorator_id = 'path_alias_deprecated_test.path.alias_manager';
      if ($container->hasDefinition($decorator_id)) {
        $container->removeDefinition($decorator_id);
      }
    }
  }

}
