<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentTranslationUITest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\content_translation\Tests\ContentTranslationUITest;
use Drupal\field\Entity\FieldConfig;

/**
 * Tests the Comment Translation UI.
 */
class CommentTranslationUITest extends ContentTranslationUITest {

  /**
   * The subject of the test comment.
   */
  protected $subject;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'content_translation', 'node', 'comment');

  public static function getInfo() {
    return array(
      'name' => 'Comment translation UI',
      'description' => 'Tests the basic comment translation UI.',
      'group' => 'Comment',
    );
  }

  function setUp() {
    $this->entityTypeId = 'comment';
    $this->nodeBundle = 'article';
    $this->bundle = 'node__comment_article';
    $this->testLanguageSelector = FALSE;
    $this->subject = $this->randomName();
    parent::setUp();
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::setupBundle().
   */
  function setupBundle() {
    parent::setupBundle();
    $this->drupalCreateContentType(array('type' => $this->nodeBundle, 'name' => $this->nodeBundle));
    // Add a comment field to the article content type.
    $this->container->get('comment.manager')->addDefaultField('node', 'article', 'comment_article');
    // Create a page content type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'page'));
    // Add a comment field to the page content type - this one won't be
    // translatable.
    $this->container->get('comment.manager')->addDefaultField('node', 'page', 'comment');
    // Mark this bundle as translatable.
    content_translation_set_config('comment', 'node__comment_article', 'enabled', TRUE);
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getTranslatorPermission().
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), array('post comments', 'administer comments', 'access comments'));
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::setupTestFields().
   */
  function setupTestFields() {
    parent::setupTestFields();
    $field = FieldConfig::loadByName('comment', 'comment_body');
    $field->translatable = TRUE;
    $field->save();
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::createEntity().
   */
  protected function createEntity($values, $langcode, $node_bundle = 'node__comment_article') {
    // The argument is called 'node_bundle' but its actually just the entity
    // bundle. Comment entity's bundle is of the form
    // {entity_type}__{field_name}. Based on the passed bundle we need to
    // determine the type of node and the name of the comment field.
    if ($node_bundle == 'node__comment_article') {
      // This is the article node type, with the 'comment_article' field.
      $node_type = 'article';
      $field_name = 'comment_article';
    }
    else {
      // This is the page node type with the non-translatable 'comment' field.
      $node_type = 'page';
      $field_name = 'comment';
    }
    $node = $this->drupalCreateNode(array(
      'type' => $node_type,
      $field_name => array(
        array('status' => CommentItemInterface::OPEN)
      ),
    ));
    $values['entity_id'] = $node->id();
    $values['entity_type'] = 'node';
    $values['field_id'] = $node_bundle;
    $values['uid'] = $node->getOwnerId();
    return parent::createEntity($values, $langcode, $node_bundle);
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getNewEntityValues().
   */
  protected function getNewEntityValues($langcode) {
    // Comment subject is not translatable hence we use a fixed value.
    return array(
      'subject' => $this->subject,
      'comment_body' => array(array('value' => $this->randomName(16))),
    ) + parent::getNewEntityValues($langcode);
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::assertPublishedStatus().
   */
  protected function assertPublishedStatus() {
    parent::assertPublishedStatus();
    $entity = entity_load($this->entityTypeId, $this->entityId);
    $user = $this->drupalCreateUser(array('access comments'));
    $this->drupalLogin($user);
    $languages = $this->container->get('language_manager')->getLanguages();

    // Check that simple users cannot see unpublished field translations.
    $path = $entity->getSystemPath();
    foreach ($this->langcodes as $index => $langcode) {
      $translation = $this->getTranslation($entity, $langcode);
      $value = $this->getValue($translation, 'comment_body', $langcode);
      $this->drupalGet($path, array('language' => $languages[$langcode]));
      if ($index > 0) {
        $this->assertNoRaw($value, 'Unpublished field translation is not shown.');
      }
      else {
        $this->assertRaw($value, 'Published field translation is shown.');
      }
    }

    // Login as translator again to ensure subsequent tests do not break.
    $this->drupalLogin($this->translator);
  }

  /**
   * Tests translate link on comment content admin page.
   */
  function testTranslateLinkCommentAdminPage() {
    $this->admin_user = $this->drupalCreateUser(array_merge(parent::getTranslatorPermissions(), array('access administration pages', 'administer comments', 'skip comment approval')));
    $this->drupalLogin($this->admin_user);

    $cid_translatable = $this->createEntity(array(), $this->langcodes[0]);
    $cid_untranslatable = $this->createEntity(array(), $this->langcodes[0], 'node__comment');

    // Verify translation links.
    $this->drupalGet('admin/content/comment');
    $this->assertResponse(200);
    $this->assertLinkByHref('comment/' . $cid_translatable . '/translations');
    $this->assertNoLinkByHref('comment/' . $cid_untranslatable . '/translations');
  }

}
