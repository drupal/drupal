<?php

namespace Drupal\Tests\comment\Functional;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\content_translation\Functional\ContentTranslationUITestBase;

/**
 * Tests the Comment Translation UI.
 *
 * @group comment
 */
class CommentTranslationUITest extends ContentTranslationUITestBase {

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
   * {inheritdoc}
   */
  protected $defaultCacheContexts = [
    'languages:language_interface',
    'session',
    'theme',
    'timezone',
    'url.query_args:_wrapper_format',
    'url.query_args.pagers:0',
    'user.permissions',
    'user.roles',
  ];

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['language', 'content_translation', 'node', 'comment'];

  protected function setUp() {
    $this->entityTypeId = 'comment';
    $this->nodeBundle = 'article';
    $this->bundle = 'comment_article';
    $this->testLanguageSelector = FALSE;
    $this->subject = $this->randomMachineName();
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  public function setupBundle() {
    parent::setupBundle();
    $this->drupalCreateContentType(['type' => $this->nodeBundle, 'name' => $this->nodeBundle]);
    // Add a comment field to the article content type.
    $this->addDefaultCommentField('node', 'article', 'comment_article', CommentItemInterface::OPEN, 'comment_article');
    // Create a page content type.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'page']);
    // Add a comment field to the page content type - this one won't be
    // translatable.
    $this->addDefaultCommentField('node', 'page', 'comment');
    // Mark this bundle as translatable.
    $this->container->get('content_translation.manager')->setEnabled('comment', 'comment_article', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), ['post comments', 'administer comments', 'access comments']);
  }

  /**
   * {@inheritdoc}
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
    $node = $this->drupalCreateNode([
      'type' => $node_type,
      $field_name => [
        ['status' => CommentItemInterface::OPEN]
      ],
    ]);
    $values['entity_id'] = $node->id();
    $values['entity_type'] = 'node';
    $values['field_name'] = $field_name;
    $values['uid'] = $node->getOwnerId();
    return parent::createEntity($values, $langcode, $comment_type);
  }

  /**
   * {@inheritdoc}
   */
  protected function getNewEntityValues($langcode) {
    // Comment subject is not translatable hence we use a fixed value.
    return [
      'subject' => [['value' => $this->subject]],
      'comment_body' => [['value' => $this->randomMachineName(16)]],
    ] + parent::getNewEntityValues($langcode);
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
        $edit = ['status' => 0];
        $url = $entity->urlInfo('edit-form', ['language' => ConfigurableLanguage::load($langcode)]);
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
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $languages = $this->container->get('language_manager')->getLanguages();
    $values = [];

    // Post different authoring information for each translation.
    foreach ($this->langcodes as $langcode) {
      $url = $entity->urlInfo('edit-form', ['language' => $languages[$langcode]]);
      $user = $this->drupalCreateUser();
      $values[$langcode] = [
        'uid' => $user->id(),
        'created' => REQUEST_TIME - mt_rand(0, 1000),
      ];
      $edit = [
        'uid' => $user->getUsername() . ' (' . $user->id() . ')',
        'date[date]' => format_date($values[$langcode]['created'], 'custom', 'Y-m-d'),
        'date[time]' => format_date($values[$langcode]['created'], 'custom', 'H:i:s'),
      ];
      $this->drupalPostForm($url, $edit, $this->getFormSubmitAction($entity, $langcode));
    }

    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    foreach ($this->langcodes as $langcode) {
      $metadata = $this->manager->getTranslationMetadata($entity->getTranslation($langcode));
      $this->assertEqual($metadata->getAuthor()->id(), $values[$langcode]['uid'], 'Translation author correctly stored.');
      $this->assertEqual($metadata->getCreatedTime(), $values[$langcode]['created'], 'Translation date correctly stored.');
    }
  }

  /**
   * Tests translate link on comment content admin page.
   */
  public function testTranslateLinkCommentAdminPage() {
    $this->adminUser = $this->drupalCreateUser(array_merge(parent::getTranslatorPermissions(), ['access administration pages', 'administer comments', 'skip comment approval']));
    $this->drupalLogin($this->adminUser);

    $cid_translatable = $this->createEntity([], $this->langcodes[0]);
    $cid_untranslatable = $this->createEntity([], $this->langcodes[0], 'comment');

    // Verify translation links.
    $this->drupalGet('admin/content/comment');
    $this->assertResponse(200);
    $this->assertLinkByHref('comment/' . $cid_translatable . '/translations');
    $this->assertNoLinkByHref('comment/' . $cid_untranslatable . '/translations');
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestTranslationEdit() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $languages = $this->container->get('language_manager')->getLanguages();

    foreach ($this->langcodes as $langcode) {
      // We only want to test the title for non-english translations.
      if ($langcode != 'en') {
        $options = ['language' => $languages[$langcode]];
        $url = $entity->urlInfo('edit-form', $options);
        $this->drupalGet($url);

        $title = t('Edit @type @title [%language translation]', [
          '@type' => $this->entityTypeId,
          '@title' => $entity->getTranslation($langcode)->label(),
          '%language' => $languages[$langcode]->getName(),
        ]);
        $this->assertRaw($title);
      }
    }
  }

}
