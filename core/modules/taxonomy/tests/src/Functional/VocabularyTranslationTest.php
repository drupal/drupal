<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests content translation for vocabularies.
 *
 * @group taxonomy
 */
class VocabularyTranslationTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
    'config_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Languages to enable.
   *
   * @var string[]
   */
  protected $additionalLangcodes = ['es'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an administrative user.
    $this->drupalLogin($this->drupalCreateUser([
      'administer taxonomy',
      'administer content translation',
      'translate configuration',
    ]));

    // Add languages.
    foreach ($this->additionalLangcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
  }

  /**
   * Tests language settings for vocabularies.
   */
  public function testVocabularyLanguage() {
    $this->drupalGet('admin/structure/taxonomy/add');

    // Check that the field to enable content translation is available.
    $this->assertSession()->fieldExists('edit-default-language-content-translation');

    // Create the vocabulary.
    $vid = $this->randomMachineName();
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

  /**
   * Tests vocabulary name translation for the overview and reset pages.
   */
  public function testVocabularyTitleLabelTranslation(): void {
    $this->drupalGet('admin/structure/taxonomy/add');

    // Create the vocabulary.
    $vid = $this->randomMachineName();
    $edit['name'] = $this->randomMachineName();
    $edit['description'] = $this->randomMachineName();
    $edit['langcode'] = 'en';
    $edit['vid'] = $vid;
    $edit['default_language[content_translation]'] = TRUE;
    $this->submitForm($edit, 'Save');

    $langcode = $this->additionalLangcodes[0];
    $vid_name = $edit['name'];
    $translated_vid_name = "Translated $vid_name";

    $this->assertSession()->pageTextContains($vid_name);

    // Assert that the name label is displayed on the translation form with the
    // right value.
    $this->drupalGet("admin/structure/taxonomy/manage/$vid/translate/$langcode/add");

    // Translate the name label.
    $this->submitForm(["translation[config_names][taxonomy.vocabulary.$vid][name]" => $translated_vid_name], 'Save translation');

    // Assert that the right name label is displayed on the taxonomy term
    // overview page.
    $this->drupalGet("admin/structure/taxonomy/manage/$vid/overview");
    $this->assertSession()->pageTextContains($vid_name);
    $this->drupalGet("$langcode/admin/structure/taxonomy/manage/$vid/overview");
    $this->assertSession()->pageTextContains($translated_vid_name);

    // Assert that the right name label is displayed on the taxonomy reset page.
    $this->drupalGet("admin/structure/taxonomy/manage/$vid/reset");
    $this->assertSession()->pageTextContains($vid_name);
    $this->drupalGet("$langcode/admin/structure/taxonomy/manage/$vid/reset");
    $this->assertSession()->pageTextContains($translated_vid_name);
  }

}
