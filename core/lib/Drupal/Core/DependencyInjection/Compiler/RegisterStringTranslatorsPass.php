<?php

/**
 * @file
 * Contains Drupal\Core\DependencyInjection\Compiler\RegisterStringTranslatorsPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds services tagged 'string_translator' to the string_translation service.
 */
class RegisterStringTranslatorsPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('string_translation')) {
      return;
    }
    $access_manager = $container->getDefinition('string_translation');
    foreach ($container->findTaggedServiceIds('string_translator') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $access_manager->addMethodCall('addTranslator', array(new Reference($id), $priority));
    }
  }

}
