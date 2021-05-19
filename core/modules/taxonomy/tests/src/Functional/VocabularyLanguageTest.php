<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;

/**
 * Tests the language functionality for vocabularies.
 *
 * @group taxonomy
 */
class VocabularyLanguageTest extends TaxonomyTestBase {

  protected static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    // Create an administrative user.
    $this->drupalLogin($this->drupalCreateUser(['administer taxonomy']));

    // Add some custom languages.
    ConfigurableLanguage::create([
      'id' => 'aa',
      'label' => $this->randomMachineName(),
    ])->save();

    ConfigurableLanguage::create([
      'id' => 'bb',
      'label' => $this->randomMachineName(),
    ])->save();
  }

  /**
   * Tests language settings for vocabularies.
   */
  public function testVocabularyLanguage() {
    $this->drupalGet('admin/structure/taxonomy/add');

    // Check that we have the language selector available.
    $this->assertSession()->fieldExists('edit-langcode');

    // Create the vocabulary.
    $vid = mb_strtolower($this->randomMachineName());
    $edit['name'] = $this->randomMachineName();
    $edit['description'] = $this->randomMachineName();
    $edit['langcode'] = 'aa';
    $edit['vid'] = $vid;
    $this->submitForm($edit, 'Save');

    // Check the language on the edit page.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vid);
    $this->assertTrue($this->assertSession()->optionExists('edit-langcode', $edit['langcode'])->isSelected());

    // Change the language and save again.
    $edit['langcode'] = 'bb';
    unset($edit['vid']);
    $this->submitForm($edit, 'Save');

    // Check again the language on the edit page.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vid);
    $this->assertTrue($this->assertSession()->optionExists('edit-langcode', $edit['langcode'])->isSelected());
  }

  /**
   * Tests term language settings for vocabulary terms are saved and updated.
   */
  public function testVocabularyDefaultLanguageForTerms() {
    // Add a new vocabulary and check that the default language settings are for
    // the terms are saved.
    $edit = [
      'name' => $this->randomMachineName(),
      'vid' => mb_strtolower($this->randomMachineName()),
      'default_language[langcode]' => 'bb',
      'default_language[language_alterable]' => TRUE,
    ];
    $vid = $edit['vid'];
    $this->drupalPostForm('admin/structure/taxonomy/add', $edit, 'Save');

    // Check that the vocabulary was actually created.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $edit['vid']);
    $this->assertSession()->statusCodeEquals(200);

    // Check that the language settings were saved.
    $language_settings = ContentLanguageSettings::loadByEntityTypeBundle('taxonomy_term', $edit['vid']);
    $this->assertEqual('bb', $language_settings->getDefaultLangcode(), 'The langcode was saved.');
    $this->assertTrue($language_settings->isLanguageAlterable(), 'The visibility setting was saved.');

    // Check that the correct options are selected in the interface.
    $this->assertTrue($this->assertSession()->optionExists('edit-default-language-langcode', 'bb')->isSelected());
    $this->assertSession()->checkboxChecked('edit-default-language-language-alterable');

    // Edit the vocabulary and check that the new settings are updated.
    $edit = [
      'default_language[langcode]' => 'aa',
      'default_language[language_alterable]' => FALSE,
    ];
    $this->drupalPostForm('admin/structure/taxonomy/manage/' . $vid, $edit, 'Save');

    // And check again the settings and also the interface.
    $language_settings = ContentLanguageSettings::loadByEntityTypeBundle('taxonomy_term', $vid);
    $this->assertEqual('aa', $language_settings->getDefaultLangcode(), 'The langcode was saved.');
    $this->assertFalse($language_settings->isLanguageAlterable(), 'The visibility setting was saved.');

    $this->drupalGet('admin/structure/taxonomy/manage/' . $vid);
    $this->assertTrue($this->assertSession()->optionExists('edit-default-language-langcode', 'aa')->isSelected());
    $this->assertSession()->checkboxNotChecked('edit-default-language-language-alterable');

    // Check that language settings are changed after editing vocabulary.
    $edit = [
      'name' => $this->randomMachineName(),
      'default_language[langcode]' => 'authors_default',
      'default_language[language_alterable]' => FALSE,
    ];
    $this->drupalPostForm('admin/structure/taxonomy/manage/' . $vid, $edit, 'Save');

    // Check that we have the new settings.
    $new_settings = ContentLanguageSettings::loadByEntityTypeBundle('taxonomy_term', $vid);
    $this->assertEqual('authors_default', $new_settings->getDefaultLangcode(), 'The langcode was saved.');
    $this->assertFalse($new_settings->isLanguageAlterable(), 'The new visibility setting was saved.');
  }

}
