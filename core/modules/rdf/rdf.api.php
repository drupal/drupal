<?php

/**
 * @file
 * Hooks provided by the RDF module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow modules to define namespaces for RDF mappings.
 *
 * Many common namespace prefixes are defined in rdf_rdf_namespaces(). However,
 * if a module implements rdf mappings that use prefixes that are not
 * defined in rdf_rdf_namespaces(), this hook should be used to define the new
 * namespace prefixes.
 *
 * @return string[]
 *   An associative array of namespaces where the key is the namespace prefix
 *   and the value is the namespace URI.
 *
 * @ingroup rdf
 */
function hook_rdf_namespaces() {
  return [
    'content'  => 'http://purl.org/rss/1.0/modules/content/',
    'dc'       => 'http://purl.org/dc/terms/',
    'foaf'     => 'http://xmlns.com/foaf/0.1/',
    'og'       => 'http://ogp.me/ns#',
    'rdfs'     => 'http://www.w3.org/2000/01/rdf-schema#',
    'sioc'     => 'http://rdfs.org/sioc/ns#',
    'sioct'    => 'http://rdfs.org/sioc/types#',
    'skos'     => 'http://www.w3.org/2004/02/skos/core#',
    'xsd'      => 'http://www.w3.org/2001/XMLSchema#',
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
