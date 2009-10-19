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
 * Allow modules to define RDF mappings for bundles.
 *
 * Modules defining their own bundles can specify which RDF semantics should be
 * used to annotate these bundles. These mappings are then used for automatic
 * RDFa output in the HTML code.
 *
 * @return
 *   An array of mapping structures. Each mapping has three mandatory keys:
 *   - type: The name of an entity type.
 *   - bundle: The name of a bundle.
 *   - mapping: The mapping structure which applies to the entity type, bundle
 *   pair. A mapping structure is an array with keys corresponding to
 *   existing field instances in the bundle. Each field is then described in
 *   terms of RDF mapping. 'predicates' is an array of RDF predicates which
 *   describe the relation between the bundle (subject in RDF) and the value of
 *   the field (object in RDF), this value being either some text, another
 *   bundle or a URL in general. 'datatype' and 'callback' are used in RDFa to
 *   format data so that it's readable by machine: a typical example is a date
 *   which can be written in many different formats but should be translated
 *   into a uniform format for machine comsumption. 'type' is a string used to
 *   determine the type of RDFa markup which will be used in the final HTML
 *   output, depending on whether the RDF object is a literal text or another
 *   RDF resource. The 'rdftype' key is a special case which is used to define
 *   the type of the instance, its value shoud be an array of RDF classes.
 */
function hook_rdf_mapping() {
  return array(
    array(
      'type' => 'node',
      'bundle' => 'blog',
      'mapping' => array(
        'rdftype' => array('sioct:Weblog'),
        'title'   => array(
          'predicates' => array('dc:title'),
        ),
        'created' => array(
          'predicates' => array('dc:date', 'dc:created'),
          'datatype' => 'xsd:dateTime',
          'callback' => 'date_iso8601',
        ),
        'body'    => array(
          'predicates' => array('content:encoded'),
        ),
        'uid'     => array(
          'predicates' => array('sioc:has_creator'),
          'type'     => 'rel',
        ),
        'name'    => array(
          'predicates' => array('foaf:name'),
        ),
      )
    ),
  );
}

/**
 * @} End of "addtogroup hooks".
 */
