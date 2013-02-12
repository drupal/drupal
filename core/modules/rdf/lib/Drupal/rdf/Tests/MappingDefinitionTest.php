<?php

/**
 * @file
 * Contains Drupal\rdf\Tests\MappingDefinitionTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\node\Tests\NodeTestBase;

/**
 * Tests the RDF mapping definition functionality.
 */
class MappingDefinitionTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rdf', 'rdf_test');

  public static function getInfo() {
    return array(
      'name' => 'RDF mapping definition functionality',
      'description' => 'Tests that RDF definitions are properly attached to entities.',
      'group' => 'RDF',
    );
  }

  function setUp() {
    parent::setUp();
    // NodeTestBase creates page content type for us.
    // Defines RDF mapping for page content type.
    $page_rdf_mapping = array(
      'type' => 'node',
      'bundle' => 'page',
      'mapping' => array(
        'rdftype' => array('foaf:DocumentBar'),
        'body' => array(
          'predicates' => array('dc:dummy-property'),
        ),
        'created' => array(
          'predicates' => array('dc:dummy-date'),
          'callback' => 'date_iso8601_foo',
          'datatype' => 'xsd:dateTimeFoo',
        ),
      ),
    );
    rdf_mapping_save($page_rdf_mapping);
  }

  /**
   * Creates a node of type page and tests whether the RDF mapping is
   * attached to the node.
   */
  function testMappingDefinitionPage() {
    $node = $this->drupalCreateNode(array('type' => 'page'));

    $expected_mapping = array(
      'rdftype' => array('foaf:DocumentBar'),
      'title' => array(
        'predicates' => array('dc:title'),
      ),
      'body' => array(
        'predicates' => array('dc:dummy-property'),
      ),
      'created' => array(
        'predicates' => array('dc:dummy-date'),
        'callback' => 'date_iso8601_foo',
        'datatype' => 'xsd:dateTimeFoo',
      ),
    );
    $node = node_load($node->nid);
    foreach ($expected_mapping as $key => $mapping) {
      $this->assertEqual($node->rdf_mapping[$key], $mapping, format_string('Expected mapping found for @key.', array('@key' => $key)));
    }
  }

  /**
   * Creates a content type and a node of type test_bundle_hook_install and
   * tests whether the RDF mapping defined in rdf_test.install is used.
   */
  function testMappingDefinitionTestBundleInstall() {
    $this->drupalCreateContentType(array('type' => 'test_bundle_hook_install'));
    $node = $this->drupalCreateNode(array('type' => 'test_bundle_hook_install'));

    $expected_mapping = array(
      'rdftype' => array('foo:mapping_install1', 'bar:mapping_install2'),
      'title' => array(
        'predicates' => array('dc:title'),
      ),
      'body' => array(
        'predicates' => array('content:encoded'),
      ),
      'created' => array(
        'predicates' => array('dc:date', 'dc:created'),
        'callback' => 'date_iso8601',
        'datatype' => 'xsd:dateTime',
      ),
    );
    $node = node_load($node->nid);
    foreach ($expected_mapping as $key => $mapping) {
      $this->assertEqual($node->rdf_mapping[$key], $mapping, format_string('Expected mapping found for @key.', array('@key' => $key)));
    }
  }

  /**
   * Creates a random content type and node and ensures the default mapping for
   * the node is being used.
   */
  function testMappingDefinitionRandomContentType() {
    $type = $this->drupalCreateContentType();
    $node = $this->drupalCreateNode(array('type' => $type->type));
    $expected_mapping = array(
      'rdftype' => array('sioc:Item', 'foaf:Document'),
      'title' => array(
        'predicates' => array('dc:title'),
      ),
      'body' => array(
        'predicates' => array('content:encoded'),
      ),
      'created' => array(
        'predicates' => array('dc:date', 'dc:created'),
        'callback' => 'date_iso8601',
        'datatype' => 'xsd:dateTime',
      ),
    );
    $node = node_load($node->nid);
    foreach ($expected_mapping as $key => $mapping) {
      $this->assertEqual($node->rdf_mapping[$key], $mapping, format_string('Expected mapping found for @key.', array('@key' => $key)));
    }
  }
}
