<?php

/**
 * @file
 * Definition of Drupal\rdf\Tests\CrudTest.
 */

namespace Drupal\rdf\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the RDF mapping CRUD functions.
 */
class CrudTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'RDF mapping CRUD functions',
      'description' => 'Test the RDF mapping CRUD functions.',
      'group' => 'RDF',
    );
  }

  function setUp() {
    parent::setUp('rdf', 'rdf_test');
  }

  /**
   * Test inserting, loading, updating, and deleting RDF mappings.
   */
  function testCRUD() {
    // Verify loading of a default mapping.
    $mapping = _rdf_mapping_load('test_entity', 'test_bundle');
    $this->assertTrue(count($mapping), t('Default mapping was found.'));

    // Verify saving a mapping.
    $mapping = array(
      'type' => 'crud_test_entity',
      'bundle' => 'crud_test_bundle',
      'mapping' => array(
        'rdftype' => array('sioc:Post'),
        'title' => array(
          'predicates' => array('dc:title'),
        ),
        'uid' => array(
          'predicates' => array('sioc:has_creator', 'dc:creator'),
          'type' => 'rel',
        ),
      ),
    );
    $this->assertTrue(rdf_mapping_save($mapping) === SAVED_NEW, t('Mapping was saved.'));

    // Read the raw record from the {rdf_mapping} table.
    $result = db_query('SELECT * FROM {rdf_mapping} WHERE type = :type AND bundle = :bundle', array(':type' => $mapping['type'], ':bundle' => $mapping['bundle']));
    $stored_mapping = $result->fetchAssoc();
    $stored_mapping['mapping'] = unserialize($stored_mapping['mapping']);
    $this->assertEqual($mapping, $stored_mapping, t('Mapping was stored properly in the {rdf_mapping} table.'));

    // Verify loading of saved mapping.
    $this->assertEqual($mapping['mapping'], _rdf_mapping_load($mapping['type'], $mapping['bundle']), t('Saved mapping loaded successfully.'));

    // Verify updating of mapping.
    $mapping['mapping']['title'] = array(
      'predicates' => array('dc2:bar2'),
    );
    $this->assertTrue(rdf_mapping_save($mapping) === SAVED_UPDATED, t('Mapping was updated.'));

    // Read the raw record from the {rdf_mapping} table.
    $result = db_query('SELECT * FROM {rdf_mapping} WHERE type = :type AND bundle = :bundle', array(':type' => $mapping['type'], ':bundle' => $mapping['bundle']));
    $stored_mapping = $result->fetchAssoc();
    $stored_mapping['mapping'] = unserialize($stored_mapping['mapping']);
    $this->assertEqual($mapping, $stored_mapping, t('Updated mapping was stored properly in the {rdf_mapping} table.'));

    // Verify loading of saved mapping.
    $this->assertEqual($mapping['mapping'], _rdf_mapping_load($mapping['type'], $mapping['bundle']), t('Saved mapping loaded successfully.'));

    // Verify deleting of mapping.
    $this->assertTrue(rdf_mapping_delete($mapping['type'], $mapping['bundle']), t('Mapping was deleted.'));
    $this->assertFalse(_rdf_mapping_load($mapping['type'], $mapping['bundle']), t('Deleted mapping is no longer found in the database.'));
  }
}
