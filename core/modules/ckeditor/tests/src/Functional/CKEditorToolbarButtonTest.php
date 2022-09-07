<?php

namespace Drupal\Tests\ckeditor\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\editor\Entity\Editor;
use Drupal\Tests\BrowserTestBase;
use Drupal\Component\Serialization\Json;

/**
 * Tests CKEditor toolbar buttons when the language direction is RTL.
 *
 * @group ckeditor
 * @group legacy
 */
class CKEditorToolbarButtonTest extends BrowserTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var array
   */
  protected static $modules = ['filter', 'editor', 'ckeditor', 'locale'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a text format and associate this with CKEditor.
    FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => [],
    ])->save();
    Editor::create([
      'format' => 'full_html',
      'editor' => 'ckeditor',
    ])->save();

    // Create a new user with admin rights.
    $this->adminUser = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
      'administer site configuration',
      'administer filters',
    ]);
  }

  /**
   * Method tests CKEditor image buttons.
   */
  public function testImageButtonDisplay() {
    $this->drupalLogin($this->adminUser);

    // Install the Arabic language (which is RTL) and configure as the default.
    $edit = [];
    $edit['predefined_langcode'] = 'ar';
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    $edit = ['site_default_language' => 'ar'];
    $this->drupalGet('admin/config/regional/language');
    $this->submitForm($edit, 'Save configuration');
    // Once the default language is changed, go to the tested text format
    // configuration page.
    $this->drupalGet('admin/config/content/formats/manage/full_html');

    // Check if any image button is loaded in CKEditor json.
    $json_encode = function ($html) {
      return trim(Json::encode($html), '"');
    };
    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');
    $markup = $json_encode($file_url_generator->generateString('core/modules/ckeditor/js/plugins/drupalimage/icons/drupalimage.png'));
    $this->assertSession()->responseContains($markup);
  }

}
