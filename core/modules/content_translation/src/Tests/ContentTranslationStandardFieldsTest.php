<?php

/**
 * @file
 * Contains \Drupal\content_translation\Tests\ContentTranslationStandardFieldsTest.
 */

namespace Drupal\content_translation\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the Content translation settings using the standard profile.
 *
 * @group content_translation
 */
class ContentTranslationStandardFieldsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'language',
    'content_translation',
    'node',
    'comment',
    'field_ui',
    'entity_test',
  );

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(array(
      'access administration pages',
      'administer languages',
      'administer content translation',
      'administer content types',
      'administer node fields',
      'administer comment fields',
      'administer comments',
      'administer comment types',
    ));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that translatable fields are being rendered.
   */
  public function testFieldTranslatableArticle() {

    $path = 'admin/config/regional/content-language';
    $this->drupalGet($path);

    // Check content block fields.
    $this->assertFieldByXPath("//input[@id='edit-settings-block-content-basic-fields-body' and @checked='checked']");

    // Check comment fields.
    $this->assertFieldByXPath("//input[@id='edit-settings-comment-comment-fields-comment-body' and @checked='checked']");

    // Check node fields.
    $this->assertFieldByXPath("//input[@id='edit-settings-node-article-fields-comment' and @checked='checked']");
    $this->assertFieldByXPath("//input[@id='edit-settings-node-article-fields-field-image' and @checked='checked']");
    $this->assertFieldByXPath("//input[@id='edit-settings-node-article-fields-field-tags' and @checked='checked']");

    // Check user fields.
    $this->assertFieldByXPath("//input[@id='edit-settings-user-user-fields-user-picture' and @checked='checked']");
  }

}
