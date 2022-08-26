<?php

namespace Drupal\Tests\editor\Functional;

use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
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
  protected static $modules = [
    'editor_test',
    // Depends on filter.module (indirectly).
    'node',
    // Pulls in the config we're using during testing which create a text format
    // - with the filter_html_image_secure filter DISABLED,
    // - with the editor set to Unicorn editor,
    // - with drupalimage.image_upload.scheme set to 'private',
    // - with drupalimage.image_upload.directory set to ''.
    'editor_private_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the editor file reference filter with private files.
   */
  public function testEditorPrivateFileReferenceFilter() {
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

    // Create some nodes to ensure file usage count does not match the ID's
    // of the nodes we are going to check.
    for ($i = 0; $i < 5; $i++) {
      $this->drupalCreateNode([
        'type' => 'page',
        'uid' => $author->id(),
      ]);
    }

    // Create a node with its body field properly pointing to the just-created
    // file.
    $published_node = $this->drupalCreateNode([
      'type' => 'page',
      'body' => [
        'value' => '<img alt="alt" data-entity-type="file" data-entity-uuid="' . $file->uuid() . '" src="' . $src . '" />',
        'format' => 'private_images',
      ],
      'uid' => $author->id(),
    ]);

    // Create an unpublished node with its body field properly pointing to the
    // just-created file.
    $unpublished_node = $this->drupalCreateNode([
      'type' => 'page',
      'status' => NodeInterface::NOT_PUBLISHED,
      'body' => [
        'value' => '<img alt="alt" data-entity-type="file" data-entity-uuid="' . $file->uuid() . '" src="' . $src . '" />',
        'format' => 'private_images',
      ],
      'uid' => $author->id(),
    ]);

    // Do the actual test. The image should be visible for anonymous users,
    // because they can view the published node. Even though they can't view
    // the unpublished node.
    $this->drupalGet($published_node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($unpublished_node->toUrl());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($src);
    $this->assertSession()->statusCodeEquals(200);

    // When the published node is also unpublished, the image should also
    // become inaccessible to anonymous users.
    $published_node->setUnpublished()->save();

    $this->drupalGet($published_node->toUrl());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($src);
    $this->assertSession()->statusCodeEquals(403);

    // Disallow anonymous users to view the entity, which then should also
    // disallow them to view the image.
    $published_node->setPublished()->save();
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->revokePermission('access content')
      ->save();
    $this->drupalGet($published_node->toUrl());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($src);
    $this->assertSession()->statusCodeEquals(403);
  }

}
