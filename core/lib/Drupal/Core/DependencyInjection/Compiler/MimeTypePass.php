<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface as LegacyMimeTypeGuesserInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Adds @mime_type_guesser tagged services to handle forwards compatibility.
 *
 * @internal
 *
 * @deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. No direct
 * replacement is provided.
 *
 * @see https://www.drupal.org/node/3133341
 */
class MimeTypePass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    $consumer = $container->getDefinition('file.mime_type.guesser');

    $tag = 'mime_type_guesser';
    $interface = MimeTypeGuesserInterface::class;
    $deprecated_interface = LegacyMimeTypeGuesserInterface::class;

    // Find all tagged handlers.
    $handlers = [];
    foreach ($container->findTaggedServiceIds($tag) as $id => $attributes) {
      // Validate the interface.
      $handler = $container->getDefinition($id);
      if (!is_subclass_of($handler->getClass(), $interface)) {
        // Special handling for $deprecated_interface.
        if (!is_subclass_of($handler->getClass(), $deprecated_interface)) {
          throw new LogicException("Service '$id' does not implement $interface.");
        }
      }
      $handlers[$id] = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $interfaces[$id] = $handler->getClass();
    }
    if (empty($handlers)) {
      throw new LogicException(sprintf("At least one service tagged with '%s' is required.", $tag));
    }

    // Sort all handlers by priority.
    arsort($handlers, SORT_NUMERIC);

    // Add a method call for each handler to the consumer service
    // definition.
    foreach ($handlers as $id => $priority) {
      $arguments = [new Reference($id), $priority];
      if (is_subclass_of($interfaces[$id], $interface)) {
        $consumer->addMethodCall('addMimeTypeGuesser', $arguments);
      }
      else {
        $consumer->addMethodCall('addGuesser', $arguments);
      }
    }
  }

}
