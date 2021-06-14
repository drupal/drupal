<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Provides a compiler pass for stacked session save handlers.
 */
class StackedSessionHandlerPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {

    if ($container->hasDefinition('session_handler')) {
      return;
    }

    $session_handler_proxies = [];
    $priorities = [];

    foreach ($container->findTaggedServiceIds('session_handler_proxy') as $id => $attributes) {
      $priorities[$id] = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $session_handler_proxies[$id] = $container->getDefinition($id);
    }

    array_multisort($priorities, SORT_ASC, $session_handler_proxies);

    $decorated_id = 'session_handler.storage';
    foreach ($session_handler_proxies as $id => $decorator) {
      // Prepend the inner session handler as first constructor argument.
      $arguments = $decorator->getArguments();
      array_unshift($arguments, new Reference($decorated_id));
      $decorator->setArguments($arguments);

      $decorated_id = $id;
    }

    $container->setAlias('session_handler', $decorated_id);
  }

}
