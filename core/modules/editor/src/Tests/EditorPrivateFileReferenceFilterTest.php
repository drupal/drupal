<?php

namespace Drupal\editor\Tests;

use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests Editor module's file reference filter with private files.
 *
 * @group editor
 */
class EditorPrivateFileReferenceFilterTest extends BrowserTestBase {

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
    $author = $this->drupalCreateUser();
    $this->drupalLogin($author);

    // Create a content type with a body field.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create a file in the 'private:// ' stream.
    $filename = 'test.png';
    $src = '/system/files/' . $filename;
    /** @var \Drupal\file\FileInterface $file */
    $file = File::create([
      'uri' => 'private://' . $filename,
    ]);
    $file->setTemporary();
    $file->setOwner($author);
    // Create the file itself.
    file_put_contents($file->getFileUri(), $this->randomString());
    $file->save();

    // The image should be visible for its author.
    $this->drupalGet($src);
    $this->assertSession()->statusCodeEquals(200);
    // The not-yet-permanent image should NOT be visible for anonymous.
    $this->drupalLogout();
    $this->drupalGet($src);
    $this->assertSession()->statusCodeEquals(403);

    // Resave the file to be permanent.
    $file->setPermanent();
    $file->save();

    // Create a node with its body field properly pointing to the just-created
    // file.
    $node = $this->drupalCreateNode([
      'type' => 'page',
      'body' => [
        'value' => '<img alt="alt" data-entity-type="file" data-entity-uuid="' . $file->uuid() . '" src="' . $src . '" />',
        'format' => 'private_images',
      ],
      'uid' => $author->id(),
    ]);

    // Do the actual test. The image should be visible for anonymous users,
    // because they can view the referencing entity.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($src);
    $this->assertSession()->statusCodeEquals(200);

    // Disallow anonymous users to view the entity, which then should also
    // disallow them to view the image.
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->revokePermission('access content')
      ->save();
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($src);
    $this->assertSession()->statusCodeEquals(403);
  }

}
