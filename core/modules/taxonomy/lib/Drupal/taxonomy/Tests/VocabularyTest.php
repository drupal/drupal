<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\VocabularyTest.
 */

namespace Drupal\taxonomy\Tests;

/**
 * Tests the taxonomy vocabulary interface.
 */
class VocabularyTest extends TaxonomyTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy vocabulary interface',
      'description' => 'Test the taxonomy vocabulary interface.',
      'group' => 'Taxonomy',
    );
  }

  function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy'));
    $this->drupalLogin($this->admin_user);
    $this->vocabulary = $this->createVocabulary();
  }

  /**
   * Create, edit and delete a vocabulary via the user interface.
   */
  function testVocabularyInterface() {
    // Visit the main taxonomy administration page.
    $this->drupalGet('admin/structure/taxonomy');

    // Create a new vocabulary.
    $this->clickLink(t('Add vocabulary'));
    $edit = array();
    $machine_name = drupal_strtolower($this->randomName());
    $edit['name'] = $this->randomName();
    $edit['description'] = $this->randomName();
    $edit['machine_name'] = $machine_name;
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertRaw(t('Created new vocabulary %name.', array('%name' => $edit['name'])), 'Vocabulary created successfully.');

    // Edit the vocabulary.
    $this->drupalGet('admin/structure/taxonomy');
    $this->assertText($edit['name'], 'Vocabulary found in the vocabulary overview listing.');
    $this->clickLink(t('edit vocabulary'));
    $edit = array();
    $edit['name'] = $this->randomName();
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->drupalGet('admin/structure/taxonomy');
    $this->assertText($edit['name'], 'Vocabulary found in the vocabulary overview listing.');

    // Try to submit a vocabulary with a duplicate machine name.
    $edit['machine_name'] = $machine_name;
    $this->drupalPost('admin/structure/taxonomy/add', $edit, t('Save'));
    $this->assertText(t('The machine-readable name is already in use. It must be unique.'));

    // Try to submit an invalid machine name.
    $edit['machine_name'] = '!&^%';
    $this->drupalPost('admin/structure/taxonomy/add', $edit, t('Save'));
    $this->assertText(t('The machine-readable name must contain only lowercase letters, numbers, and underscores.'));
  }

  /**
   * Changing weights on the vocabulary overview with two or more vocabularies.
   */
  function testTaxonomyAdminChangingWeights() {
    // Create some vocabularies.
    for ($i = 0; $i < 10; $i++) {
      $this->createVocabulary();
    }
    // Get all vocabularies and change their weights.
    $vocabularies = taxonomy_vocabulary_load_multiple(FALSE);
    $edit = array();
    foreach ($vocabularies as $key => $vocabulary) {
      $vocabulary->weight = -$vocabulary->weight;
      $vocabularies[$key]->weight = $vocabulary->weight;
      $edit[$key . '[weight]'] = $vocabulary->weight;
    }
    // Saving the new weights via the interface.
    $this->drupalPost('admin/structure/taxonomy', $edit, t('Save'));

    // Load the vocabularies from the database.
    $new_vocabularies = taxonomy_vocabulary_load_multiple(FALSE);

    // Check that the weights are saved in the database correctly.
    foreach ($vocabularies as $key => $vocabulary) {
      $this->assertEqual($new_vocabularies[$key]->weight, $vocabularies[$key]->weight, 'The vocabulary weight was changed.');
    }
  }

  /**
   * Test the vocabulary overview with no vocabularies.
   */
  function testTaxonomyAdminNoVocabularies() {
    // Delete all vocabularies.
    $vocabularies = taxonomy_vocabulary_load_multiple(FALSE);
    foreach ($vocabularies as $key => $vocabulary) {
      taxonomy_vocabulary_delete($key);
    }
    // Confirm that no vocabularies are found in the database.
    $this->assertFalse(taxonomy_vocabulary_load_multiple(FALSE), 'No vocabularies found in the database.');
    $this->drupalGet('admin/structure/taxonomy');
    // Check the default message for no vocabularies.
    $this->assertText(t('No vocabularies available.'), 'No vocabularies were found.');
  }

  /**
   * Deleting a vocabulary.
   */
  function testTaxonomyAdminDeletingVocabulary() {
    // Create a vocabulary.
    $edit = array(
      'name' => $this->randomName(),
      'machine_name' => drupal_strtolower($this->randomName()),
    );
    $this->drupalPost('admin/structure/taxonomy/add', $edit, t('Save'));
    $this->assertText(t('Created new vocabulary'), 'New vocabulary was created.');

    // Check the created vocabulary.
    $vocabularies = taxonomy_vocabulary_load_multiple(FALSE);
    $vid = $vocabularies[count($vocabularies) - 1]->vid;
    entity_get_controller('taxonomy_vocabulary')->resetCache();
    $vocabulary = taxonomy_vocabulary_load($vid);
    $this->assertTrue($vocabulary, 'Vocabulary found in database.');

    // Delete the vocabulary.
    $edit = array();
    $this->drupalPost('admin/structure/taxonomy/' . $vocabulary->machine_name . '/edit', $edit, t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete the vocabulary %name?', array('%name' => $vocabulary->name)), '[confirm deletion] Asks for confirmation.');
    $this->assertText(t('Deleting a vocabulary will delete all the terms in it. This action cannot be undone.'), '[confirm deletion] Inform that all terms will be deleted.');

    // Confirm deletion.
    $this->drupalPost(NULL, NULL, t('Delete'));
    $this->assertRaw(t('Deleted vocabulary %name.', array('%name' => $vocabulary->name)), 'Vocabulary deleted.');
    entity_get_controller('taxonomy_vocabulary')->resetCache();
    $this->assertFalse(taxonomy_vocabulary_load($vid), t('Vocabulary is not found in the database'));
  }
}
