<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Functional\FieldFormatter;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\Plugin\Field\FieldFormatter\MediaThumbnailFormatter;
use Drupal\Tests\media\Functional\MediaFunctionalTestBase;
use Drupal\Tests\TestFileCreationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Media Thumbnail Formatter.
 */
#[Group('media')]
#[CoversClass(MediaThumbnailFormatter::class)]
#[RunTestsInSeparateProcesses]
class MediaThumbnailFormatterTest extends MediaFunctionalTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the media thumbnail field formatter.
   */
  public function testRender(): void {
    $this->drupalLogin($this->adminUser);

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

    // Change the image thumbnail to point into the media.
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
    $title = $this->randomMachineName();
    $edit = [
      'title[0][value]' => $title,
    ];
    $edit['field_media_reference[0][target_id]'] = $mediaImage->getName();
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');

    // Validate the image being loaded with the media reference.
    $this->assertSession()->responseContains('<a href="' . $mediaImage->toUrl('edit-form')->toString());

    // Retrieve the created node.
    $node = $this->drupalGetNodeByTitle($title);
    $nid = $node->id();

    // Change the image thumbnail to point into the content node.
    $this->changeMediaReferenceFieldLinkType('content');
    $this->drupalGet('node/' . $nid);

    // Validate image being loaded with the content on the link.
    $this->assertSession()->responseContains('<a href="' . $node->toUrl()->toString());
    $this->assertSession()->responseContains('loading="eager"');
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
          'image_loading' => ['attribute' => 'eager'],
        ],
      ])
      ->save();
  }

}
