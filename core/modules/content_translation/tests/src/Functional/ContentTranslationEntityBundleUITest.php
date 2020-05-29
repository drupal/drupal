<?php

namespace Drupal\Tests\content_translation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the content translation behaviors on entity bundle UI.
 *
 * @group content_translation
 */
class ContentTranslationEntityBundleUITest extends BrowserTestBase {

  public static $modules = [
    'language',
    'content_translation',
    'node',
    'comment',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp() {
    parent::setUp();
    $user = $this->drupalCreateUser(['access administration pages', 'administer languages', 'administer content translation', 'administer content types']);
    $this->drupalLogin($user);
  }

  /**
   * Tests content types default translation behavior.
   */
  public function testContentTypeUI() {
    // Create first content type.
    $this->drupalCreateContentType(['type' => 'article']);
    // Enable content translation.
    $edit = ['language_configuration[content_translation]' => TRUE];
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, 'Save content type');

    // Make sure add page does not inherit translation configuration from first
    // content type.
    $this->drupalGet('admin/structure/types/add');
    $this->assertNoFieldChecked('edit-language-configuration-content-translation');

    // Create second content type and set content translation.
    $edit = [
      'name' => 'Page',
      'type' => 'page',
      'language_configuration[content_translation]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/types/add', $edit, 'Save and manage fields');

    // Make sure the settings are saved when creating the content type.
    $this->drupalGet('admin/structure/types/manage/page');
    $this->assertFieldChecked('edit-language-configuration-content-translation');

  }

}
