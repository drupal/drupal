<?php

/**
 * @file
 * Contains EntitySchema.
 */

namespace Drupal\rdf\SiteSchema;

use Drupal\rdf\RdfConstants;
use Drupal\rdf\SiteSchema\SchemaTermBase;

/**
 * Defines RDF terms corresponding to Drupal entity types.
 */
class EntitySchema extends SchemaTermBase {

  /**
   * The URI pattern for entity site schema terms.
   *
   * @var string
   */
  public static $uriPattern = '{entity_type}';

  /**
   * The entity type that this term identifies.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Constructor.
   *
   * @param \Drupal\rdf\SiteSchema\SiteSchema $site_schema
   *   The schema the term is defined in.
   * @param string $entity_type
   *   The entity type.
   */
  public function __construct($site_schema, $entity_type) {
    parent::__construct($site_schema);
    $this->entityType = $entity_type;
  }

  /**
   * Implements \Drupal\rdf\SiteSchema\SchemaTermInterface::getGraph().
   *
   * @todo Loop through all fields and add their RDF descriptions.
   */
  public function getGraph() {
    $graph = array();
    $graph[$this->getUri()] = $this->getProperties();
    return $graph;
  }

  /**
   * Implements \Drupal\rdf\SiteSchema\SchemaTermInterface::getUri().
   */
  public function getUri() {
    $path = str_replace('{entity_type}', $this->entityType , static::$uriPattern);
    return $this->siteSchema->getUri() . '/' . $path;
  }

  /**
   * Overrides \Drupal\rdf\SiteSchema\SchemaTermBase::getProperties().
   */
  public function getProperties() {
    $properties = parent::getProperties();
    $properties[RdfConstants::RDF_TYPE] = RdfConstants::RDFS_CLASS;
    return $properties;
  }

}
