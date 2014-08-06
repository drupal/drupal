<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\VocabularyLanguageTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests the language functionality for vocabularies.
 *
 * @group taxonomy
 */
class VocabularyLanguageTest extends TaxonomyTestBase {

  public static $modules = array('language');

  function setUp() {
    parent::setUp();

    // Create an administrative user.
    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy'));
    $this->drupalLogin($this->admin_user);

    // Add some custom languages.
    $language = new Language(array(
      'id' => 'aa',
      'name' => $this->randomMachineName(),
    ));
    language_save($language);

    $language = new Language(array(
      'id' => 'bb',
      'name' => $this->randomMachineName(),
    ));
    language_save($language);
  }

  /**
   * Tests language settings for vocabularies.
   */
  function testVocabularyLanguage() {
    $this->drupalGet('admin/structure/taxonomy/add');

    // Check that we have the language selector available.
    $this->assertField('edit-langcode', 'The language selector field was found on the page.');

    // Create the vocabulary.
    $vid = drupal_strtolower($this->randomMachineName());
    $edit['name'] = $this->randomMachineName();
    $edit['description'] = $this->randomMachineName();
    $edit['langcode'] = 'aa';
    $edit['vid'] = $vid;
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check the language on the edit page.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vid);
    $this->assertOptionSelected('edit-langcode', $edit['langcode'], 'The vocabulary language was correctly selected.');

    // Change the language and save again.
    $edit['langcode'] = 'bb';
    unset($edit['vid']);
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check again the language on the edit page.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vid);
    $this->assertOptionSelected('edit-langcode', $edit['langcode'], 'The vocabulary language was correctly selected.');
  }

  /**
   * Tests term language settings for vocabulary terms are saved and updated.
   */
  function testVocabularyDefaultLanguageForTerms() {
    // Add a new vocabulary and check that the default language settings are for
    // the terms are saved.
    $edit = array(
      'name' => $this->randomMachineName(),
      'vid' => drupal_strtolower($this->randomMachineName()),
      'default_language[langcode]' => 'bb',
      'default_language[language_show]' => TRUE,
    );
    $vid = $edit['vid'];
    $this->drupalPostForm('admin/structure/taxonomy/add', $edit, t('Save'));

    // Check that the vocabulary was actually created.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $edit['vid']);
    $this->assertResponse(200, 'The vocabulary has been created.');

    // Check that the language settings were saved.
    $language_settings = language_get_default_configuration('taxonomy_term', $edit['vid']);
    $this->assertEqual($language_settings['langcode'], 'bb', 'The langcode was saved.');
    $this->assertTrue($language_settings['language_show'], 'The visibility setting was saved.');

    // Check that the correct options are selected in the interface.
    $this->assertOptionSelected('edit-default-language-langcode', 'bb', 'The correct default language for the terms of this vocabulary is selected.');
    $this->assertFieldChecked('edit-default-language-language-show', 'Show language selection option is checked.');

    // Edit the vocabulary and check that the new settings are updated.
    $edit = array(
      'default_language[langcode]' => 'aa',
      'default_language[language_show]' => FALSE,
    );
    $this->drupalPostForm('admin/structure/taxonomy/manage/' . $vid, $edit, t('Save'));

    // And check again the settings and also the interface.
    $language_settings = language_get_default_configuration('taxonomy_term', $vid);
    $this->assertEqual($language_settings['langcode'], 'aa', 'The langcode was saved.');
    $this->assertFalse($language_settings['language_show'], 'The visibility setting was saved.');

    $this->drupalGet('admin/structure/taxonomy/manage/' . $vid);
    $this->assertOptionSelected('edit-default-language-langcode', 'aa', 'The correct default language for the terms of this vocabulary is selected.');
    $this->assertNoFieldChecked('edit-default-language-language-show', 'Show language selection option is not checked.');

    // Check that language settings are changed after editing vocabulary.
    $edit = array(
      'name' => $this->randomMachineName(),
      'default_language[langcode]' => 'authors_default',
      'default_language[language_show]' => FALSE,
    );
    $this->drupalPostForm('admin/structure/taxonomy/manage/' . $vid, $edit, t('Save'));

    // Check that we have the new settings.
    $new_settings = language_get_default_configuration('taxonomy_term', $vid);
    $this->assertEqual($new_settings['langcode'], 'authors_default', 'The langcode was saved.');
    $this->assertFalse($new_settings['language_show'], 'The new visibility setting was saved.');
  }
}
