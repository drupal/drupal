<?php

namespace Drupal\Tests\content_translation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Content translation settings using the standard profile.
 *
 * @group content_translation
 */
class ContentTranslationStandardFieldsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'language',
    'content_translation',
    'node',
    'comment',
    'field_ui',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer languages',
      'administer content translation',
      'administer content types',
      'administer node fields',
      'administer comment fields',
      'administer comments',
      'administer comment types',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that translatable fields are being rendered.
   */
  public function testFieldTranslatableArticle() {

    $path = 'admin/config/regional/content-language';
    $this->drupalGet($path);

    // Check content block fields.
    $this->assertSession()->checkboxChecked('edit-settings-block-content-basic-fields-body');

    // Check comment fields.
    $this->assertSession()->checkboxChecked('edit-settings-comment-comment-fields-comment-body');

    // Check node fields.
    $this->assertSession()->checkboxChecked('edit-settings-node-article-fields-comment');
    $this->assertSession()->checkboxChecked('edit-settings-node-article-fields-field-image');
    $this->assertSession()->checkboxChecked('edit-settings-node-article-fields-field-tags');

    // Check user fields.
    $this->assertSession()->checkboxChecked('edit-settings-user-user-fields-user-picture');
  }

  /**
   * Test that revision_log is not translatable.
   */
  public function testRevisionLogNotTranslatable() {
    $path = 'admin/config/regional/content-language';
    $this->drupalGet($path);
    $this->assertSession()->fieldNotExists('edit-settings-node-article-fields-revision-log');
  }

}
