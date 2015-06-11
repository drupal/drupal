<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\VocabularyTranslationTest.
 */
namespace Drupal\taxonomy\Tests;

use Drupal\Component\Utility\Unicode;

/**
 * Tests content translation for vocabularies.
 *
 * @group taxonomy
 */
class VocabularyTranslationTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('content_translation', 'language');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an administrative user.
    $this->drupalLogin($this->drupalCreateUser([
      'administer taxonomy',
      'administer content translation',
    ]));
  }

  /**
   * Tests language settings for vocabularies.
   */
  function testVocabularyLanguage() {
    $this->drupalGet('admin/structure/taxonomy/add');

    // Check that the field to enable content translation is available.
    $this->assertField('edit-default-language-content-translation', 'The content translation checkbox is present on the page.');

    // Create the vocabulary.
    $vid = Unicode::strtolower($this->randomMachineName());
    $edit['name'] = $this->randomMachineName();
    $edit['description'] = $this->randomMachineName();
    $edit['langcode'] = 'en';
    $edit['vid'] = $vid;
    $edit['default_language[content_translation]'] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check if content translation is enabled on the edit page.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vid);
    $this->assertFieldChecked('edit-default-language-content-translation', 'The content translation was correctly selected.');
  }

}
