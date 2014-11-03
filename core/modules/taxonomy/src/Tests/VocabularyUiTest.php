<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\VocabularyUiTest.
 */

namespace Drupal\taxonomy\Tests;
use Drupal\Component\Utility\Unicode;

/**
 * Tests the taxonomy vocabulary interface.
 *
 * @group taxonomy
 */
class VocabularyUiTest extends TaxonomyTestBase {

  protected function setUp() {
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
    $vid = Unicode::strtolower($this->randomMachineName());
    $edit['name'] = $this->randomMachineName();
    $edit['description'] = $this->randomMachineName();
    $edit['vid'] = $vid;
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('Created new vocabulary %name.', array('%name' => $edit['name'])), 'Vocabulary created successfully.');

    // Edit the vocabulary.
    $this->drupalGet('admin/structure/taxonomy');
    $this->assertText($edit['name'], 'Vocabulary found in the vocabulary overview listing.');
    $this->clickLink(t('Edit vocabulary'));
    $edit = array();
    $edit['name'] = $this->randomMachineName();
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('admin/structure/taxonomy');
    $this->assertText($edit['name'], 'Vocabulary found in the vocabulary overview listing.');

    // Try to submit a vocabulary with a duplicate machine name.
    $edit['vid'] = $vid;
    $this->drupalPostForm('admin/structure/taxonomy/add', $edit, t('Save'));
    $this->assertText(t('The machine-readable name is already in use. It must be unique.'));

    // Try to submit an invalid machine name.
    $edit['vid'] = '!&^%';
    $this->drupalPostForm('admin/structure/taxonomy/add', $edit, t('Save'));
    $this->assertText(t('The machine-readable name must contain only lowercase letters, numbers, and underscores.'));

    // Ensure that vocabulary titles are escaped properly.
    $edit = array();
    $edit['name'] = 'Don\'t Panic';
    $edit['description'] = $this->randomMachineName();
    $edit['vid'] = 'don_t_panic';
    $this->drupalPostForm('admin/structure/taxonomy/add', $edit, t('Save'));

    $site_name = \Drupal::config('system.site')->get('name');
    $this->assertTitle(t('Don\'t Panic | @site-name', array('@site-name' => $site_name)), 'The page title contains the escaped character.');
    $this->assertNoTitle(t('Don&#039;t Panic | @site-name', array('@site-name' => $site_name)), 'The page title does not contain an encoded character.');
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
    $vocabularies = entity_load_multiple('taxonomy_vocabulary');
    $edit = array();
    foreach ($vocabularies as $key => $vocabulary) {
      $weight = -$vocabulary->weight;
      $vocabularies[$key]->weight = $weight;
      $edit['vocabularies[' . $key . '][weight]'] = $weight;
    }
    // Saving the new weights via the interface.
    $this->drupalPostForm('admin/structure/taxonomy', $edit, t('Save'));

    // Load the vocabularies from the database.
    $this->container->get('entity.manager')->getStorage('taxonomy_vocabulary')->resetCache();
    $new_vocabularies = entity_load_multiple('taxonomy_vocabulary');

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
    $vocabularies = entity_load_multiple('taxonomy_vocabulary');
    foreach ($vocabularies as $key => $vocabulary) {
      $vocabulary->delete();
    }
    // Confirm that no vocabularies are found in the database.
    $this->assertFalse(entity_load_multiple('taxonomy_vocabulary'), 'No vocabularies found.');
    $this->drupalGet('admin/structure/taxonomy');
    // Check the default message for no vocabularies.
    $this->assertText(t('No vocabularies available.'));
  }

  /**
   * Deleting a vocabulary.
   */
  function testTaxonomyAdminDeletingVocabulary() {
    // Create a vocabulary.
    $vid = Unicode::strtolower($this->randomMachineName());
    $edit = array(
      'name' => $this->randomMachineName(),
      'vid' => $vid,
    );
    $this->drupalPostForm('admin/structure/taxonomy/add', $edit, t('Save'));
    $this->assertText(t('Created new vocabulary'), 'New vocabulary was created.');

    // Check the created vocabulary.
    $this->container->get('entity.manager')->getStorage('taxonomy_vocabulary')->resetCache();
    $vocabulary = entity_load('taxonomy_vocabulary', $vid);
    $this->assertTrue($vocabulary, 'Vocabulary found.');

    // Delete the vocabulary.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id());
    $this->clickLink(t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete the vocabulary %name?', array('%name' => $vocabulary->name)), '[confirm deletion] Asks for confirmation.');
    $this->assertText(t('Deleting a vocabulary will delete all the terms in it. This action cannot be undone.'), '[confirm deletion] Inform that all terms will be deleted.');

    // Confirm deletion.
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertRaw(t('Deleted vocabulary %name.', array('%name' => $vocabulary->name)), 'Vocabulary deleted.');
    $this->container->get('entity.manager')->getStorage('taxonomy_vocabulary')->resetCache();
    $this->assertFalse(entity_load('taxonomy_vocabulary', $vid), 'Vocabulary not found.');
  }
}
