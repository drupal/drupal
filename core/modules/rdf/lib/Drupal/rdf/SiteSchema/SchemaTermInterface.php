<?php

/**
 * @file
 * Contains SchemaTermInterface.
 */

namespace Drupal\rdf\SiteSchema;

interface SchemaTermInterface {

  /**
   * Get the full graph of terms and properties to display.
   *
   * When an RDF term URI is dereferenced, it usually contains a description of
   * the term in RDF. To make it easier to use this description, include
   * information about all related terms. For example, when viewing the RDF
   * description for the RDF class which corresponds to a Drupal bundle, data
   * about its fields would also be included.
   *
   * @return array
   *   An array of terms and their properties, keyed by term URI.
   */
  public function getGraph();

  /**
   * Get the term properties.
   *
   * @return array
   *   An array of properties for this term, keyed by URI.
   */
  public function getProperties();

  /**
   * Get the URI of the term.
   *
   * Implementations of this method will use the URI patterns defined in
   * $uriPattern static variables and replace placeholders with actual values.
   *
   * @return string
   *   The URI of the term.
   */
  public function getUri();
}
