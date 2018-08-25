<?php

namespace Drupal\layout_builder;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\layout_builder\EventSubscriber\SetInlineBlockDependency;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Sets the layout_builder.get_block_dependency_subscriber service definition.
 *
 * This service is dependent on the block_content module so it must be provided
 * dynamically.
 *
 * @internal
 *
 * @see \Drupal\layout_builder\EventSubscriber\SetInlineBlockDependency
 */
class LayoutBuilderServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['block_content'])) {
      $definition = new Definition(SetInlineBlockDependency::class);
      $definition->setArguments([
        new Reference('entity_type.manager'),
        new Reference('database'),
        new Reference('inline_block.usage'),
      ]);
      $definition->addTag('event_subscriber');
      $container->setDefinition('layout_builder.get_block_dependency_subscriber', $definition);
    }
  }

}
