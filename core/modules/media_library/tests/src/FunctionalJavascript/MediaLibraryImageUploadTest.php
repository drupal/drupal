<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests the handling of images uploaded to the media library.
 *
 * @group media_library
 */
class MediaLibraryImageUploadTest extends MediaLibraryTestBase {

  use EntityReferenceTestTrait;
  use MediaTypeCreationTrait;

  /**
   * Tests that oversized images are automatically resized on upload.
   */
  public function testImageResizing() {
    // Create a media type that only accepts images up to 16x16 in size.
    $media_type = $this->createMediaType('image');
    $media_type->getSource()
      ->getSourceFieldDefinition($media_type)
      ->setSetting('max_resolution', '16x16')
      ->save();

    $node_type = $this->drupalCreateContentType()->id();
    $this->createEntityReferenceField('node', $node_type, 'field_icon', 'Icon', 'media');
    $this->container->get('entity_display.repository')
      ->getFormDisplay('node', $node_type)
      ->setComponent('field_icon', [
        'type' => 'media_library_widget',
      ])
      ->save();

    $account = $this->drupalCreateUser([
      "create $node_type content",
      'create ' . $media_type->id() . ' media',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet("/node/add/$node_type");
    $this->openMediaLibraryForField('field_icon');

    $image_uri = uniqid('public://') . '.png';
    $image_uri = $this->getRandomGenerator()->image($image_uri, '16x16', '32x32');
    $image_path = $this->container->get('file_system')->realpath($image_uri);
    $this->assertNotEmpty($image_path);
    $this->assertFileExists($image_path);

    $this->waitForFieldExists('Add file')->attachFile($image_path);
    $this->waitForText('The image was resized to fit within the maximum allowed dimensions of 16x16 pixels.');
  }

}
