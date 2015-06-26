<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\Compiler\RegisterStreamWrappersPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds services tagged 'stream_wrapper' to the stream_wrapper_manager service.
 */
class RegisterStreamWrappersPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('stream_wrapper_manager')) {
      return;
    }

    $stream_wrapper_manager = $container->getDefinition('stream_wrapper_manager');

    foreach ($container->findTaggedServiceIds('stream_wrapper') as $id => $attributes) {
      $class = $container->getDefinition($id)->getClass();
      $scheme = $attributes[0]['scheme'];

      $stream_wrapper_manager->addMethodCall('addStreamWrapper', array($id, $class, $scheme));
    }
  }

}
