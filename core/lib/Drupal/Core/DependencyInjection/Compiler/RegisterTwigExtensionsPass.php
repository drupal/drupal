<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\Compiler\RegisterTwigExtensionsPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Register additional Twig extensions to the Twig service container.
 */
class RegisterTwigExtensionsPass implements CompilerPassInterface {

  /**
   * Adds services tagged 'twig.extension' to the Twig service container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container to process.
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('twig')) {
      return;
    }

    $definition = $container->getDefinition('twig');

    foreach ($container->findTaggedServiceIds('twig.extension') as $id => $attributes) {
      // We must assume that the class value has been correcly filled,
      // even if the service is created by a factory.
      $class = $container->getDefinition($id)->getClass();

      $refClass = new \ReflectionClass($class);
      $interface = 'Twig_ExtensionInterface';
      if (!$refClass->implementsInterface($interface)) {
        throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
      }
      $definition->addMethodCall('addExtension', array(new Reference($id)));
    }
  }

}
