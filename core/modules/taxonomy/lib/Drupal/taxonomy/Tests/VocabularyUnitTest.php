<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\VocabularyUnitTest.
 */

namespace Drupal\taxonomy\Tests;

/**
 * Tests for taxonomy vocabulary functions.
 */
class VocabularyUnitTest extends TaxonomyTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy vocabularies',
      'description' => 'Test loading, saving and deleting vocabularies.',
      'group' => 'Taxonomy',
    );
  }

  function setUp() {
    parent::setUp(array('field_test'));
    $admin_user = $this->drupalCreateUser(array('create article content', 'administer taxonomy'));
    $this->drupalLogin($admin_user);
    $this->vocabulary = $this->createVocabulary();
  }

  /**
   * Ensure that when an invalid vocabulary vid is loaded, it is possible
   * to load the same vid successfully if it subsequently becomes valid.
   */
  function testTaxonomyVocabularyLoadReturnFalse() {
    // Load a vocabulary that doesn't exist.
    $vocabularies = taxonomy_vocabulary_load_multiple(FALSE);
    $vid = count($vocabularies) + 1;
    $vocabulary = taxonomy_vocabulary_load($vid);
    // This should not return an object because no such vocabulary exists.
    $this->assertTrue(empty($vocabulary), 'No object loaded.');

    // Create a new vocabulary.
    $this->createVocabulary();
    // Load the vocabulary with the same $vid from earlier.
    // This should return a vocabulary object since it now matches a real vid.
    $vocabulary = taxonomy_vocabulary_load($vid);
    $this->assertTrue(!empty($vocabulary) && is_object($vocabulary), 'Vocabulary is an object.');
    $this->assertEqual($vocabulary->vid, $vid, 'Valid vocabulary vid is the same as our previously invalid one.');
  }

  /**
   * Test deleting a taxonomy that contains terms.
   */
  function testTaxonomyVocabularyDeleteWithTerms() {
    // Delete any existing vocabularies.
    foreach (taxonomy_vocabulary_load_multiple(FALSE) as $vocabulary) {
      taxonomy_vocabulary_delete($vocabulary->vid);
    }

    // Assert that there are no terms left.
    $this->assertEqual(0, db_query('SELECT COUNT(*) FROM {taxonomy_term_data}')->fetchField());

    // Create a new vocabulary and add a few terms to it.
    $vocabulary = $this->createVocabulary();
    $terms = array();
    for ($i = 0; $i < 5; $i++) {
      $terms[$i] = $this->createTerm($vocabulary);
    }

    // Set up hierarchy. term 2 is a child of 1 and 4 a child of 1 and 2.
    $terms[2]->parent = array($terms[1]->tid);
    taxonomy_term_save($terms[2]);
    $terms[4]->parent = array($terms[1]->tid, $terms[2]->tid);
    taxonomy_term_save($terms[4]);

    // Assert that there are now 5 terms.
    $this->assertEqual(5, db_query('SELECT COUNT(*) FROM {taxonomy_term_data}')->fetchField());

    taxonomy_vocabulary_delete($vocabulary->vid);

    // Assert that there are no terms left.
    $this->assertEqual(0, db_query('SELECT COUNT(*) FROM {taxonomy_term_data}')->fetchField());
  }

  /**
   * Ensure that the vocabulary static reset works correctly.
   */
  function testTaxonomyVocabularyLoadStaticReset() {
    $original_vocabulary = taxonomy_vocabulary_load($this->vocabulary->vid);
    $this->assertTrue(is_object($original_vocabulary), 'Vocabulary loaded successfully.');
    $this->assertEqual($this->vocabulary->name, $original_vocabulary->name, 'Vocabulary loaded successfully.');

    // Change the name and description.
    $vocabulary = $original_vocabulary;
    $vocabulary->name = $this->randomName();
    $vocabulary->description = $this->randomName();
    taxonomy_vocabulary_save($vocabulary);

    // Load the vocabulary.
    $new_vocabulary = taxonomy_vocabulary_load($original_vocabulary->vid);
    $this->assertEqual($new_vocabulary->name, $vocabulary->name);
    $this->assertEqual($new_vocabulary->name, $vocabulary->name);

    // Delete the vocabulary.
    taxonomy_vocabulary_delete($this->vocabulary->vid);
    $vocabularies = taxonomy_vocabulary_load_multiple(FALSE);
    $this->assertTrue(!isset($vocabularies[$this->vocabulary->vid]), 'The vocabulary was deleted.');
  }

  /**
   * Tests for loading multiple vocabularies.
   */
  function testTaxonomyVocabularyLoadMultiple() {

    // Delete any existing vocabularies.
    foreach (taxonomy_vocabulary_load_multiple(FALSE) as $vocabulary) {
      taxonomy_vocabulary_delete($vocabulary->vid);
    }

    // Create some vocabularies and assign weights.
    $vocabulary1 = $this->createVocabulary();
    $vocabulary1->weight = 0;
    taxonomy_vocabulary_save($vocabulary1);
    $vocabulary2 = $this->createVocabulary();
    $vocabulary2->weight = 1;
    taxonomy_vocabulary_save($vocabulary2);
    $vocabulary3 = $this->createVocabulary();
    $vocabulary3->weight = 2;
    taxonomy_vocabulary_save($vocabulary3);

    // Fetch the names for all vocabularies, confirm that they are keyed by
    // machine name.
    $names = taxonomy_vocabulary_get_names();
    $this->assertEqual($names[$vocabulary1->machine_name]->name, $vocabulary1->name, 'Vocabulary 1 name found.');

    // Fetch all of the vocabularies using taxonomy_vocabulary_load_multiple(FALSE).
    // Confirm that the vocabularies are ordered by weight.
    $vocabularies = taxonomy_vocabulary_load_multiple(FALSE);
    $this->assertEqual(array_shift($vocabularies)->vid, $vocabulary1->vid, 'Vocabulary was found in the vocabularies array.');
    $this->assertEqual(array_shift($vocabularies)->vid, $vocabulary2->vid, 'Vocabulary was found in the vocabularies array.');
    $this->assertEqual(array_shift($vocabularies)->vid, $vocabulary3->vid, 'Vocabulary was found in the vocabularies array.');

    // Fetch the vocabularies with taxonomy_vocabulary_load_multiple(), specifying IDs.
    // Ensure they are returned in the same order as the original array.
    $vocabularies = taxonomy_vocabulary_load_multiple(array($vocabulary3->vid, $vocabulary2->vid, $vocabulary1->vid));
    $this->assertEqual(array_shift($vocabularies)->vid, $vocabulary3->vid, 'Vocabulary loaded successfully by ID.');
    $this->assertEqual(array_shift($vocabularies)->vid, $vocabulary2->vid, 'Vocabulary loaded successfully by ID.');
    $this->assertEqual(array_shift($vocabularies)->vid, $vocabulary1->vid, 'Vocabulary loaded successfully by ID.');

    // Fetch vocabulary 1 by name.
    $vocabulary = current(taxonomy_vocabulary_load_multiple(array(), array('name' => $vocabulary1->name)));
    $this->assertEqual($vocabulary->vid, $vocabulary1->vid, 'Vocabulary loaded successfully by name.');

    // Fetch vocabulary 1 by name and ID.
    $this->assertEqual(current(taxonomy_vocabulary_load_multiple(array($vocabulary1->vid), array('name' => $vocabulary1->name)))->vid, $vocabulary1->vid, 'Vocabulary loaded successfully by name and ID.');
  }

  /**
   * Tests that machine name changes are properly reflected.
   */
  function testTaxonomyVocabularyChangeMachineName() {
    // Add a field instance to the vocabulary.
    $field = array(
      'field_name' => 'field_test',
      'type' => 'test_field',
    );
    field_create_field($field);
    $instance = array(
      'field_name' => 'field_test',
      'entity_type' => 'taxonomy_term',
      'bundle' => $this->vocabulary->machine_name,
    );
    field_create_instance($instance);

    // Change the machine name.
    $old_name = $this->vocabulary->machine_name;
    $new_name = drupal_strtolower($this->randomName());
    $this->vocabulary->machine_name = $new_name;
    taxonomy_vocabulary_save($this->vocabulary);

    // Check that entity bundles are properly updated.
    $info = entity_get_info('taxonomy_term');
    $this->assertFalse(isset($info['bundles'][$old_name]), 'The old bundle name does not appear in entity_get_info().');
    $this->assertTrue(isset($info['bundles'][$new_name]), 'The new bundle name appears in entity_get_info().');

    // Check that the field instance is still attached to the vocabulary.
    $this->assertTrue(field_info_instance('taxonomy_term', 'field_test', $new_name), 'The bundle name was updated correctly.');
  }

  /**
   * Test uninstall and reinstall of the taxonomy module.
   */
  function testUninstallReinstall() {
    // Fields and field instances attached to taxonomy term bundles should be
    // removed when the module is uninstalled.
    $this->field_name = drupal_strtolower($this->randomName() . '_field_name');
    $this->field = array('field_name' => $this->field_name, 'type' => 'text', 'cardinality' => 4);
    $this->field = field_create_field($this->field);
    $this->instance = array(
      'field_name' => $this->field_name,
      'entity_type' => 'taxonomy_term',
      'bundle' => $this->vocabulary->machine_name,
      'label' => $this->randomName() . '_label',
    );
    field_create_instance($this->instance);

    module_disable(array('taxonomy'));
    require_once DRUPAL_ROOT . '/core/includes/install.inc';
    module_uninstall(array('taxonomy'));
    module_enable(array('taxonomy'));

    // Now create a vocabulary with the same name. All field instances
    // connected to this vocabulary name should have been removed when the
    // module was uninstalled. Creating a new field with the same name and
    // an instance of this field on the same bundle name should be successful.
    $this->vocabulary->enforceIsNew();
    taxonomy_vocabulary_save($this->vocabulary);
    unset($this->field['id']);
    field_create_field($this->field);
    field_create_instance($this->instance);
  }
}
