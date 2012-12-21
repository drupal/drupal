<?php

/**
 * @file
 * Contains RdfTestMappingBundle.
 */

namespace Drupal\rdf_test_mapping;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * RDF dependency injection container.
 */
class RdfTestMappingBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    // Mapping subscriber.
    $container->register('rdf_test_mapping.mapping', 'Drupal\rdf_test_mapping\EventSubscriber\TestMappingSubscriber')
      ->addTag('event_subscriber');
  }

}
