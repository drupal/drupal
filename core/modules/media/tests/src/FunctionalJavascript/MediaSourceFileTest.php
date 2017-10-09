<?php

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\media\Entity\Media;

/**
 * Tests the file media source.
 *
 * @group media
 */
class MediaSourceFileTest extends MediaSourceTestBase {

  /**
   * Tests the file media source.
   */
  public function testMediaFileSource() {
    $media_type_id = 'test_media_file_type';
    $source_field_id = 'field_media_file';

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->doTestCreateMediaType($media_type_id, 'file');

    // Hide the name field widget to test default name generation.
    $this->hideMediaTypeFieldWidget('name', $media_type_id);

    $test_filename = $this->randomMachineName() . '.txt';
    $test_filepath = 'public://' . $test_filename;
    file_put_contents($test_filepath, $this->randomMachineName());

    // Create a media item.
    $this->drupalGet("media/add/{$media_type_id}");
    $page->attachFileToField("files[{$source_field_id}_0]", \Drupal::service('file_system')->realpath($test_filepath));
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->pressButton('Save');

    $assert_session->addressEquals('media/1');

    // Make sure the thumbnail is displayed.
    $assert_session->elementAttributeContains('css', '.image-style-thumbnail', 'src', 'generic.png');

    // Make sure checkbox changes the visibility of log message field.
    $this->drupalGet("media/1/edit");
    $page->uncheckField('revision');
    $assert_session->elementAttributeContains('css', '.field--name-revision-log-message', 'style', 'display: none');
    $page->checkField('revision');
    $assert_session->elementAttributeNotContains('css', '.field--name-revision-log-message', 'style', 'display');

    // Load the media and check if the label was properly populated.
    $media = Media::load(1);
    $this->assertEquals($test_filename, $media->getName());

    // Test the MIME type icon.
    $icon_base = \Drupal::config('media.settings')->get('icon_base_uri');
    file_unmanaged_copy($icon_base . '/generic.png', $icon_base . '/text--plain.png');
    $this->drupalGet("media/add/{$media_type_id}");
    $page->attachFileToField("files[{$source_field_id}_0]", \Drupal::service('file_system')->realpath($test_filepath));
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->pressButton('Save');
    $assert_session->elementAttributeContains('css', '.image-style-thumbnail', 'src', 'text--plain.png');
  }

}
