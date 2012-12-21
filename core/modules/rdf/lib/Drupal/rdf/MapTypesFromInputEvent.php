<?php

/**
 * @file
 * Contains MapInputTypesEvent.
 */

namespace Drupal\rdf;

use Symfony\Component\EventDispatcher\Event;

/**
 * Represents type mapping as event.
 */
class MapTypesFromInputEvent extends Event {

  /**
   * An array of incoming RDF type URIs.
   *
   * @var array
   */
  protected $inputUris;

  /**
   * An array of entity_type/bundles, keyed by site schema type URI
   *
   * @var array
   */
  protected $siteSchemaTypes;

  /**
   * The site schema type URI.
   *
   * @var string
   */
  protected $siteSchemaUri;

  /**
   * Constructor.
   *
   * @param $input_uris
   *   An array of incoming RDF type URIs.
   * @param $site_schema_types
   *   An array of entity_type/bundles, keyed by site schema type URI.
   */
  public function __construct($input_uris, $site_schema_types) {
    $this->inputUris = $input_uris;
    $this->siteSchemaTypes = $site_schema_types;
    $this->siteSchemaUri = FALSE;
  }

  /**
   * Gets the input URI.
   *
   * @return array
   *   The array of incoming RDF type URIs.
   */
  public function getInputUris() {
    return $this->inputUris;
  }

  /**
   * Gets the cache of internal site schema types.
   *
   * @return array
   *   The cached site schema type array.
   */
  public function getSiteSchemaTypes() {
    return $this->siteSchemaTypes;
  }

  /**
   * Gets the site schema URI.
   *
   * @return string|bool
   *   The site schema type URI if set, FALSE if otherwise.
   */
  public function getSiteSchemaUri() {
    return $this->siteSchemaUri;
  }

  /**
   * Sets the site schema URI.
   *
   * @param string $uri
   *   The site schema type URI.
   */
  public function setSiteSchemaUri($uri) {
    $this->siteSchemaUri = $uri;
  }
}
