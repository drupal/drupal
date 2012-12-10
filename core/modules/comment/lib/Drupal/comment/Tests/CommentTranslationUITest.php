<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentTranslationUITest.
 */

namespace Drupal\comment\Tests;

use Drupal\translation_entity\Tests\EntityTranslationUITest;

/**
 * Tests the Comment Translation UI.
 */
class CommentTranslationUITest extends EntityTranslationUITest {

  /**
   * The subject of the test comment.
   */
  protected $subject;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'translation_entity', 'node', 'comment');

  public static function getInfo() {
    return array(
      'name' => 'Comment translation UI',
      'description' => 'Tests the basic comment translation UI.',
      'group' => 'Comment',
    );
  }

  /**
   * Overrides \Drupal\simpletest\WebTestBase::setUp().
   */
  function setUp() {
    $this->entityType = 'comment';
    $this->nodeBundle = 'article';
    $this->bundle = 'comment';
    $this->testLanguageSelector = FALSE;
    $this->subject = $this->randomName();
    parent::setUp();
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::setupBundle().
   */
  function setupBundle() {
    parent::setupBundle();
    $this->drupalCreateContentType(array('type' => $this->nodeBundle, 'name' => $this->nodeBundle));
    // Add a comment field to the article content type.
    comment_add_default_comment_field('node', 'article');
    // Mark this bundle as translatable.
    translation_entity_set_config('comment', 'comment', 'enabled', TRUE);
    // Refresh entity info.
    entity_info_cache_clear();
    // Flush the permissions after adding the translatable comment bundle.
    $this->checkPermissions(array(), TRUE);
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::getTranslatorPermission().
   */
  function getTranslatorPermissions() {
    return array('post comments', 'administer comments', "translate $this->entityType entities", 'edit original values');
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::setupTestFields().
   */
  function setupTestFields() {
    parent::setupTestFields();
    $field = field_info_field('comment_body');
    $field['translatable'] = TRUE;
    field_update_field($field);
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::createEntity().
   */
  protected function createEntity($values, $langcode, $bundle_name = NULL) {
    if (!isset($bundle_name)) {
      $bundle_name = $this->nodeBundle;
    }
    $node = $this->drupalCreateNode(array(
      'type' => $bundle_name,
      'comment' => array(LANGUAGE_NOT_SPECIFIED => array(
        array('comment' => COMMENT_OPEN)
      ))
    ));
    $values['entity_id'] = $node->nid;
    $values['entity_type'] = 'node';
    $values['field_name'] = 'comment';
    $values['uid'] = $node->uid;
    return parent::createEntity($values, $langcode, $bundle_name);
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::getNewEntityValues().
   */
  protected function getNewEntityValues($langcode) {
    // Comment subject is not translatable hence we use a fixed value.
    return array(
      'subject' => $this->subject,
      'comment_body' => array(array('value' => $this->randomString(16))),
    ) + parent::getNewEntityValues($langcode);
  }

  /**
   * Tests translate link on comment content admin page.
   */
  function testTranslateLinkCommentAdminPage() {
    $this->admin_user = $this->drupalCreateUser(array('access administration pages', 'administer comments', 'translate any entity'));
    $this->drupalLogin($this->admin_user);

    $cid_translatable = $this->createEntity(array(), $this->langcodes[0], $this->nodeBundle);
    $cid_untranslatable = $this->createEntity(array(), $this->langcodes[0], 'page');

    // Verify translation links.
    $this->drupalGet('admin/content/comment');
    $this->assertResponse(200);
    $this->assertLinkByHref('comment/' . $cid_translatable . '/translations');
    $this->assertNoLinkByHref('comment/' . $cid_untranslatable . '/translations');
  }

}
