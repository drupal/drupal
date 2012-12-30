<?php

/**
 * @file
 * Contains SchemaTermBase.
 */

namespace Drupal\rdf\SiteSchema;

use Drupal\rdf\RdfConstants;

/**
 * Base class to define an RDF term in a schema.
 */
abstract class SchemaTermBase implements SchemaTermInterface {

  /**
   * The URI pattern for this type of site schema term.
   *
   * @var string
   */
  public static $uriPattern;

  /**
   * The schema in which this term is defined.
   *
   * @var \Drupal\rdf\SiteSchema\SiteSchema
   */
  protected $siteSchema;

  /**
   * Constructor.
   *
   * @param \Drupal\rdf\SiteSchema\SiteSchema $site_schema
   *   The namespace.
   */
  public function __construct($site_schema) {
    $this->siteSchema = $site_schema;
  }

  /**
   * Implements \Drupal\rdf\SiteSchema\SchemaTermInterface::getProperties().
   */
  public function getProperties() {
    return array(
      RdfConstants::RDFS_IS_DEFINED_BY => $this->siteSchema->getUri(),
    );
  }

}
