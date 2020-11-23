<?php

namespace Drupal\Tests\file\Functional;

use Drupal\file\Entity\File;

/**
 * Ensure that files added to nodes appear correctly in RSS feeds.
 *
 * @group file
 */
class FileFieldRSSContentTest extends FileFieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests RSS enclosure formatter display for RSS feeds.
   */
  public function testFileFieldRSSContent() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $field_name = strtolower($this->randomMachineName());
    $type_name = 'article';

    $this->createFileField($field_name, 'node', $type_name);

    // RSS display must be added manually.
    $this->drupalGet("admin/structure/types/manage/$type_name/display");
    $edit = [
      "display_modes_custom[rss]" => '1',
    ];
    $this->submitForm($edit, 'Save');

    // Change the format to 'RSS enclosure'.
    $this->drupalGet("admin/structure/types/manage/$type_name/display/rss");
    $edit = [
      "fields[$field_name][type]" => 'file_rss_enclosure',
      "fields[$field_name][region]" => 'content',
    ];
    $this->submitForm($edit, 'Save');

    // Create a new node with a file field set. Promote to frontpage
    // needs to be set so this node will appear in the RSS feed.
    $node = $this->drupalCreateNode(['type' => $type_name, 'promote' => 1]);
    $test_file = $this->getTestFile('text');

    // Create a new node with the uploaded file.
    $nid = $this->uploadNodeFile($test_file, $field_name, $node->id());

    // Get the uploaded file from the node.
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);

    // Check that the RSS enclosure appears in the RSS feed.
    $this->drupalGet('rss.xml');
    $selector = sprintf(
      '//enclosure[@url="%s" and @length="%s" and @type="%s"]',
      file_create_url($node_file->getFileUri()),
      $node_file->getSize(),
      $node_file->getMimeType()
    );
    $this->assertNotEmpty($this->getSession()->getDriver()->find($selector), 'File field RSS enclosure is displayed when viewing the RSS feed.');
  }

}
