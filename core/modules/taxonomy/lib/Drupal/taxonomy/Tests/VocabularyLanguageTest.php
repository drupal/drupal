<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\VocabularyLanguageTest.
 */

namespace Drupal\taxonomy\Tests;

/**
 * Tests for the language feature on vocabularies.
 */
class VocabularyLanguageTest extends TaxonomyTestBase {

  public static $modules = array('language');

  public static function getInfo() {
    return array(
      'name' => 'Vocabulary language',
      'description' => 'Tests the language functionality for vocabularies.',
      'group' => 'Taxonomy',
    );
  }

  function setUp() {
    parent::setUp();

    // Create an administrative user.
    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy'));
    $this->drupalLogin($this->admin_user);
  }

  function testVocabularyLanguage() {
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

    $this->drupalGet('admin/structure/taxonomy/add');
    // Check that we have the language selector available.
    $this->assertField('edit-langcode', t('The language selector field was found on the page'));

    // Create the vocabulary.
    $machine_name = drupal_strtolower($this->randomName());
    $edit['name'] = $this->randomName();
    $edit['description'] = $this->randomName();
    $edit['langcode'] = 'aa';
    $edit['machine_name'] = $machine_name;
    $this->drupalPost(NULL, $edit, t('Save'));

    // Check the language on the edit page.
    $this->drupalGet('admin/structure/taxonomy/' . $machine_name . '/edit');
    $this->assertOptionSelected('edit-langcode', $edit['langcode'], t('The vocabulary language was correctly selected.'));

    // Change the language and save again.
    $edit['langcode'] = 'bb';
    $this->drupalPost(NULL, $edit, t('Save'));

    // Check again the language on the edit page.
    $this->drupalGet('admin/structure/taxonomy/' . $machine_name . '/edit');
    $this->assertOptionSelected('edit-langcode', $edit['langcode'], t('The vocabulary language was correctly selected.'));
  }
}
