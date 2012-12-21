<?php

/**
 * @file
 * Contains RdfMappingEvents.
 */

namespace Drupal\rdf;

/**
 * Contains all events for mapping site schemas to external vocabularies.
 */
final class RdfMappingEvents {

  /**
   * Maps an array of incoming type URIs to a site schema URI.
   *
   * Modules can use this event to convert an RDF type from an externally
   * defined vocabulary to a URI defined in the site's schema. From the site
   * schema URI, the site can derive the Typed Data API ids, which can be used
   * to create an entity.
   *
   * @see \Drupal\rdf\RdfMappingManager
   *
   * @var string
   */
  const MAP_TYPES_FROM_INPUT = 'rdf.map_types_from_input';

}
