<?php

/**
 * @file
 * Contains \Drupal\file\Tests\FileListingTest.
 */

namespace Drupal\file\Tests;

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;

/**
 * Tests file listing page functionality.
 *
 * @group file
 */
class FileListingTest extends FileFieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views', 'file', 'image');

  /**
   * An authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser;

  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(array('access files overview', 'bypass node access'));
    $this->baseUser = $this->drupalCreateUser();
    $this->createFileField('file', 'node', 'article', array(), array('file_extensions' => 'txt png'));
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
    $file_usage = $this->container->get('file.usage');
    // Users without sufficient permissions should not see file listing.
    $this->drupalLogin($this->baseUser);
    $this->drupalGet('admin/content/files');
    $this->assertResponse(403);

    // Login with user with right permissions and test listing.
    $this->drupalLogin($this->adminUser);

    for ($i = 0; $i < 5; $i++) {
      $nodes[] = $this->drupalCreateNode(array('type' => 'article'));
    }

    $this->drupalGet('admin/content/files');
    $this->assertResponse(200);
    $this->assertText('No files available.');
    $this->drupalGet('admin/content/files');
    $this->assertResponse(200);

    // Create a file with no usage.
    $file = $this->createFile();

    $this->drupalGet('admin/content/files/usage/' . $file->id());
    $this->assertResponse(200);
    $this->assertTitle(t('File usage information for @file | Drupal', array('@file' => $file->getFilename())));

    foreach ($nodes as &$node) {
      $this->drupalGet('node/' . $node->id() . '/edit');
      $file = $this->getTestFile('image');

      $edit = array(
        'files[file_0]' => drupal_realpath($file->getFileUri()),
      );
      $this->drupalPostForm(NULL, $edit, t('Save'));
      $node = Node::load($node->id());
    }

    $this->drupalGet('admin/content/files');

    foreach ($nodes as $node) {
      $file = File::load($node->file->target_id);
      $this->assertText($file->getFilename());
      $this->assertLinkByHref(file_create_url($file->getFileUri()));
      $this->assertLinkByHref('admin/content/files/usage/' . $file->id());
    }
    $this->assertFalse(preg_match('/views-field-status priority-low\">\s*' . t('Temporary') . '/', $this->getRawContent()), 'All files are stored as permanent.');

    // Use one file two times and check usage information.
    $orphaned_file = $nodes[1]->file->target_id;
    $used_file = $nodes[0]->file->target_id;
    $nodes[1]->file->target_id = $used_file;
    $nodes[1]->save();

    $this->drupalGet('admin/content/files');
    $file = File::load($orphaned_file);
    $usage = $this->sumUsages($file_usage->listUsage($file));
    $this->assertRaw('admin/content/files/usage/' . $file->id() . '">' . $usage);

    $file = File::load($used_file);
    $usage = $this->sumUsages($file_usage->listUsage($file));
    $this->assertRaw('admin/content/files/usage/' . $file->id() . '">' . $usage);

    $result = $this->xpath("//td[contains(@class, 'views-field-status') and contains(text(), :value)]", array(':value' => t('Temporary')));
    $this->assertEqual(1, count($result), 'Unused file marked as temporary.');

    // Test file usage page.
    foreach ($nodes as $node) {
      $file = File::load($node->file->target_id);
      $usage = $file_usage->listUsage($file);
      $this->drupalGet('admin/content/files/usage/' . $file->id());
      $this->assertResponse(200);
      $this->assertText($node->getTitle(), 'Node title found on usage page.');
      $this->assertText('node', 'Registering entity type found on usage page.');
      $this->assertText('file', 'Registering module found on usage page.');
      foreach ($usage as $module) {
        foreach ($module as $entity_type) {
          foreach ($entity_type as $entity) {
            $this->assertText($entity, 'Usage count found on usage page.');
          }
        }
      }
      $this->assertLinkByHref('node/' . $node->id(), 0, 'Link to registering entity found on usage page.');
    }
  }

  /**
   * Creates and saves a test file.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *  A file entity.
   */
  protected function createFile() {
    // Create a new file entity.
    $file = entity_create('file', array(
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ));
    file_put_contents($file->getFileUri(), 'hello world');

    // Save it, inserting a new record.
    $file->save();

    return $file;
  }

}
