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

    $event_subscriber_info = [];
    foreach ($container->findTaggedServiceIds('event_subscriber') as $id => $attributes) {

      // We must assume that the class value has been correctly filled, even if the service is created by a factory
      $class = $container->getDefinition($id)->getClass();

      $refClass = new \ReflectionClass($class);
      $interface = 'Symfony\Component\EventDispatcher\EventSubscriberInterface';
      if (!$refClass->implementsInterface($interface)) {
        throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $interface));
      }

      // Get all subscribed events.
      foreach ($class::getSubscribedEvents() as $event_name => $params) {
        if (is_string($params)) {
          $priority = 0;
          $event_subscriber_info[$event_name][$priority][] = ['service' => [$id, $params]];
        }
        elseif (is_string($params[0])) {
          $priority = isset($params[1]) ? $params[1] : 0;
          $event_subscriber_info[$event_name][$priority][] = ['service' => [$id, $params[0]]];
        }
        else {
          foreach ($params as $listener) {
            $priority = isset($listener[1]) ? $listener[1] : 0;
            $event_subscriber_info[$event_name][$priority][] = ['service' => [$id, $listener[0]]];
          }
        }
      }
    }

    foreach (array_keys($event_subscriber_info) as $event_name) {
      krsort($event_subscriber_info[$event_name]);
    }

    $definition->addArgument($event_subscriber_info);
  }
}
