<?php

/**
 * @file
 * Contains \Drupal\rdf\Tests\RdfaAttributesTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests RDFa attribute generation.
 */
class RdfaAttributesTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rdf');

  public static function getInfo() {
    return array(
      'name' => 'RDFa attributes',
      'description' => 'Test RDFa attribute generation from RDF mapping.',
      'group' => 'RDF',
    );
  }

  /**
   * Test attribute creation for mappings which use 'property'.
   */
  function testProperty() {
    $properties = array('dc:title');

    $mapping = array('properties' => $properties);
    $expected_attributes = array('property' => $properties);

    $this->_testAttributes($expected_attributes, $mapping);
  }

  /**
   * Test attribute creation for mappings which use 'datatype'.
   */
  function testDatatype() {
    $properties = array('foo:bar1');
    $datatype = 'foo:bar1type';

    $mapping = array(
      'datatype' => $datatype,
      'properties' => $properties,
    );
    $expected_attributes = array(
      'datatype' => $datatype,
      'property' => $properties,
    );

    $this->_testAttributes($expected_attributes, $mapping);
  }

  /**
   * Test attribute creation for mappings which override human-readable content.
   */
  function testDatatypeCallback() {
    $properties = array('dc:created');
    $datatype = 'xsd:dateTime';

    $date = 1252750327;
    $iso_date = date('c', $date);

    $mapping = array(
      'datatype' => $datatype,
      'properties' => $properties,
      'datatype_callback' => array('callable' => 'date_iso8601'),
    );
    $expected_attributes = array(
      'datatype' => $datatype,
      'property' => $properties,
      'content' => $iso_date,
    );

    $this->_testAttributes($expected_attributes, $mapping, $date);
  }


  /**
   * Test attribute creation for mappings which use data converters.
   */
  function testDatatypeCallbackWithConverter() {
    $properties = array('schema:interactionCount');

    $data = "23";
    $content = "UserComments:23";

    $mapping = array(
      'properties' => $properties,
      'datatype_callback' => array(
        'callable' => 'Drupal\rdf\SchemaOrgDataConverter::interactionCount',
        'arguments' => array('interaction_type' => 'UserComments'),
      ),
    );
    $expected_attributes = array(
      'property' => $properties,
      'content' => $content,
    );

    $this->_testAttributes($expected_attributes, $mapping, $data);
  }

  /**
   * Test attribute creation for mappings which use 'rel'.
   */
  function testRel() {
    $properties = array('sioc:has_creator', 'dc:creator');

    $mapping = array(
      'properties' => $properties,
      'mapping_type' => 'rel',
    );
    $expected_attributes = array('rel' => $properties);

    $this->_testAttributes($expected_attributes, $mapping);
  }

  /**
   * Helper function to test attribute generation.
   *
   * @param array $expected_attributes
   *   The expected return of rdf_rdfa_attributes.
   * @param array $field_mapping
   *   The field mapping to merge into the RDF mapping config.
   * @param mixed $data
   *   The data to pass into the datatype callback, if specified.
   */
  protected function _testAttributes($expected_attributes, $field_mapping, $data = NULL) {
    $mapping = rdf_get_mapping('node', 'article')
      ->setFieldMapping('field_test', $field_mapping)
      ->getPreparedFieldMapping('field_test');
    $attributes = rdf_rdfa_attributes($mapping, $data);
    ksort($expected_attributes);
    ksort($attributes);
    $this->assertEqual($expected_attributes, $attributes);
  }

}
