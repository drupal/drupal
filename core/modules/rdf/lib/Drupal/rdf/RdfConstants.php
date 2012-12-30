<?php

/**
 * @file
 * Contains RdfConstants.
 */

namespace Drupal\rdf;

/**
 * Defines constants for RDF terms.
 */
abstract class RdfConstants {
  const RDF_TYPE            = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
  // RDF Schema terms.
  const RDFS_CLASS          = 'http://www.w3.org/2000/01/rdf-schema#class';
  const RDFS_DOMAIN         = 'http://www.w3.org/2000/01/rdf-schema#domain';
  const RDFS_IS_DEFINED_BY  = 'http://www.w3.org/2000/01/rdf-schema#isDefinedBy';
  const RDFS_RANGE          = 'http://www.w3.org/2000/01/rdf-schema#range';
  const RDFS_SUB_CLASS_OF   = 'http://www.w3.org/2000/01/rdf-schema#subClassOf';
  // XSD datatypes.
  const XSD_INTEGER         = 'http://www.w3.org/2001/XMLSchema#integer';
  const XSD_DOUBLE          = 'http://www.w3.org/2001/XMLSchema#double';
  const XSD_BOOLEAN         = 'http://www.w3.org/2001/XMLSchema#boolean';
  const XSD_STRING          = 'http://www.w3.org/2001/XMLSchema#string';
}
