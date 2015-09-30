<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\VocabularyPermissionsTest.
 */

namespace Drupal\taxonomy\Tests;

/**
 * Tests the taxonomy vocabulary permissions.
 *
 * @group taxonomy
 */
class VocabularyPermissionsTest extends TaxonomyTestBase {

  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Create, edit and delete a taxonomy term via the user interface.
   */
  function testVocabularyPermissionsTaxonomyTerm() {
    // Vocabulary used for creating, removing and editing terms.
    $vocabulary = $this->createVocabulary();

    // Test as admin user.
    $user = $this->drupalCreateUser(array('administer taxonomy'));
    $this->drupalLogin($user);

    // Visit the main taxonomy administration page.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add');
    $this->assertResponse(200);
    $this->assertField('edit-name-0-value', 'Add taxonomy term form opened successfully.');

    // Submit the term.
    $edit = array();
    $edit['name[0][value]'] = $this->randomMachineName();

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('Created new term %name.', array('%name' => $edit['name[0][value]'])), 'Term created successfully.');

    $terms = taxonomy_term_load_multiple_by_name($edit['name[0][value]']);
    $term = reset($terms);

    // Edit the term.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->assertResponse(200);
    $this->assertText($edit['name[0][value]'], 'Edit taxonomy term form opened successfully.');

    $edit['name[0][value]'] = $this->randomMachineName();
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('Updated term %name.', array('%name' => $edit['name[0][value]'])), 'Term updated successfully.');

    // Delete the vocabulary.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/delete');
    $this->assertRaw(t('Are you sure you want to delete the @entity-type %label?', array('@entity-type' => 'taxonomy term', '%label' => $edit['name[0][value]'])), 'Delete taxonomy term form opened successfully.');

    // Confirm deletion.
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertRaw(t('Deleted term %name.', array('%name' => $edit['name[0][value]'])), 'Term deleted.');

    // Test as user with "edit" permissions.
    $user = $this->drupalCreateUser(array("edit terms in {$vocabulary->id()}"));
    $this->drupalLogin($user);

    // Visit the main taxonomy administration page.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add');
    $this->assertResponse(403, 'Add taxonomy term form open failed.');

    // Create a test term.
    $term = $this->createTerm($vocabulary);

    // Edit the term.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->assertResponse(200);
    $this->assertText($term->getName(), 'Edit taxonomy term form opened successfully.');

    $edit['name[0][value]'] = $this->randomMachineName();
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('Updated term %name.', array('%name' => $edit['name[0][value]'])), 'Term updated successfully.');

    // Delete the vocabulary.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/delete');
    $this->assertResponse(403, 'Delete taxonomy term form open failed.');

    // Test as user with "delete" permissions.
    $user = $this->drupalCreateUser(array("delete terms in {$vocabulary->id()}"));
    $this->drupalLogin($user);

    // Visit the main taxonomy administration page.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add');
    $this->assertResponse(403, 'Add taxonomy term form open failed.');

    // Create a test term.
    $term = $this->createTerm($vocabulary);

    // Edit the term.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->assertResponse(403, 'Edit taxonomy term form open failed.');

    // Delete the vocabulary.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/delete');
    $this->assertRaw(t('Are you sure you want to delete the @entity-type %label?', array('@entity-type' => 'taxonomy term', '%label' => $term->getName())), 'Delete taxonomy term form opened successfully.');

    // Confirm deletion.
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertRaw(t('Deleted term %name.', array('%name' => $term->getName())), 'Term deleted.');

    // Test as user without proper permissions.
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    // Visit the main taxonomy administration page.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id() . '/add');
    $this->assertResponse(403, 'Add taxonomy term form open failed.');

    // Create a test term.
    $term = $this->createTerm($vocabulary);

    // Edit the term.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->assertResponse(403, 'Edit taxonomy term form open failed.');

    // Delete the vocabulary.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/delete');
    $this->assertResponse(403, 'Delete taxonomy term form open failed.');
  }
}
