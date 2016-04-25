<?php

namespace Drupal\content_translation\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the content translation behaviours on entity bundle UI.
 *
 * @group content_translation
 */
class ContentTranslationEntityBundleUITest extends WebTestBase {

  public static $modules = array('language', 'content_translation', 'node', 'comment', 'field_ui');

  protected function setUp() {
    parent::setUp();
    $user = $this->drupalCreateUser(array('access administration pages', 'administer languages', 'administer content translation', 'administer content types'));
    $this->drupalLogin($user);
  }

  /**
   * Tests content types default translation behaviour.
   */
  public function testContentTypeUI() {
    // Create first content type.
    $this->drupalCreateContentType(array('type' => 'article'));
    // Enable content translation.
    $edit = array('language_configuration[content_translation]' => TRUE);
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, 'Save content type');

    // Make sure add page does not inherit translation configuration from first
    // content type.
    $this->drupalGet('admin/structure/types/add');
    $this->assertNoFieldChecked('edit-language-configuration-content-translation');

    // Create second content type and set content translation.
    $edit = array(
      'name' => 'Page',
      'type' => 'page',
      'language_configuration[content_translation]' => TRUE,
    );
    $this->drupalPostForm('admin/structure/types/add', $edit, 'Save and manage fields');

    // Make sure the settings are saved when creating the content type.
    $this->drupalGet('admin/structure/types/manage/page');
    $this->assertFieldChecked('edit-language-configuration-content-translation');

  }

}
