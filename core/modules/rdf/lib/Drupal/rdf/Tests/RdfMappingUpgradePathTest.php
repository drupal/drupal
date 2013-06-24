<?php

/**
 * @file
 * Contains \Drupal\rdf\Tests\RdfMappingUpgradePathTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\system\Tests\Upgrade\UpgradePathTestBase;

/**
 * Tests the upgrade path of RDF mappings.
 */
class RdfMappingUpgradePathTest extends UpgradePathTestBase {

  public static function getInfo() {
    return array(
      'name' => 'RDF mapping upgrade test',
      'description' => 'Upgrade tests with RDF mapping data.',
      'group' => 'RDF',
    );
  }

  public function setUp() {
    // Path to the database dump files.
    $this->databaseDumpFiles = array(
      drupal_get_path('module', 'system') . '/tests/upgrade/drupal-7.bare.standard_all.database.php.gz',
      drupal_get_path('module', 'rdf') . '/tests/drupal-7.rdf.database.php',
    );
    parent::setUp();
  }

  /**
   * Tests to see if RDF mappings were upgraded.
   */
  public function testRdfMappingUpgrade() {
    $this->assertTrue(db_table_exists('rdf_mapping'), 'RDF mapping table exists.');
    $this->assertTrue($this->performUpgrade(), 'The upgrade was completed successfully.');

    $this->_testUnalteredMappingUpgrade();
    $this->_testAlteredMappingUpgrade();
    $this->_testReverseRelationUpgrade();

  }

  /**
   * Helper function to test upgrade of unaltered mappings.
   */
  protected function _testUnalteredMappingUpgrade() {
    $config = rdf_get_mapping('node', 'page');

    // Test bundle mapping.
    $mapping = $config->getBundleMapping();
    $expected_mapping = array(
      'types' => array('foaf:Document'),
    );
    $this->assertEqual($mapping, $expected_mapping, 'Unaltered bundle mapping upgraded correctly.');

    // Test field mapping - property.
    $mapping = $config->getFieldMapping('title');
    $expected_mapping = array(
      'properties' => array('dc:title'),
    );
    $this->assertEqual($mapping, $expected_mapping, 'Unaltered field mapping upgraded correctly.');

    // Test field mapping - property with datatype and callback.
    $mapping = $config->getFieldMapping('created');
    $expected_mapping = array(
      'properties' => array('dc:date', 'dc:created'),
      'datatype' => 'xsd:dateTime',
      'datatype_callback' => 'date_iso8601',
    );
    $this->assertEqual($mapping, $expected_mapping, 'Unaltered field mapping with datatype and datatype callback upgraded correctly.');

    // Test field mapping - rel.
    $mapping = $config->getFieldMapping('uid');
    $expected_mapping = array(
      'properties' => array('sioc:has_creator'),
      'mapping_type' => 'rel',
    );
    $this->assertEqual($mapping, $expected_mapping, 'Unaltered field mapping with rel mapping type upgraded correctly.');
  }

  /**
   * Helper function to test upgrade of altered mappings.
   */
  protected function _testAlteredMappingUpgrade() {
    $config = rdf_get_mapping('node', 'article');

    // Test bundle mapping.
    $mapping = $config->getBundleMapping();
    $expected_mapping = array(
      'types' => array('foo:Type'),
    );
    $this->assertEqual($mapping, $expected_mapping, 'Overriden bundle mapping upgraded correctly.');

    // Test field mapping.
    $mapping = $config->getFieldMapping('field_image');
    $expected_mapping = array(
      'properties' => array('foo:image'),
    );
    $this->assertEqual($expected_mapping, $mapping, 'Overriden field mapping is upgraded correctly.');

    // Test field mapping.
    $mapping = $config->getFieldMapping('changed');
    $expected_mapping = array();
    $this->assertEqual($expected_mapping, $mapping, 'Empty field mapping from overriden mapping is upgraded correctly.');
  }

  /**
   * Helper function to test handling of deprecated reverse relation mappings.
   */
  protected function _testReverseRelationUpgrade() {
    // Test field mapping - rev.
    $config = rdf_get_mapping('node', 'rev_test');
    $mapping = $config->getFieldMapping('field_rev');
    $expected_mapping = array();
    $this->assertEqual($mapping, $expected_mapping, 'Reverse relation mapping has been dropped.');
  }

}
