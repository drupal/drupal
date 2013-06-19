<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\Compiler\RegisterBreadcrumbBuilderPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds services to the breadcrumb_builder service.
 */
class RegisterBreadcrumbBuilderPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('breadcrumb')) {
      return;
    }
    $manager = $container->getDefinition('breadcrumb');
    foreach ($container->findTaggedServiceIds('breadcrumb_builder') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $manager->addMethodCall('addBuilder', array(new Reference($id), $priority));
    }
  }

}
