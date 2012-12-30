<?php
/**
 * @file
 * Contains MappingSubscriber.
 */

namespace Drupal\rdf\EventSubscriber;

use Drupal\rdf\RdfMappingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Default RDF mapping handling.
 */
class MappingSubscriber implements EventSubscriberInterface {

  /**
   * Stops event if incoming URI is a site schema URI.
   *
   * If the incoming URI is one of the site's own registered types, then
   * mapping is unnecessary. Mapping is only necessary if the incoming type URI
   * is from an external vocabulary.
   *
   * @param \Drupal\rdf\MapTypesFromInputEvent $event
   *   The mapping event.
   */
  public function mapTypesFromInput(\Drupal\rdf\MapTypesFromInputEvent $event) {
    $input_uris = $event->getInputUris();
    $site_schema_types = $event->getSiteSchemaTypes();
    foreach ($input_uris as $input_uri) {
      if (isset($site_schema_types[$input_uri])) {
        $event->setSiteSchemaUri($input_uri);
        $event->stopPropagation();
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
