<?php
// $Id$

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
 * @} End of "addtogroup hooks".
 */
