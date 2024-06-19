<?php

declare(strict_types=1);

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\media\Entity\Media;
use Drupal\media\Plugin\media\Source\File;

/**
 * Tests the file media source.
 *
 * @group media
 */
class MediaSourceFileTest extends MediaSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the file media source.
   */
  public function testMediaFileSource(): void {
    // Skipped due to frequent random test failures.
    $this->markTestSkipped();
    $media_type_id = 'test_media_file_type';
    $source_field_id = 'field_media_file';
    $provided_fields = [
      File::METADATA_ATTRIBUTE_NAME,
      File::METADATA_ATTRIBUTE_SIZE,
      File::METADATA_ATTRIBUTE_MIME,
    ];

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->doTestCreateMediaType($media_type_id, 'file', $provided_fields);

    // Create custom fields for the media type to store metadata attributes.
    $fields = [
      'field_string_file_size' => 'string',
      'field_string_mime_type' => 'string',
    ];
    $this->createMediaTypeFields($fields, $media_type_id);

    // Hide the name field widget to test default name generation.
    $this->hideMediaTypeFieldWidget('name', $media_type_id);

    $this->drupalGet("admin/structure/media/manage/{$media_type_id}");
    $page->selectFieldOption("field_map[" . File::METADATA_ATTRIBUTE_NAME . "]", 'name');
    $page->selectFieldOption("field_map[" . File::METADATA_ATTRIBUTE_SIZE . "]", 'field_string_file_size');
    $page->selectFieldOption("field_map[" . File::METADATA_ATTRIBUTE_MIME . "]", 'field_string_mime_type');
    $page->pressButton('Save');

    $test_filename = $this->randomMachineName() . '.txt';
    $test_filepath = 'public://' . $test_filename;
    file_put_contents($test_filepath, $this->randomMachineName());

    // Create a media item.
    $this->drupalGet("media/add/{$media_type_id}");
    $page->attachFileToField("files[{$source_field_id}_0]", \Drupal::service('file_system')->realpath($test_filepath));
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->pressButton('Save');

    $assert_session->addressEquals('admin/content/media');

    // Get the media entity view URL from the creation message.
    $this->drupalGet($this->assertLinkToCreatedMedia());

    // Make sure a link to the file is displayed.
    $assert_session->linkExists($test_filename);
    // The thumbnail should not be displayed.
    $assert_session->elementNotExists('css', 'img');

    // Make sure checkbox changes the visibility of log message field.
    $this->drupalGet("media/1/edit");
    $page->uncheckField('revision');
    $assert_session->elementAttributeContains('css', '.field--name-revision-log-message', 'style', 'display: none');
    $page->checkField('revision');
    $assert_session->elementAttributeNotContains('css', '.field--name-revision-log-message', 'style', 'display');

    // Load the media and check that all the fields are properly populated.
    $media = Media::load(1);
    $this->assertSame($test_filename, $media->getName());
    $this->assertSame('8', $media->get('field_string_file_size')->value);
    $this->assertSame('text/plain', $media->get('field_string_mime_type')->value);

    // Test the MIME type icon.
    $icon_base = \Drupal::config('media.settings')->get('icon_base_uri');
    \Drupal::service('file_system')->copy($icon_base . '/generic.png', $icon_base . '/text--plain.png');
    $this->drupalGet("media/add/{$media_type_id}");
    $page->attachFileToField("files[{$source_field_id}_0]", \Drupal::service('file_system')->realpath($test_filepath));
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->pressButton('Save');
    $assert_session->elementAttributeContains('css', 'img', 'src', 'text--plain.png');

    // Check if the mapped name is automatically updated.
    $new_filename = $this->randomMachineName() . '.txt';
    $new_filepath = 'public://' . $new_filename;
    file_put_contents($new_filepath, $this->randomMachineName());
    $this->drupalGet("media/1/edit");
    $page->pressButton('Remove');
    $result = $assert_session->waitForField("files[{$source_field_id}_0]");
    $this->assertNotEmpty($result);
    $page->attachFileToField("files[{$source_field_id}_0]", \Drupal::service('file_system')->realpath($new_filepath));
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->pressButton('Save');
    /** @var \Drupal\media\MediaInterface $media */
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadUnchanged(1);
    $this->assertEquals($new_filename, $media->getName());
    $assert_session->statusMessageContains("$new_filename has been updated.", 'status');
  }

}
