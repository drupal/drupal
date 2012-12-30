<?php

/**
 * @file
 * Contains RdfMappingManager.
 */

namespace Drupal\rdf;

use ReflectionClass;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\rdf\MapTypesFromInputEvent;
use Drupal\rdf\RdfMappingEvents;
use Drupal\rdf\SiteSchema\BundleSchema;
use Drupal\rdf\SiteSchema\SiteSchema;
use Drupal\rdf\SiteSchema\SiteSchemaManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Manager for mapping internal and external schema terms.
 */
class RdfMappingManager {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The site schema manager.
   *
   * @var \Drupal\rdf\SiteSchema\SiteSchemaManager
   */
  protected $siteSchemaManager;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Drupal\rdf\SiteSchema\SiteSchemaManager $site_schema_manager
   *   The site schema manager.
   */
  public function __construct(EventDispatcherInterface $dispatcher, SiteSchemaManager $site_schema_manager) {
    $this->dispatcher = $dispatcher;
    $this->siteSchemaManager = $site_schema_manager;
  }

  /**
   * Convert an array of RDF type URIs to the corresponding TypedData IDs.
   *
   * @param array $input_rdf_types
   *   An array of URIs for the type.
   *
   * @return array
   *   An array containing entity_type and bundle.
   *
   * @throws \Drupal\rdf\RdfMappingException
   */
  public function getTypedDataIdsFromTypeUris($input_rdf_types) {
    // Get the cache of site schema types.
    $site_schema_types = $this->siteSchemaManager->getTypes();
    // Map the RDF type from the incoming data to an RDF type defined in the
    // internal site schema.
    $type_uri = $this->mapTypesFromInput($input_rdf_types);
    // If no site schema URI has been determined, then it's impossible to know
    // what entity type to create. Throw an exception.
    if ($type_uri == FALSE) {
      throw new RdfMappingException(sprintf('No mapping to a site schema type URI found for incoming types (%s).', implode(',', $input_rdf_types)));
    }
    // Use the mapped RDF type URI to get the TypedData API ids the rest of the
    // system uses (entity type and bundle).
    return $site_schema_types[$type_uri];
  }

  /**
   * Map an array of incoming URIs to an internal site schema URI.
   *
   * @param array $input_rdf_types
   *   An array of RDF type URIs.
   *
   * @return string
   *   The corresponding site schema type URI.
   */
  protected function mapTypesFromInput($input_rdf_types) {
    // Create the event using the array of incoming RDF type URIs and the cache
    // of internal site schema URIs.
    $site_schema_types = $this->siteSchemaManager->getTypes();
    $mapping_event = new MapTypesFromInputEvent($input_rdf_types, $site_schema_types);

    // Allow other modules to map the incoming type URIs to an internal site
    // schema type URI. For example, a content deployment module could take
    // URIs from the staging site's schema and map them to the corresponding
    // URI in the live site's schema.
    $this->dispatcher->dispatch(RdfMappingEvents::MAP_TYPES_FROM_INPUT, $mapping_event);

    return $mapping_event->getSiteSchemaUri();
  }
}
