<?php

namespace Drupal\Tests\taxonomy\Functional;

/**
 * Tests content translation for vocabularies.
 *
 * @group taxonomy
 */
class VocabularyTranslationTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['content_translation', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
  public function testVocabularyLanguage() {
    $this->drupalGet('admin/structure/taxonomy/add');

    // Check that the field to enable content translation is available.
    $this->assertSession()->fieldExists('edit-default-language-content-translation');

    // Create the vocabulary.
    $vid = mb_strtolower($this->randomMachineName());
    $edit['name'] = $this->randomMachineName();
    $edit['description'] = $this->randomMachineName();
    $edit['langcode'] = 'en';
    $edit['vid'] = $vid;
    $edit['default_language[content_translation]'] = TRUE;
    $this->submitForm($edit, 'Save');

    // Check if content translation is enabled on the edit page.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vid);
    $this->assertSession()->checkboxChecked('edit-default-language-content-translation');
  }

}
