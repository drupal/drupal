<?php

/**
 * @file
 * Definition of Drupal\jsonld\JsonldBundle.
 */

namespace Drupal\jsonld;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Serializer\Serializer;

/**
 * Jsonld dependency injection container.
 */
class JsonldBundle extends Bundle {

  /**
   * Overrides Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    $priority = 5;

    // Normalizers can be specified to support a particular class and format in
    // Normalizer::supportsNormalization(). Since the first matching Normalizer
    // is used, Normalizers should be ordered from most specific to least
    // specific.
    $normalizers = array(
      // Field Item.
      'entity_reference' => array(
        'jsonld' => 'Drupal\jsonld\JsonldEntityReferenceNormalizer',
      ),
      'field_item' => array(
        'jsonld' => 'Drupal\jsonld\JsonldFieldItemNormalizer',
      ),
      // Entity.
      'entity' => array(
        'jsonld' => 'Drupal\jsonld\JsonldEntityNormalizer',
      ),
      // RDF Schema.
      'rdf_schema' => array(
        'jsonld' => 'Drupal\jsonld\JsonldRdfSchemaNormalizer',
      ),
    );

    // Add Normalizers to service container.
    foreach ($normalizers as $supported_class => $formats) {
      foreach ($formats as $format => $normalizer_class) {
        $container->register("serializer.normalizer.{$supported_class}.{$format}", $normalizer_class)
          ->addArgument(new Reference('rdf.site_schema_manager'))
          ->addArgument(new Reference('rdf.mapping_manager'))
          ->addTag('normalizer', array('priority' => $priority));
      }
    }

    // Add the encoder to the service container. Encoders can only specify which
    // format they support in Encoder::supportsEncoding().
    $container->register('serializer.encoder.jsonld', 'Drupal\jsonld\JsonldEncoder')
      ->addTag('encoder', array(
        'priority' => $priority,
        'format' => array(
          'jsonld' => 'JSON-LD',
          'drupal_jsonld' => 'Drupal JSON-LD',
        ),
      ));

    $container->register('jsonld.subscriber', 'Drupal\jsonld\EventSubscriber\JsonldSubscriber')
      ->addTag('event_subscriber');
  }
}
