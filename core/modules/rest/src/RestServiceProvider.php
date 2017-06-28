<?php

namespace Drupal\rest;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\rest\LinkManager\LinkManager;
use Drupal\rest\LinkManager\RelationLinkManager;
use Drupal\rest\LinkManager\TypeLinkManager;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Provides BC services.
 *
 * These services are not added via rest.services.yml because the service
 * classes extend classes from the HAL module. They also have no use without
 * that module.
 */
class RestServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $modules = $container->getParameter(('container.modules'));
    if (isset($modules['hal'])) {
      // @deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.0.
      //   Use hal.link_manager instead.
      // @see https://www.drupal.org/node/2830467
      $service_definition = new DefinitionDecorator(new Reference('hal.link_manager'));
      $service_definition->setClass(LinkManager::class);
      $container->setDefinition('rest.link_manager', $service_definition);

      // @deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.0.
      //   Use hal.link_manager.type instead.
      // @see https://www.drupal.org/node/2830467
      $service_definition = new DefinitionDecorator(new Reference('hal.link_manager.type'));
      $service_definition->setClass(TypeLinkManager::class);
      $container->setDefinition('rest.link_manager.type', $service_definition);

      // @deprecated in Drupal 8.3.x and will be removed before Drupal 9.0.0.
      //   Use hal.link_manager.relation instead.
      // @see https://www.drupal.org/node/2830467
      $service_definition = new DefinitionDecorator(new Reference('hal.link_manager.relation'));
      $service_definition->setClass(RelationLinkManager::class);
      $container->setDefinition('rest.link_manager.relation', $service_definition);
    }
  }

}
