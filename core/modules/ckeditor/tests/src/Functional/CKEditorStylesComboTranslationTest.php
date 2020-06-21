<?php

namespace Drupal\Tests\ckeditor\Functional;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests administration of the CKEditor StylesCombo plugin.
 *
 * @group ckeditor
 */
class CKEditorStylesComboTranslationTest extends BrowserTestBase {

  /**
   * {inheritdoc}
   */
  protected static $modules = ['ckeditor', 'config_translation'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with the 'administer filters' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A randomly generated format machine name.
   *
   * @var string
   */
  protected $format;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->format = strtolower($this->randomMachineName());
    $filter_format = FilterFormat::create([
      'format' => $this->format,
      'name' => $this->randomString(),
      'filters' => [],
    ]);
    $filter_format->save();
    $editor = Editor::create([
      'format' => $this->format,
      'editor' => 'ckeditor',
    ]);
    $editor->save();

    $this->adminUser = $this->drupalCreateUser([
      'administer filters',
      'translate configuration',
    ]);

    ConfigurableLanguage::createFromLangcode('de')->save();
  }

  /**
   * Tests translations of CKEditor styles configuration.
   */
  public function testExistingFormat() {
    $this->drupalLogin($this->adminUser);
    $edit = [
      'editor[settings][plugins][stylescombo][styles]' => 'h1.title|Title',
    ];
    $this->drupalPostForm('admin/config/content/formats/manage/' . $this->format, $edit, 'Save configuration');

    $this->drupalGet('admin/config/content/formats/manage/' . $this->format . '/translate/de/add');
    $this->assertEquals('textarea', $this->assertSession()->fieldExists('List of styles')->getTagName());
    $this->assertSession()->fieldValueEquals('List of styles', 'h1.title|Title');

    $page = $this->getSession()->getPage();
    $page->fillField('List of styles', 'h1.title|Titel');
    $page->pressButton('Save translation');
    $this->assertSession()->pageTextContains('Successfully saved German translation.');
  }

}
