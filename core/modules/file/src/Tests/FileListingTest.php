<?php

namespace Drupal\file\Tests;

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\entity_test\Entity\EntityTestConstraints;

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
  public static $modules = ['views', 'file', 'image', 'entity_test'];

  /**
   * An authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser;

  protected function setUp() {
    parent::setUp();

    // This test expects unused managed files to be marked as a temporary file.
    $this->config('file.settings')
      ->set('make_unused_managed_files_temporary', TRUE)
      ->save();

    $this->adminUser = $this->drupalCreateUser(['access files overview', 'bypass node access']);
    $this->baseUser = $this->drupalCreateUser();
    $this->createFileField('file', 'node', 'article', [], ['file_extensions' => 'txt png']);
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
  public function testFileListingPages() {
    $file_usage = $this->container->get('file.usage');
    // Users without sufficient permissions should not see file listing.
    $this->drupalLogin($this->baseUser);
    $this->drupalGet('admin/content/files');
    $this->assertResponse(403);

    // Log in with user with right permissions and test listing.
    $this->drupalLogin($this->adminUser);

    for ($i = 0; $i < 5; $i++) {
      $nodes[] = $this->drupalCreateNode(['type' => 'article']);
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
    $this->assertTitle(t('File usage information for @file | Drupal', ['@file' => $file->getFilename()]));

    foreach ($nodes as &$node) {
      $this->drupalGet('node/' . $node->id() . '/edit');
      $file = $this->getTestFile('image');

      $edit = [
        'files[file_0]' => \Drupal::service('file_system')->realpath($file->getFileUri()),
      ];
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

    $result = $this->xpath("//td[contains(@class, 'views-field-status') and contains(text(), :value)]", [':value' => t('Temporary')]);
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
   * Tests file listing usage page for entities with no canonical link template.
   */
  public function testFileListingUsageNoLink() {
    // Login with user with right permissions and test listing.
    $this->drupalLogin($this->adminUser);

    // Create a bundle and attach a File field to the bundle.
    $bundle = $this->randomMachineName();
    entity_test_create_bundle($bundle, NULL, 'entity_test_constraints');
    $this->createFileField('field_test_file', 'entity_test_constraints', $bundle, [], ['file_extensions' => 'txt png']);

    // Create file to attach to entity.
    $file = File::create([
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
    ]);
    $file->setPermanent();
    file_put_contents($file->getFileUri(), 'hello world');
    $file->save();

    // Create entity and attach the created file.
    $entity_name = $this->randomMachineName();
    $entity = EntityTestConstraints::create([
      'uid' => 1,
      'name' => $entity_name,
      'type' => $bundle,
      'field_test_file' => [
        'target_id' => $file->id(),
      ],
    ]);
    $entity->save();

    // Create node entity and attach the created file.
    $node = $this->drupalCreateNode(['type' => 'article', 'file' => $file]);
    $node->save();

    // Load the file usage page for the created and attached file.
    $this->drupalGet('admin/content/files/usage/' . $file->id());

    $this->assertResponse(200);
    // Entity name should be displayed, but not linked if Entity::toUrl
    // throws an exception
    $this->assertText($entity_name, 'Entity name is added to file usage listing.');
    $this->assertNoLink($entity_name, 'Linked entity name not added to file usage listing.');
    $this->assertLink($node->getTitle());
  }

  /**
   * Creates and saves a test file.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A file entity.
   */
  protected function createFile() {
    // Create a new file entity.
    $file = File::create([
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ]);
    file_put_contents($file->getFileUri(), 'hello world');

    // Save it, inserting a new record.
    $file->save();

    return $file;
  }

}
