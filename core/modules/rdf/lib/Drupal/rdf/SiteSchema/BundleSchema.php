<?php

/**
 * @file
 * Contains BundleSchema.
 */

namespace Drupal\rdf\SiteSchema;

use Drupal\rdf\RdfConstants;
use Drupal\rdf\SiteSchema\EntitySchema;

/**
 * Defines RDF terms corresponding to Drupal bundles.
 */
class BundleSchema extends EntitySchema {

  /**
   * The URI pattern for bundle site schema terms.
   *
   * @var string
   */
  public static $uriPattern = '{entity_type}/{bundle}';

  /**
   * The bundle that this term identifies.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Constructor.
   *
   * @param \Drupal\rdf\SiteSchema\SiteSchema $site_schema
   *   The schema the term is defined in.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   */
  public function __construct($site_schema, $entity_type, $bundle) {
    parent::__construct($site_schema, $entity_type);
    $this->bundle = $bundle;
  }

  /**
   * Implements \Drupal\rdf\SiteSchema\SchemaTermInterface::getUri().
   */
  public function getUri() {
    $path = str_replace(array('{entity_type}', '{bundle}'), array($this->entityType, $this->bundle), static::$uriPattern);
    return $this->siteSchema->getUri() . '/' . $path;
  }

  /**
   * Overrides \Drupal\rdf\SiteSchema\SchemaTermBase::getProperties().
   */
  public function getProperties() {
    $properties = parent::getProperties();
    $properties[RdfConstants::RDFS_SUB_CLASS_OF] = $this->siteSchema->entity($this->entityType)->getUri();
    return $properties;
  }

}
