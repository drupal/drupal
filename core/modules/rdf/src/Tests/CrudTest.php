<?php

/**
 * @file
 * Definition of Drupal\rdf\Tests\CrudTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the RDF mapping CRUD functions.
 *
 * @group rdf
 */
class CrudTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test', 'rdf', 'system');

  /**
   * @var string
   */
  protected $prefix;

  /**
   * @var string
   */
  protected $entity_type;

  /**
   * @var string
   */
  protected $bundle;

  protected function setUp() {
    parent::setUp();
    $this->prefix = 'rdf.mapping';
    $this->entity_type = $this->bundle = 'entity_test';
  }

  /**
   * Tests creation of RDF mapping.
   */
  function testMappingCreation() {
    $mapping_config_name = "{$this->prefix}.{$this->entity_type}.{$this->bundle}";

    // Save bundle mapping config.
    rdf_get_mapping($this->entity_type, $this->bundle)->save();
    // Test that config file was saved.
    $mapping_config = \Drupal::configFactory()->listAll('rdf.mapping.');
    $this->assertTrue(in_array($mapping_config_name, $mapping_config), 'Rdf mapping config saved.');
  }

  /**
   * Test the handling of bundle mappings.
   */
  function testBundleMapping() {
    // Test that the bundle mapping can be saved.
    $types = array('sioc:Post', 'foaf:Document');
    rdf_get_mapping($this->entity_type, $this->bundle)
      ->setBundleMapping(array('types' => $types))
      ->save();
    $bundle_mapping = rdf_get_mapping($this->entity_type, $this->bundle)
      ->getBundleMapping();
    $this->assertEqual($types, $bundle_mapping['types'], 'Bundle mapping saved.');

    // Test that the bundle mapping can be edited.
    $types = array('schema:BlogPosting');
    rdf_get_mapping($this->entity_type, $this->bundle)
      ->setBundleMapping(array('types' => $types))
      ->save();
    $bundle_mapping = rdf_get_mapping($this->entity_type, $this->bundle)
      ->getBundleMapping();
    $this->assertEqual($types, $bundle_mapping['types'], 'Bundle mapping updated.');
  }

  /**
   * Test the handling of field mappings.
   */
  function testFieldMapping() {
    $field_name = 'created';

    // Test that the field mapping can be saved.
    $mapping = array(
      'properties' => array('dc:created'),
      'datatype' => 'xsd:dateTime',
      'datatype_callback' => array('callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value'),
    );
    rdf_get_mapping($this->entity_type, $this->bundle)
      ->setFieldMapping($field_name, $mapping)
      ->save();
    $field_mapping = rdf_get_mapping($this->entity_type, $this->bundle)
      ->getFieldMapping($field_name);
    $this->assertEqual($mapping, $field_mapping, 'Field mapping saved.');

    // Test that the field mapping can be edited.
    $mapping = array(
      'properties' => array('dc:date'),
      'datatype' => 'foo:bar',
      'datatype_callback' => array('callable' => 'Drupal\rdf\CommonDataConverter::dateIso8601Value'),
    );
    rdf_get_mapping($this->entity_type, $this->bundle)
      ->setFieldMapping($field_name, $mapping)
      ->save();
    $field_mapping = rdf_get_mapping($this->entity_type, $this->bundle)
      ->getFieldMapping($field_name);
    $this->assertEqual($mapping, $field_mapping, 'Field mapping updated.');
  }
}
