<?php
// $Id: rdf.api.php,v 1.4 2010/03/06 06:40:35 dries Exp $

/**
 * @file
 * Hooks provided by the RDF module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow modules to define RDF mappings for field bundles.
 *
 * Modules defining their own field bundles can specify which RDF semantics
 * should be used to annotate these bundles. These mappings are then used for
 * automatic RDFa output in the HTML code.
 *
 * @return
 *   A list of mapping structures, where each mapping is an associative array:
 *   - type: The name of an entity type, e.g. 'node' or 'comment'.
 *   - bundle: The name of the bundle, e.g. 'blog', or RDF_DEFAULT_BUNDLE for
 *     default mappings.
 *   - mapping: The mapping structure which applies to the entity type and
 *     bundle. A mapping structure is an array with keys corresponding to
 *     existing field instances in the bundle. Each field is then described in
 *     terms of RDF mapping:
 *     - predicates: An array of RDF predicates which describe the relation
 *       between the bundle (RDF subject) and the value of the field (RDF
 *       object). This value is either some text, another bundle or a URL in
 *       general.
 *     - datatype: Is used along with 'callback' to format data so that it is
 *       readable by machine. A typical example is a date which can be written
 *       in many different formats but should be translated into a uniform
 *       format for machine consumption.
 *     - callback: A function name to invoke for 'datatype'.
 *     - type: A string used to determine the type of RDFa markup which will be
 *       used in the final HTML output, depending on whether the RDF object is a
 *       literal text or another RDF resource.
 *     - rdftype: A special property used to define the type of the instance.
 *       Its value should be an array of RDF classes.
 *
 *       @ingroup rdf
 */
function hook_rdf_mapping() {
  return array(
    array(
      'type' => 'node',
      'bundle' => 'blog',
      'mapping' => array(
        'rdftype' => array('sioct:Weblog'),
        'title' => array(
          'predicates' => array('dc:title'),
        ),
        'created' => array(
          'predicates' => array('dc:date', 'dc:created'),
          'datatype' => 'xsd:dateTime',
          'callback' => 'date_iso8601',
        ),
        'body' => array(
          'predicates' => array('content:encoded'),
        ),
        'uid' => array(
          'predicates' => array('sioc:has_creator'),
          'type' => 'rel',
        ),
        'name' => array(
          'predicates' => array('foaf:name'),
        ),
      ),
    ),
  );
}

/**
 * Allow modules to define namespaces for RDF mappings.
 *
 * Many common namespace prefixes are defined in rdf_rdf_namespaces(). However,
 * if a module implements hook_rdf_mapping() and uses a prefix that is not
 * defined in rdf_rdf_namespaces(), this hook should be used to define the new
 * namespace prefix.
 *
 * @return
 *   An associative array of namespaces where the key is the namespace prefix
 *   and the value is the namespace URI.
 *
 * @ingroup rdf
 */
function hook_rdf_namespaces() {
  return array(
    'admin'    => 'http://webns.net/mvcb/',
    'content'  => 'http://purl.org/rss/1.0/modules/content/',
    'dc'       => 'http://purl.org/dc/terms/',
    'foaf'     => 'http://xmlns.com/foaf/0.1/',
    'owl'      => 'http://www.w3.org/2002/07/owl#',
    'rdf'      => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
    'rdfs'     => 'http://www.w3.org/2000/01/rdf-schema#',
    'rss'      => 'http://purl.org/rss/1.0/',
    'tags'     => 'http://www.holygoat.co.uk/owl/redwood/0.1/tags/',
    'sioc'     => 'http://rdfs.org/sioc/ns#',
    'sioct'    => 'http://rdfs.org/sioc/types#',
    'ctag'     => 'http://commontag.org/ns#',
    'skos'     => 'http://www.w3.org/2004/02/skos/core#',
    'xsd'      => 'http://www.w3.org/2001/XMLSchema#',
  );
}

/**
 * @} End of "addtogroup hooks".
 */
