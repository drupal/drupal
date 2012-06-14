<?php

/**
 * @file
 * Definition of Drupal\rdf\Tests\MappingHookTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the RDF mapping hook.
 */
class MappingHookTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'RDF mapping hook',
      'description' => 'Test hook_rdf_mapping().',
      'group' => 'RDF',
    );
  }

  function setUp() {
    parent::setUp('rdf', 'rdf_test', 'field_test');
  }

  /**
   * Test that hook_rdf_mapping() correctly returns and processes mapping.
   */
  function testMapping() {
    // Test that the mapping is returned correctly by the hook.
    $mapping = rdf_mapping_load('test_entity', 'test_bundle');
    $this->assertIdentical($mapping['rdftype'], array('sioc:Post'), t('Mapping for rdftype is sioc:Post.'));
    $this->assertIdentical($mapping['title'], array('predicates' => array('dc:title')), t('Mapping for title is dc:title.'));
    $this->assertIdentical($mapping['created'], array(
      'predicates' => array('dc:created'),
      'datatype' => 'xsd:dateTime',
      'callback' => 'date_iso8601',
    ), t('Mapping for created is dc:created with datatype xsd:dateTime and callback date_iso8601.'));
    $this->assertIdentical($mapping['uid'], array('predicates' => array('sioc:has_creator', 'dc:creator'), 'type' => 'rel'), t('Mapping for uid is sioc:has_creator and dc:creator, and type is rel.'));

    $mapping = rdf_mapping_load('test_entity', 'test_bundle_no_mapping');
    $this->assertEqual($mapping, array(), t('Empty array returned when an entity type, bundle pair has no mapping.'));
  }
}
