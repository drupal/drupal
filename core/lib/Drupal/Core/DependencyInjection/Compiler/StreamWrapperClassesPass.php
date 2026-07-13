<?php

declare(strict_types=1);

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects the classes of services tagged 'stream_wrapper'.
 *
 * Stream wrapper classes have to be registered with PHP by class name, as
 * PHP instantiates stream wrappers itself without dependency injection.
 * Collecting the class names at compile time allows the stream wrapper manager
 * to register wrappers without instantiating the individual services.
 */
class StreamWrapperClassesPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    if (!$container->hasDefinition('stream_wrapper_manager')) {
      return;
    }

    $wrapper_classes = [];
    foreach ($container->findTaggedServiceIds('stream_wrapper') as $id => $tags) {
      $class = $container->getDefinition($id)->getClass();
      foreach ($tags as $attributes) {
        $wrapper_classes[$attributes['scheme']] = $class;
      }
    }
    $container->getDefinition('stream_wrapper_manager')->setArgument('$wrapperClasses', $wrapper_classes);
  }

}
