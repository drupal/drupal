<?php

/**
 * @file
 * Contains \Drupal\file\Tests\FileListingTest.
 */

namespace Drupal\file\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests file listing page functionality.
 */
class FileListingTest extends FileFieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views', 'file', 'image');

  public static function getInfo() {
    return array(
      'name' => 'File listing',
      'description' => 'Tests file listing page functionality.',
      'group' => 'File',
    );
  }

  function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('access files overview', 'bypass node access'));
    $this->base_user = $this->drupalCreateUser();
    $this->createFileField('file', 'article', array(), array('file_extensions' => 'txt png'));
  }

  /**
   * Calculates total count of usages for a file.
   *
   * @param $usage array
   *   Array of file usage information as returned from file_usage subsystem.
   * @return int
   *   Total usage count.
   */
  protected function sumUsages($usage) {
    $count = 0;
    foreach ($usage as $module) {
      foreach ($module as $entity_type) {
        foreach ($entity_type as $entity) {
          $count += $entity;
        }
      }
    }

    return $count;
  }

  /**
   * Tests file overview with different user permissions.
   */
  function testFileListingPages() {
    // Users without sufficent permissions should not see file listing.
    $this->drupalLogin($this->base_user);
    $this->drupalGet('admin/content/files');
    $this->assertResponse(403);

    // Login with user with right permissions and test listing.
    $this->drupalLogin($this->admin_user);

    for ($i = 0; $i < 5; $i++) {
      $nodes[] = $this->drupalCreateNode(array('type' => 'article'));
    }

    foreach ($nodes as &$node) {
      $this->drupalGet('node/' . $node->nid . '/edit');
      $file = $this->getTestFile('image');

      $edit = array(
        'files[file_' . Language::LANGCODE_NOT_SPECIFIED . '_' . 0 . ']' => drupal_realpath($file->getFileUri()),
      );
      $this->drupalPost(NULL, $edit, t('Save'));
      $node = entity_load('node', $node->nid)->getNGEntity();
    }

    $this->drupalGet('admin/content/files');
    $this->assertResponse(200);

    foreach ($nodes as $node) {
      $file = entity_load('file', $node->file->target_id);
      $this->assertText($file->getFilename());
      $this->assertLinkByHref(file_create_url($file->getFileUri()));
      $this->assertLinkByHref('admin/content/files/usage/' . $file->id());
    }
    $this->assertFalse(preg_match('/views-field-status priority-low\">\s*' . t('Temporary') . '/', $this->drupalGetContent()), 'All files are stored as permanent.');

    // Use one file two times and check usage information.
    $orphaned_file = $nodes[1]->file->target_id;
    $used_file = $nodes[0]->file->target_id;
    $nodes[1]->file->target_id = $used_file;
    $nodes[1]->save();

    $this->drupalGet('admin/content/files');
    $file = entity_load('file', $orphaned_file);
    $usage = $this->sumUsages(file_usage()->listUsage($file));
    $this->assertRaw('admin/content/files/usage/' . $file->id() . '">' . $usage);

    $file = entity_load('file', $used_file);
    $usage = $this->sumUsages(file_usage()->listUsage($file));
    $this->assertRaw('admin/content/files/usage/' . $file->id() . '">' . $usage);

    $result = $this->xpath("//td[contains(@class, 'views-field-status') and contains(text(), :value)]", array(':value' => t('Temporary')));
    $this->assertEqual(1, count($result), 'Unused file marked as temporary.');
  }
}
