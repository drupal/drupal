<?php

/**
 * @file
 * Contains Drupal\Core\DependencyInjection\Compiler\RegisterParamConvertersPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers EntityConverter services with the ParamConverterManager.
 */
class RegisterParamConvertersPass implements CompilerPassInterface {

  /**
   * Adds services tagged with "paramconverter" to the param converter service.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container to process.
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('paramconverter_manager')) {
      return;
    }

    $manager = $container->getDefinition('paramconverter_manager');
    foreach ($container->findTaggedServiceIds('paramconverter') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $manager->addMethodCall('addConverter', array($id, $priority));
    }
  }
}
