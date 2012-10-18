<?php

/**
 * @file
 * Definition of Drupal\taxonomy\Tests\VocabularyLanguageTest.
 */

namespace Drupal\taxonomy\Tests;

use Drupal\Core\Language\Language;

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

    // Add some custom languages.
    $language = new Language(array(
      'langcode' => 'aa',
      'name' => $this->randomName(),
    ));
    language_save($language);

    $language = new Language(array(
      'langcode' => 'bb',
      'name' => $this->randomName(),
    ));
    language_save($language);
  }

  /**
   * Tests language settings for vocabularies.
   */
  function testVocabularyLanguage() {
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

  /**
   * Tests term language settings for vocabulary terms are saved and updated.
   */
  function testVocabularyDefaultLanguageForTerms() {
    // Add a new vocabulary and check that the default language settings are for
    // the terms are saved.
    $edit = array(
      'name' => $this->randomName(),
      'machine_name' => drupal_strtolower($this->randomName()),
      'default_language[langcode]' => 'bb',
      'default_language[language_hidden]' => FALSE,
    );
    $machine_name = $edit['machine_name'];
    $this->drupalPost('admin/structure/taxonomy/add', $edit, t('Save'));

    // Check that the vocabulary was actually created.
    $this->drupalGet('admin/structure/taxonomy/' . $edit['machine_name'] . '/edit');
    $this->assertResponse(200, 'The vocabulary has been created.');

    // Check that the language settings were saved.
    $language_settings = language_get_default_configuration('vocabulary', $edit['machine_name']);
    $this->assertEqual($language_settings['langcode'], 'bb');
    $this->assertEqual($language_settings['language_hidden'], FALSE);

    // Check that the correct options are selected in the interface.
    $this->assertOptionSelected('edit-default-language-langcode', 'bb', 'The correct default language for the terms of this vocabulary is selected.');
    $this->assertNoFieldChecked('edit-default-language-language-hidden', 'Hide language selection option is not checked.');

    // Edit the vocabulary and check that the new settings are updated.
    $edit = array(
      'default_language[langcode]' => 'aa',
      'default_language[language_hidden]' => TRUE,
    );
    $this->drupalPost('admin/structure/taxonomy/' . $machine_name . '/edit', $edit, t('Save'));

    // And check again the settings and also the interface.
    $language_settings = language_get_default_configuration('vocabulary', $machine_name);
    $this->assertEqual($language_settings['langcode'], 'aa');
    $this->assertEqual($language_settings['language_hidden'], TRUE);

    $this->drupalGet('admin/structure/taxonomy/' . $machine_name . '/edit');
    $this->assertOptionSelected('edit-default-language-langcode', 'aa', 'The correct default language for the terms of this vocabulary is selected.');
    $this->assertFieldChecked('edit-default-language-language-hidden', 'Hide language selection option is not checked.');

    // Check that, if the machine name of the vocabulary is changed, then the
    // settings are applied on the new machine name.
     $edit = array(
      'machine_name' => $machine_name . '_new',
      'default_language[langcode]' => 'authors_default',
      'default_language[language_hidden]' => TRUE,
    );
    $new_machine_name = $edit['machine_name'];
    $this->drupalPost('admin/structure/taxonomy/' . $machine_name . '/edit', $edit, t('Save'));

    // Check that the old settings are empty.
    $old_settings = config('language.settings')->get(language_get_default_configuration_settings_key('vocabulary', $machine_name));
    $this->assertNull($old_settings, 'The old vocabulary settings were deleted.');
    // Check that we have the new settings.
    $new_settings = language_get_default_configuration('vocabulary', $new_machine_name);
    $this->assertEqual($new_settings['langcode'], 'authors_default');
    $this->assertEqual($new_settings['language_hidden'], TRUE);
  }
}
