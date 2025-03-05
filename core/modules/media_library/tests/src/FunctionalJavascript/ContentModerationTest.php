<?php

declare(strict_types=1);

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

// cspell:ignore hoglet

/**
 * Tests media library integration with content moderation.
 *
 * @group media_library
 */
class ContentModerationTest extends WebDriverTestBase {

  use ContentModerationTestTrait;
  use EntityReferenceFieldCreationTrait;
  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'field',
    'media',
    'media_library',
    'node',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User with the 'administer media' permission.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userAdmin;

  /**
   * User with the 'view media' permission.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userViewer;

  /**
   * User with the 'view media' and 'view own unpublished media' permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userViewOwnUnpublished;

  /**
   * User with the 'view media' and 'view any unpublished content' permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userViewAnyUnpublished;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an image media type and article node type.
    $this->createMediaType('image', ['id' => 'image']);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Create a media reference field on articles.
    $this->createEntityReferenceField(
      'node',
      'article',
      'field_media',
      'Media',
      'media',
      'default',
      ['target_bundles' => ['image']],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    // Add the media field to the form display.
    $form_display = \Drupal::service('entity_display.repository')->getFormDisplay('node', 'article', 'default');
    $form_display->setComponent('field_media', [
      'type' => 'media_library_widget',
    ])->save();

    // Configure the "Editorial" workflow to apply to image media.
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('media', 'image');
    $workflow->save();

    $image = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $image->setPermanent();
    $image->save();

    // Create a draft, published and archived media item.
    $draft_media = Media::create([
      'name' => 'Hoglet',
      'bundle' => 'image',
      'field_media_image' => $image,
      'moderation_state' => 'draft',
    ]);
    $draft_media->save();
    $published_media = Media::create([
      'name' => 'Panda',
      'bundle' => 'image',
      'field_media_image' => $image,
      'moderation_state' => 'published',
    ]);
    $published_media->save();
    $archived_media = Media::create([
      'name' => 'Mammoth',
      'bundle' => 'image',
      'field_media_image' => $image,
      'moderation_state' => 'archived',
    ]);
    $archived_media->save();

    // Create some users for our tests. We want to check with user 1, a media
    // administrator with 'administer media' permissions, a user that has the
    // 'view media' permissions, a user that can 'view media' and 'view own
    // unpublished media', and a user that has 'view media' and 'view any
    // unpublished content' permissions.
    $this->userAdmin = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'access media overview',
      'edit own article content',
      'create article content',
      'administer media',
    ]);
    $this->userViewer = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'access media overview',
      'edit own article content',
      'create article content',
      'view media',
      'create media',
    ]);
    $this->userViewOwnUnpublished = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'access media overview',
      'edit own article content',
      'create article content',
      'view media',
      'view own unpublished media',
      'create media',
    ]);
    $this->userViewAnyUnpublished = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'access media overview',
      'edit own article content',
      'create article content',
      'view media',
      'create media',
      'view any unpublished content',
    ]);
  }

  /**
   * Tests the media library widget only shows published media.
   */
  public function testAdministrationPage(): void {
    // The media admin user should be able to see all media items.
    $this->drupalLogin($this->userAdmin);
    $this->drupalGet('admin/content/media');
    $this->assertAllMedia();

    // The media viewer user should be able to see only published media items.
    $this->drupalLogin($this->userViewer);
    $this->drupalGet('admin/content/media');
    $this->assertOnlyPublishedMedia();

    // The media viewer user that can also view its own unpublished media should
    // also be able to see only published media items since it is not the owner
    // of the created media items.
    $this->drupalLogin($this->userViewOwnUnpublished);
    $this->drupalGet('admin/content/media');
    $this->assertOnlyPublishedMedia();

    // When content moderation is enabled, a media viewer that can view any
    // unpublished content should be able to see all media.
    // @see content_moderation_entity_access()
    $this->drupalLogin($this->userViewAnyUnpublished);
    $this->drupalGet('admin/content/media');
    $this->assertAllMedia();

    // Assign all media to the user with the 'view own unpublished media'
    // permission.
    foreach (Media::loadMultiple() as $media) {
      $media->setOwner($this->userViewOwnUnpublished);
      $media->save();
    }

    // The media admin user should still be able to see all media items.
    $this->drupalLogin($this->userAdmin);
    $this->drupalGet('admin/content/media');
    $this->assertAllMedia();

    // The media viewer user should still be able to see only published media
    // items.
    $this->drupalLogin($this->userViewer);
    $this->drupalGet('admin/content/media');
    $this->assertOnlyPublishedMedia();

    // The media viewer user that can also view its own unpublished media
    // should now be able to see all media items since it is the owner of the
    // created media items.
    $this->drupalLogin($this->userViewOwnUnpublished);
    $this->drupalGet('admin/content/media');
    $this->assertAllMedia();

    // The media viewer that can view any unpublished content should still be
    // able to see all media.
    $this->drupalLogin($this->userViewAnyUnpublished);
    $this->drupalGet('admin/content/media');
    $this->assertAllMedia();
  }

  /**
   * Tests the media library widget only shows published media.
   */
  public function testWidget(): void {
    $assert_session = $this->assertSession();

    // All users should only be able to see published media items.
    $this->drupalLogin($this->userAdmin);
    $this->drupalGet('node/add/article');
    $assert_session->elementExists('css', '.js-media-library-open-button[name^="field_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertOnlyPublishedMedia();
    $this->drupalLogin($this->userViewer);
    $this->drupalGet('node/add/article');
    $assert_session->elementExists('css', '.js-media-library-open-button[name^="field_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertOnlyPublishedMedia();
    $this->drupalLogin($this->userViewOwnUnpublished);
    $this->drupalGet('node/add/article');
    $assert_session->elementExists('css', '.js-media-library-open-button[name^="field_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertOnlyPublishedMedia();
    $this->drupalLogin($this->userViewAnyUnpublished);
    $this->drupalGet('node/add/article');
    $assert_session->elementExists('css', '.js-media-library-open-button[name^="field_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertOnlyPublishedMedia();

    // After we change the owner to the user with 'view own unpublished media'
    // permission, all users should still only be able to see published media.
    foreach (Media::loadMultiple() as $media) {
      $media->setOwner($this->userViewOwnUnpublished);
      $media->save();
    }

    $this->drupalLogin($this->userAdmin);
    $this->drupalGet('node/add/article');
    $assert_session->elementExists('css', '.js-media-library-open-button[name^="field_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertOnlyPublishedMedia();
    $this->drupalLogin($this->userViewer);
    $this->drupalGet('node/add/article');
    $assert_session->elementExists('css', '.js-media-library-open-button[name^="field_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertOnlyPublishedMedia();
    $this->drupalLogin($this->userViewOwnUnpublished);
    $this->drupalGet('node/add/article');
    $assert_session->elementExists('css', '.js-media-library-open-button[name^="field_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertOnlyPublishedMedia();
    $this->drupalLogin($this->userViewAnyUnpublished);
    $this->drupalGet('node/add/article');
    $assert_session->elementExists('css', '.js-media-library-open-button[name^="field_media"]')->click();
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertOnlyPublishedMedia();
  }

  /**
   * Asserts all media items are visible.
   *
   * @internal
   */
  protected function assertAllMedia(): void {
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Hoglet');
    $assert_session->pageTextContains('Panda');
    $assert_session->pageTextContains('Mammoth');
  }

  /**
   * Asserts only published media items are visible.
   *
   * @internal
   */
  protected function assertOnlyPublishedMedia(): void {
    $assert_session = $this->assertSession();
    $assert_session->pageTextNotContains('Hoglet');
    $assert_session->pageTextContains('Panda');
    $assert_session->pageTextNotContains('Mammoth');
  }

}
