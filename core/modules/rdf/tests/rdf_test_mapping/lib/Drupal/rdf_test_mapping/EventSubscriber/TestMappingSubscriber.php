<?php

/**
 * @file
 * Contains TestMappingSubscriber.
 */

namespace Drupal\rdf_test_mapping\EventSubscriber;

use Drupal\rdf\RdfMappingEvents;
use Drupal\rdf\SiteSchema\BundleSchema;
use Drupal\rdf\SiteSchema\SiteSchema;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestMappingSubscriber implements EventSubscriberInterface {

  const STAGING_SITE_TYPE_URI = 'http://staging.com/entity_test_bundle';

  /**
   * Demonstrate mapping between external type and site schema type.
   *
   * @param \Drupal\rdf\MapTypesFromInputEvent $event
   *   The mapping event.
   */
  public function mapTypesFromInput($event) {
    $input_uris = $event->getInputUris();
    $site_schema_types = $event->getSiteSchemaTypes();

    // This mapping between an external type and a site schema type would be
    // managed by something in the implementing module, such as a database
    // table. For the test, manually map a fake external URI to the site schema
    // URI for the test entity.
    $schema = new SiteSchema(SiteSchema::CONTENT_DEPLOYMENT);
    $bundle_schema = $schema->bundle('entity_test', 'entity_test');
    $site_schema_type = $bundle_schema->getUri();
    $mapping = array(
      self::STAGING_SITE_TYPE_URI => $site_schema_type,
    );

    foreach ($input_uris as $input_uri) {
      // If the incoming URI is mapped in the mapping array, and the value of
      // that mapping is found in the cache of site schema types, then set the
      // site schema URI.
      if (isset($mapping[$input_uri]) && isset($site_schema_types[$mapping[$input_uri]])) {
        $event->setSiteSchemaUri($mapping[$input_uri]);
      }
    }
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    $events[RdfMappingEvents::MAP_TYPES_FROM_INPUT] = 'mapTypesFromInput';
    return $events;
  }

}
