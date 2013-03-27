<?php

/**
 * @file
 * Contains RdfBundle.
 */

namespace Drupal\rdf;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * RDF dependency injection container.
 */
class RdfBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    // Site schema manager service.
    $container->register('rdf.site_schema_manager', 'Drupal\rdf\SiteSchema\SiteSchemaManager')
      ->addArgument(new Reference('cache.cache'));
    // Mapping manager service.
    $container->register('rdf.mapping_manager', 'Drupal\rdf\RdfMappingManager')
      ->addArgument(new Reference('event_dispatcher'))
      ->addArgument(new Reference('rdf.site_schema_manager'));

    // Mapping subscriber.
    $container->register('rdf.mapping', 'Drupal\rdf\EventSubscriber\MappingSubscriber')
      ->addTag('event_subscriber');
    // Route subscriber.
    $container->register('rdf.route_subscriber', 'Drupal\rdf\EventSubscriber\RouteSubscriber')
      ->addArgument(new Reference('rdf.site_schema_manager'))
      ->addTag('event_subscriber');
  }
}
