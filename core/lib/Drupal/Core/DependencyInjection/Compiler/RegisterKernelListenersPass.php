<?php

/**
 * @file
 * Definition of Drupal\Core\DependencyInjection\Compiler\RegisterKernelListenersPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class RegisterKernelListenersPass implements CompilerPassInterface {
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('event_dispatcher')) {
      return;
    }

    $definition = $container->getDefinition('event_dispatcher');

    foreach ($container->findTaggedServiceIds('event_subscriber') as $id => $attributes) {

      // We must assume that the class value has been correctly filled, even if the service is created by a factory
      $class = $container->getDefinition($id)->getClass();

      $refClass = new \ReflectionClass($class);
      $interface = 'Symfony\Component\EventDispatcher\EventSubscriberInterface';
      if (!$refClass->implementsInterface($interface)) {
        throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
      }
      $definition->addMethodCall('addSubscriberService', array($id, $class));
    }
  }
}
