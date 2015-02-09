<?php

/**
 * @file
 * Definition of Drupal\comment\Tests\CommentTranslationUITest.
 */

namespace Drupal\comment\Tests;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\content_translation\Tests\ContentTranslationUITest;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the Comment Translation UI.
 *
 * @group comment
 */
class CommentTranslationUITest extends ContentTranslationUITest {

  use CommentTestTrait;

  /**
   * The subject of the test comment.
   */
  protected $subject;

  /**
   * An administrative user with permission to administer comments.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('language', 'content_translation', 'node', 'comment');

  protected function setUp() {
    $this->entityTypeId = 'comment';
    $this->nodeBundle = 'article';
    $this->bundle = 'comment_article';
    $this->testLanguageSelector = FALSE;
    $this->subject = $this->randomMachineName();
    parent::setUp();
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::setupBundle().
   */
  function setupBundle() {
    parent::setupBundle();
    $this->drupalCreateContentType(array('type' => $this->nodeBundle, 'name' => $this->nodeBundle));
    // Add a comment field to the article content type.
    $this->addDefaultCommentField('node', 'article', 'comment_article', CommentItemInterface::OPEN, 'comment_article');
    // Create a page content type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'page'));
    // Add a comment field to the page content type - this one won't be
    // translatable.
    $this->addDefaultCommentField('node', 'page', 'comment');
    // Mark this bundle as translatable.
    $this->container->get('content_translation.manager')->setEnabled('comment', 'comment_article', TRUE);
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getTranslatorPermission().
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), array('post comments', 'administer comments', 'access comments'));
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::createEntity().
   */
  protected function createEntity($values, $langcode, $comment_type = 'comment_article') {
    if ($comment_type == 'comment_article') {
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
    $values['field_name'] = $field_name;
    $values['uid'] = $node->getOwnerId();
    return parent::createEntity($values, $langcode, $comment_type);
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getNewEntityValues().
   */
  protected function getNewEntityValues($langcode) {
    // Comment subject is not translatable hence we use a fixed value.
    return array(
      'subject' => array(array('value' => $this->subject)),
      'comment_body' => array(array('value' => $this->randomMachineName(16))),
    ) + parent::getNewEntityValues($langcode);
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestPublishedStatus() {
    $entity_manager = \Drupal::entityManager();
    $storage = $entity_manager->getStorage($this->entityTypeId);

    $storage->resetCache();
    $entity = $storage->load($this->entityId);

    // Unpublish translations.
    foreach ($this->langcodes as $index => $langcode) {
      if ($index > 0) {
        $edit = array('status' => 0);
        $url = $entity->urlInfo('edit-form', array('language' => ConfigurableLanguage::load($langcode)));
        $this->drupalPostForm($url, $edit, $this->getFormSubmitAction($entity, $langcode));
        $storage->resetCache();
        $entity = $storage->load($this->entityId);
        $this->assertFalse($this->manager->getTranslationMetadata($entity->getTranslation($langcode))->isPublished(), 'The translation has been correctly unpublished.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestAuthoringInfo() {
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $path = $entity->getSystemPath('edit-form');
    $languages = $this->container->get('language_manager')->getLanguages();
    $values = array();

    // Post different authoring information for each translation.
    foreach ($this->langcodes as $langcode) {
      $user = $this->drupalCreateUser();
      $values[$langcode] = array(
        'uid' => $user->id(),
        'created' => REQUEST_TIME - mt_rand(0, 1000),
      );
      $edit = array(
        'name' => $user->getUsername(),
        'date[date]' => format_date($values[$langcode]['created'], 'custom', 'Y-m-d'),
        'date[time]' => format_date($values[$langcode]['created'], 'custom', 'H:i:s'),
      );
      $this->drupalPostForm($path, $edit, $this->getFormSubmitAction($entity, $langcode), array('language' => $languages[$langcode]));
    }

    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    foreach ($this->langcodes as $langcode) {
      $metadata = $this->manager->getTranslationMetadata($entity->getTranslation($langcode));
      $this->assertEqual($metadata->getAuthor()->id(), $values[$langcode]['uid'], 'Translation author correctly stored.');
      $this->assertEqual($metadata->getCreatedTime(), $values[$langcode]['created'], 'Translation date correctly stored.');
    }
  }

  /**
   * Tests translate link on comment content admin page.
   */
  function testTranslateLinkCommentAdminPage() {
    $this->adminUser = $this->drupalCreateUser(array_merge(parent::getTranslatorPermissions(), array('access administration pages', 'administer comments', 'skip comment approval')));
    $this->drupalLogin($this->adminUser);

    $cid_translatable = $this->createEntity(array(), $this->langcodes[0]);
    $cid_untranslatable = $this->createEntity(array(), $this->langcodes[0], 'comment');

    // Verify translation links.
    $this->drupalGet('admin/content/comment');
    $this->assertResponse(200);
    $this->assertLinkByHref('comment/' . $cid_translatable . '/translations');
    $this->assertNoLinkByHref('comment/' . $cid_untranslatable . '/translations');
  }

}
