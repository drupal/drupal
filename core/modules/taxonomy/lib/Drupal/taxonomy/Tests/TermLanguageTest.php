<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\TermLanguageTest.
 */

namespace Drupal\taxonomy\Tests;

/**
 * Tests for the language feature on taxonomy terms.
 */
class TermLanguageTest extends TaxonomyTestBase {

  public static $modules = array('language');

  public static function getInfo() {
    return array(
      'name' => 'Taxonomy term language',
      'description' => 'Tests the language functionality for the taxonomy terms.',
      'group' => 'Taxonomy',
    );
  }

  function setUp() {
    parent::setUp();

    // Create an administrative user.
    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy'));
    $this->drupalLogin($this->admin_user);

    // Create a vocabulary to which the terms will be assigned.
    $this->vocabulary = $this->createVocabulary();
  }

  function testTermLanguage() {
    // Add first some custom languages.
    $language = (object) array(
      'langcode' => 'aa',
      'name' => $this->randomName(),
    );
    language_save($language);

    $language = (object) array(
      'langcode' => 'bb',
      'name' => $this->randomName(),
    );
    language_save($language);

    // Add a term.
    $this->drupalGet('admin/structure/taxonomy/' . $this->vocabulary->machine_name . '/add');
    // Check that we have the language selector.
    $this->assertField('edit-langcode', t('The language selector field was found on the page'));
    // Submit the term.
    $edit = array(
      'name' => $this->randomName(),
      'langcode' => 'aa',
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    $terms = taxonomy_term_load_multiple_by_name($edit['name']);
    $term = reset($terms);
    $this->assertEqual($term->langcode, $edit['langcode']);

    // Check if on the edit page the language is correct.
    $this->drupalGet('taxonomy/term/' . $term->tid . '/edit');
    $this->assertOptionSelected('edit-langcode', $edit['langcode'], t('The term language was correctly selected.'));

    // Change the language of the term.
    $edit['langcode'] = 'bb';
    $this->drupalPost('taxonomy/term/' . $term->tid . '/edit', $edit, t('Save'));

    // Check again that on the edit page the language is correct.
    $this->drupalGet('taxonomy/term/' . $term->tid . '/edit');
    $this->assertOptionSelected('edit-langcode', $edit['langcode'], t('The term language was correctly selected.'));
  }
}
