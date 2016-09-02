<?php

namespace Drupal\editor\Tests;

use Drupal\file\Entity\File;
use Drupal\simpletest\WebTestBase;

/**
 * Tests Editor module's file reference filter with private files.
 *
 * @group editor
 */
class EditorPrivateFileReferenceFilterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    // Needed for the config: this is the only module in core that utilizes the
    // functionality in editor.module to be tested, and depends on that.
    'ckeditor',
    // Depends on filter.module (indirectly).
    'node',
    // Pulls in the config we're using during testing which create a text format
    // - with the filter_html_image_secure filter DISABLED,
    // - with the editor set to CKEditor,
    // - with drupalimage.image_upload.scheme set to 'private',
    // - with drupalimage.image_upload.directory set to ''.
    'editor_private_test',
  ];

  /**
   * Tests the editor file reference filter with private files.
   */
  function testEditorPrivateFileReferenceFilter() {
    // Create a content type with a body field.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create a file in the 'private:// ' stream.
    $filename = 'test.png';
    $src = '/system/files/' . $filename;

    $file = File::create([
      'uri' => 'private://' . $filename,
      'status' => FILE_STATUS_PERMANENT,
    ]);
    // Create the file itself.
    file_put_contents($file->getFileUri(), $this->randomString());
    $file->save();

    // Create a node with its body field properly pointing to the just-created
    // file.
    $node = $this->drupalCreateNode([
      'type' => 'page',
      'body' => [
        'value' => '<img alt="alt" data-entity-type="file" data-entity-uuid="' . $file->uuid() . '" src="' . $src . '" />',
        'format' => 'private_images',
      ],
    ]);
    $this->drupalGet('/node/' . $node->id());

    // Do the actual test. The image should be visible for anonymous.
    $this->drupalGet($src);
    $this->assertResponse(200, 'Image is downloadable as anonymous.');
  }

}
