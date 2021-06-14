<?php

namespace Drupal\node;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\node\EventSubscriber\NodeTranslationExceptionSubscriber;
use Drupal\node\EventSubscriber\NodeTranslationMigrateSubscriber;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers services in the container.
 */
class NodeServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Register the node.node_translation_migrate service in the container if
    // the migrate and language modules are enabled.
    $modules = $container->getParameter('container.modules');
    if (isset($modules['migrate']) && isset($modules['language'])) {
      $container->register('node.node_translation_migrate', NodeTranslationMigrateSubscriber::class)
        ->addTag('event_subscriber')
        ->addArgument(new Reference('keyvalue'))
        ->addArgument(new Reference('state'));
    }

    // Register the node.node_translation_exception service in the container if
    // the language module is enabled.
    if (isset($modules['language'])) {
      $container->register('node.node_translation_exception', NodeTranslationExceptionSubscriber::class)
        ->addTag('event_subscriber')
        ->addArgument(new Reference('keyvalue'))
        ->addArgument(new Reference('language_manager'))
        ->addArgument(new Reference('url_generator'))
        ->addArgument(new Reference('state'));
    }
  }

}
