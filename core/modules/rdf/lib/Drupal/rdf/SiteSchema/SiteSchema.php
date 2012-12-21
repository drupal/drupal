<?php

/**
 * @file
 * Contains SiteSchema.
 */

namespace Drupal\rdf\SiteSchema;

use Drupal\rdf\SiteSchema\BundleSchema;
use Drupal\rdf\SiteSchema\EntitySchema;

/**
 * Defines a site-generated schema.
 */
class SiteSchema {

  // Site schema paths. There are only two site schemas provided by core, which
  // are not intended to be extensible. If a site wants to use external
  // vocabulary terms, the appropriate way to do this is to use the RDF mapping
  // system.
  const CONTENT_DEPLOYMENT = 'site-schema/content-deployment/';
  const SYNDICATION     = 'site-schema/syndication/';

  /**
   * The relative base path of the instantiated schema.
   *
   * @var string
   */
  protected $schemaPath;

  /**
   * Constructor.
   *
   * @param string $schema_path
   *   The schema path constant, used to determine which schema to instantiate.
   *
   * @throws \UnexpectedValueException
   */
  public function __construct($schema_path) {
    $valid_paths = array(self::CONTENT_DEPLOYMENT, self::SYNDICATION);
    if (!in_array($schema_path, $valid_paths)) {
      throw new \UnexpectedValueException(sprintf('%s is not a valid site schema path. Schema path must be one of %s.'), $schema_path, implode(', ', $valid_paths));
    }
    $this->schemaPath = $schema_path;
  }

  /**
   * Get an entity's term definition in this vocabulary.
   */
  public function entity($entity_type) {
    return new EntitySchema($this, $entity_type);
  }

  /**
   * Get a bundle's term definition in this vocabulary.
   */
  public function bundle($entity_type, $bundle) {
    return new BundleSchema($this, $entity_type, $bundle);
  }

  /**
   * Get the URI of the schema.
   *
   * @return string
   *   The URI of the schema.
   */
  public function getUri() {
    return url($this->schemaPath, array('absolute' => TRUE));
  }

  /**
   * Get the relative base path of the schema.
   */
  public function getPath() {
    return $this->schemaPath;
  }

  /**
   * Get the routes for the types of terms defined in this schema.
   *
   * @return array
   *   An array of route patterns, keyed by controller method name.
   */
  public function getRoutes() {
    return array(
      'bundle' => $this->schemaPath . BundleSchema::$uriPattern,
    );
  }
}
