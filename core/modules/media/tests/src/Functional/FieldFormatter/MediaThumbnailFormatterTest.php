<?php

namespace Drupal\Tests\media\Functional\FieldFormatter;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Functional\MediaFunctionalTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * @covers \Drupal\media\Plugin\Field\FieldFormatter\MediaThumbnailFormatter
 *
 * @group media
 */
class MediaThumbnailFormatterTest extends MediaFunctionalTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the media thumbnail field formatter.
   */
  public function testRender() {
    $this->drupalLogin($this->adminUser);

    /** @var \Drupal\Core\Render\Renderer $renderer */
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\node\NodeStorage $node_storage */
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // Create an image media type for testing the formatter.
    $this->createMediaType('image', ['id' => 'image']);

    // Create an article content type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    // Creates an entity reference field for media.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media_reference',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'cardinality' => 1,
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => 'Reference media',
      'translatable' => FALSE,
    ])->save();

    // Alter the form display.
    $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'article')
      ->setComponent('field_media_reference', [
        'type' => 'entity_reference_autocomplete',
      ])
      ->save();

    // The first case is validate the image with media link.
    $this->changeMediaReferenceFieldLinkType('media');

    // Create and upload a file to the media.
    $file = File::create([
      'uri' => current($this->getTestFiles('image'))->uri,
    ]);
    $file->save();
    $mediaImage = Media::create([
      'bundle' => 'image',
      'name' => 'Test image',
      'field_media_image' => $file->id(),
    ]);
    $mediaImage->save();

    // Save the article node.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
    ];
    $edit['field_media_reference[0][target_id]'] = $mediaImage->getName();
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');

    // Retrieve node id.
    $matches = [];
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    $nid = $matches[1];

    // Loads the new node entity.
    $node = $node_storage->load($nid);

    /** @var \Drupal\media\Entity\Media $media */
    $media = $node->field_media_reference->entity;
    $image = [
      '#theme' => 'image_formatter',
      '#item' => $media->get('thumbnail')->first(),
      '#item_attributes' => [],
      '#image_style' => '',
      '#url' => $media->toUrl(),
    ];
    // Check the image being loaded.
    $this->assertSession()->responseContains($renderer->renderRoot($image));
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', $media->getCacheTags()[0]);

    // The second scenario is to validate the image thumbnail with content link.
    $this->changeMediaReferenceFieldLinkType('content');
    $node_storage->resetCache([$nid]);

    $image['#url'] = $node->toUrl();
    $this->assertSession()->responseContains($renderer->renderRoot($image));
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', $media->getCacheTags()[0]);
  }

  /**
   * Helper function to change field display.
   *
   * @param string $type
   *   Image link type.
   */
  private function changeMediaReferenceFieldLinkType(string $type): void {
    // Change the display to use the media thumbnail formatter with image link.
    $this->container->get('entity_display.repository')
      ->getViewDisplay('node', 'article', 'default')
      ->setComponent('field_media_reference', [
        'type' => 'media_thumbnail',
        'settings' => [
          'image_link' => $type,
          'image_style' => '',
        ],
      ])
      ->save();
  }

}
