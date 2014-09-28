<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\VocabularyCrudTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\field\Entity\FieldConfig;

/**
 * Tests loading, saving and deleting vocabularies.
 *
 * @group taxonomy
 */
class VocabularyCrudTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test', 'taxonomy_crud');

  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(array('create article content', 'administer taxonomy'));
    $this->drupalLogin($admin_user);
    $this->vocabulary = $this->createVocabulary();
  }

  /**
   * Test deleting a taxonomy that contains terms.
   */
  function testTaxonomyVocabularyDeleteWithTerms() {
    // Delete any existing vocabularies.
    foreach (entity_load_multiple('taxonomy_vocabulary') as $vocabulary) {
      $vocabulary->delete();
    }

    // Assert that there are no terms left.
    $this->assertEqual(0, db_query('SELECT COUNT(*) FROM {taxonomy_term_data}')->fetchField(), 'There are no terms remaining.');

    $terms = array();
    for ($i = 0; $i < 5; $i++) {
      $terms[$i] = $this->createTerm($vocabulary);
    }

    // Set up hierarchy. term 2 is a child of 1 and 4 a child of 1 and 2.
    $terms[2]->parent = array($terms[1]->id());
    $terms[2]->save();
    $terms[4]->parent = array($terms[1]->id(), $terms[2]->id());
    $terms[4]->save();

    // Assert that there are now 5 terms.
    $this->assertEqual(5, db_query('SELECT COUNT(*) FROM {taxonomy_term_data}')->fetchField(), 'There are 5 terms found.');

    $vocabulary->delete();

    // Assert that there are no terms left.
    $this->assertEqual(0, db_query('SELECT COUNT(*) FROM {taxonomy_term_data}')->fetchField(), 'All terms are deleted.');
  }

  /**
   * Ensure that the vocabulary static reset works correctly.
   */
  function testTaxonomyVocabularyLoadStaticReset() {
    $original_vocabulary = entity_load('taxonomy_vocabulary', $this->vocabulary->id());
    $this->assertTrue(is_object($original_vocabulary), 'Vocabulary loaded successfully.');
    $this->assertEqual($this->vocabulary->name, $original_vocabulary->name, 'Vocabulary loaded successfully.');

    // Change the name and description.
    $vocabulary = $original_vocabulary;
    $vocabulary->name = $this->randomMachineName();
    $vocabulary->description = $this->randomMachineName();
    $vocabulary->save();

    // Load the vocabulary.
    $new_vocabulary = entity_load('taxonomy_vocabulary', $original_vocabulary->id());
    $this->assertEqual($new_vocabulary->name, $vocabulary->name, 'The vocabulary was loaded.');

    // Delete the vocabulary.
    $this->vocabulary->delete();
    $vocabularies = entity_load_multiple('taxonomy_vocabulary');
    $this->assertTrue(!isset($vocabularies[$this->vocabulary->id()]), 'The vocabulary was deleted.');
  }

  /**
   * Tests for loading multiple vocabularies.
   */
  function testTaxonomyVocabularyLoadMultiple() {

    // Delete any existing vocabularies.
    foreach (entity_load_multiple('taxonomy_vocabulary') as $vocabulary) {
      $vocabulary->delete();
    }

    // Create some vocabularies and assign weights.
    $vocabulary1 = $this->createVocabulary();
    $vocabulary1->weight = 0;
    $vocabulary1->save();
    $vocabulary2 = $this->createVocabulary();
    $vocabulary2->weight = 1;
    $vocabulary2->save();
    $vocabulary3 = $this->createVocabulary();
    $vocabulary3->weight = 2;
    $vocabulary3->save();

    // Check if third party settings exist.
    $this->assertEqual('bar', $vocabulary1->getThirdPartySetting('taxonomy_crud', 'foo'), 'Third party settings were added to the vocabulary.');
    $this->assertEqual('bar', $vocabulary2->getThirdPartySetting('taxonomy_crud', 'foo'), 'Third party settings were added to the vocabulary.');
    $this->assertEqual('bar', $vocabulary3->getThirdPartySetting('taxonomy_crud', 'foo'), 'Third party settings were added to the vocabulary.');

    // Fetch the names for all vocabularies, confirm that they are keyed by
    // machine name.
    $names = taxonomy_vocabulary_get_names();
    $this->assertEqual($names[$vocabulary1->id()], $vocabulary1->id(), 'Vocabulary 1 name found.');

    // Fetch the vocabularies with entity_load_multiple(), specifying IDs.
    // Ensure they are returned in the same order as the original array.
    $vocabularies = entity_load_multiple('taxonomy_vocabulary', array($vocabulary3->id(), $vocabulary2->id(), $vocabulary1->id()));
    $loaded_order = array_keys($vocabularies);
    $expected_order = array($vocabulary3->id(), $vocabulary2->id(), $vocabulary1->id());
    $this->assertIdentical($loaded_order, $expected_order);

    // Test loading vocabularies by their properties.
    $controller = $this->container->get('entity.manager')->getStorage('taxonomy_vocabulary');
    // Fetch vocabulary 1 by name.
    $vocabulary = current($controller->loadByProperties(array('name' => $vocabulary1->name)));
    $this->assertEqual($vocabulary->id(), $vocabulary1->id(), 'Vocabulary loaded successfully by name.');

    // Fetch vocabulary 2 by name and ID.
    $vocabulary = current($controller->loadByProperties(array(
      'name' => $vocabulary2->name,
      'vid' => $vocabulary2->id(),
    )));
    $this->assertEqual($vocabulary->id(), $vocabulary2->id(), 'Vocabulary loaded successfully by name and ID.');
  }

  /**
   * Tests that machine name changes are properly reflected.
   */
  function testTaxonomyVocabularyChangeMachineName() {
    // Add a field to the vocabulary.
    entity_create('field_storage_config', array(
      'field_name' => 'field_test',
      'entity_type' => 'taxonomy_term',
      'type' => 'test_field',
    ))->save();
    entity_create('field_config', array(
      'field_name' => 'field_test',
      'entity_type' => 'taxonomy_term',
      'bundle' => $this->vocabulary->id(),
    ))->save();

    // Change the machine name.
    $old_name = $this->vocabulary->id();
    $new_name = drupal_strtolower($this->randomMachineName());
    $this->vocabulary->vid = $new_name;
    $this->vocabulary->save();

    // Check that entity bundles are properly updated.
    $info = entity_get_bundles('taxonomy_term');
    $this->assertFalse(isset($info[$old_name]), 'The old bundle name does not appear in entity_get_bundles().');
    $this->assertTrue(isset($info[$new_name]), 'The new bundle name appears in entity_get_bundles().');

    // Check that the field is still attached to the vocabulary.
    $this->assertTrue(FieldConfig::loadByName('taxonomy_term', $new_name, 'field_test'), 'The bundle name was updated correctly.');
  }

  /**
   * Test uninstall and reinstall of the taxonomy module.
   */
  function testUninstallReinstall() {
    // Field storages and fields attached to taxonomy term bundles should be
    // removed when the module is uninstalled.
    $field_name = drupal_strtolower($this->randomMachineName() . '_field_name');
    $storage_definition = array(
      'field_name' => $field_name,
      'entity_type' => 'taxonomy_term',
      'type' => 'text',
      'cardinality' => 4
    );
    entity_create('field_storage_config', $storage_definition)->save();
    $field_definition = array(
      'field_name' => $field_name,
      'entity_type' => 'taxonomy_term',
      'bundle' => $this->vocabulary->id(),
      'label' => $this->randomMachineName() . '_label',
    );
    entity_create('field_config', $field_definition)->save();

    require_once DRUPAL_ROOT . '/core/includes/install.inc';
    $this->container->get('module_handler')->uninstall(array('taxonomy'));
    \Drupal::moduleHandler()->install(array('taxonomy'));

    // Now create a vocabulary with the same name. All fields
    // connected to this vocabulary name should have been removed when the
    // module was uninstalled. Creating a new field with the same name and
    // an instance of this field on the same bundle name should be successful.
    $this->vocabulary->enforceIsNew();
    $this->vocabulary->save();
    entity_create('field_storage_config', $storage_definition)->save();
    entity_create('field_config', $field_definition)->save();
  }
}
