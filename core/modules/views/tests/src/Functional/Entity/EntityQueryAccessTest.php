<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Entity;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests that Views respects 'ENTITY_TYPE_access' query tags.
 *
 * @group views
 */
class EntityQueryAccessTest extends ViewTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media_test_source',
    'views_test_query_access',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the 'media_access' query tag is respected by Views.
   */
  public function testMediaEntityQueryAccess(): void {
    $this->container->get('module_installer')->install(['media']);

    $media_type = $this->createMediaType('test');
    $source_field = $media_type->getSource()
      ->getSourceFieldDefinition($media_type)
      ->getName();

    $hidden_media = Media::create([
      'bundle' => $media_type->id(),
      // This UUID should prevent this media item from being visible in the
      // view.
      // @see views_test_access_query_media_access_alter()
      'uuid' => 'hidden-media',
      'name' => $this->randomString(),
      $source_field => $this->randomString(),
    ]);
    $hidden_media->save();

    $accessible_media = Media::create([
      'bundle' => $media_type->id(),
      'name' => $this->randomString(),
      $source_field => $this->randomString(),
    ]);
    $accessible_media->save();

    $account = $this->drupalCreateUser([
      'access media overview',
      'administer media',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/content/media');
    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    $assert_session->linkExists($accessible_media->label());
    $assert_session->linkNotExists($hidden_media->label());
  }

  /**
   * Tests that the 'block_content_access' query tag is respected by Views.
   */
  public function testBlockContentEntityQueryAccess(): void {
    $this->container->get('module_installer')->install(['block_content']);

    BlockContentType::create([
      'id' => 'test',
      'label' => 'Test',
    ])->save();

    $hidden_block = BlockContent::create([
      'type' => 'test',
      // This UUID should prevent this block from being visible in the view.
      // @see views_test_access_query_block_content_access_alter()
      'uuid' => 'hidden-block_content',
      'info' => $this->randomString(),
    ]);
    $hidden_block->save();

    $accessible_block = BlockContent::create([
      'type' => 'test',
      'info' => $this->randomString(),
    ]);
    $accessible_block->save();

    $account = $this->drupalCreateUser([
      'access block library',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/content/block');
    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains($accessible_block->label());
    $assert_session->pageTextNotContains($hidden_block->label());
  }

}
