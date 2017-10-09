<?php

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\media\Entity\Media;
use Drupal\media\Plugin\media\Source\Image;

/**
 * Tests the image media source.
 *
 * @group media
 */
class MediaSourceImageTest extends MediaSourceTestBase {

  /**
   * Tests the image media source.
   */
  public function testMediaImageSource() {
    $media_type_id = 'test_media_image_type';
    $source_field_id = 'field_media_image';
    $provided_fields = [
      Image::METADATA_ATTRIBUTE_WIDTH,
      Image::METADATA_ATTRIBUTE_HEIGHT,
    ];

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->doTestCreateMediaType($media_type_id, 'image', $provided_fields);

    // Create custom fields for the media type to store metadata attributes.
    $fields = [
      'field_string_width' => 'string',
      'field_string_height' => 'string',
    ];
    $this->createMediaTypeFields($fields, $media_type_id);

    // Hide the name field widget to test default name generation.
    $this->hideMediaTypeFieldWidget('name', $media_type_id);

    $this->drupalGet("admin/structure/media/manage/{$media_type_id}");
    $page->selectFieldOption("field_map[" . Image::METADATA_ATTRIBUTE_WIDTH . "]", 'field_string_width');
    $page->selectFieldOption("field_map[" . Image::METADATA_ATTRIBUTE_HEIGHT . "]", 'field_string_height');
    $page->pressButton('Save');

    // Create a media item.
    $this->drupalGet("media/add/{$media_type_id}");
    $page->attachFileToField("files[{$source_field_id}_0]", \Drupal::root() . '/core/modules/media/tests/fixtures/example_1.jpeg');
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->fillField("{$source_field_id}[0][alt]", 'Image Alt Text 1');
    $page->pressButton('Save');

    $assert_session->addressEquals('media/1');

    // Make sure the thumbnail is displayed from uploaded image.
    $assert_session->elementAttributeContains('css', '.image-style-thumbnail', 'src', 'example_1.jpeg');

    // Load the media and check that all fields are properly populated.
    $media = Media::load(1);
    $this->assertEquals('example_1.jpeg', $media->getName());
    $this->assertEquals('200', $media->get('field_string_width')->value);
    $this->assertEquals('89', $media->get('field_string_height')->value);
  }

}
