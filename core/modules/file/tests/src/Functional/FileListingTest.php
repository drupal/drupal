<?php

namespace Drupal\Tests\file\Functional;

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
  protected static $modules = ['views', 'file', 'image', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser;

  protected function setUp(): void {
    parent::setUp();

    // This test expects unused managed files to be marked as a temporary file.
    $this->config('file.settings')
      ->set('make_unused_managed_files_temporary', TRUE)
      ->save();

    $this->adminUser = $this->drupalCreateUser([
      'access files overview',
      'bypass node access',
    ]);
    $this->baseUser = $this->drupalCreateUser();
    $this->createFileField('file', 'node', 'article', [], ['file_extensions' => 'txt png']);
  }

  /**
   * Calculates total count of usages for a file.
   *
   * @param $usage array
   *   Array of file usage information as returned from file_usage subsystem.
   *
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
    $this->assertSession()->statusCodeEquals(403);

    // Log in with user with right permissions and test listing.
    $this->drupalLogin($this->adminUser);

    for ($i = 0; $i < 5; $i++) {
      $nodes[] = $this->drupalCreateNode(['type' => 'article']);
    }

    $this->drupalGet('admin/content/files');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('No files available.');
    $this->drupalGet('admin/content/files');
    $this->assertSession()->statusCodeEquals(200);

    // Create a file with no usage.
    $file = $this->createFile();

    $this->drupalGet('admin/content/files/usage/' . $file->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals('File usage information for ' . $file->getFilename() . ' | Drupal');

    foreach ($nodes as &$node) {
      $this->drupalGet('node/' . $node->id() . '/edit');
      $file = $this->getTestFile('image');

      $edit = [
        'files[file_0]' => \Drupal::service('file_system')->realpath($file->getFileUri()),
      ];
      $this->submitForm($edit, 'Save');
      $node = Node::load($node->id());
    }

    $this->drupalGet('admin/content/files');

    foreach ($nodes as $node) {
      $file = File::load($node->file->target_id);
      $this->assertSession()->pageTextContains($file->getFilename());
      $this->assertSession()->linkByHrefExists($file->createFileUrl());
      $this->assertSession()->linkByHrefExists('admin/content/files/usage/' . $file->id());
    }
    $this->assertSession()->elementTextNotContains('css', '.views-element-container table', 'Temporary');
    $this->assertSession()->elementTextContains('css', '.views-element-container table', 'Permanent');

    // Use one file two times and check usage information.
    $orphaned_file = $nodes[1]->file->target_id;
    $used_file = $nodes[0]->file->target_id;
    $nodes[1]->file->target_id = $used_file;
    $nodes[1]->save();

    $this->drupalGet('admin/content/files');
    $file = File::load($orphaned_file);
    $usage = $this->sumUsages($file_usage->listUsage($file));
    $this->assertSession()->responseContains('admin/content/files/usage/' . $file->id() . '">' . $usage);

    $file = File::load($used_file);
    $usage = $this->sumUsages($file_usage->listUsage($file));
    $this->assertSession()->responseContains('admin/content/files/usage/' . $file->id() . '">' . $usage);

    $result = $this->xpath("//td[contains(@class, 'views-field-status') and contains(text(), :value)]", [':value' => 'Temporary']);
    $this->assertCount(1, $result, 'Unused file marked as temporary.');

    // Test file usage page.
    foreach ($nodes as $node) {
      $file = File::load($node->file->target_id);
      $usage = $file_usage->listUsage($file);
      $this->drupalGet('admin/content/files/usage/' . $file->id());
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->pageTextContains($node->getTitle());
      // Verify that registering entity type is found on usage page.
      $this->assertSession()->pageTextContains('node');
      // Verify that registering module is found on usage page.
      $this->assertSession()->pageTextContains('file');
      foreach ($usage as $module) {
        foreach ($module as $entity_type) {
          foreach ($entity_type as $entity) {
            // Verify that usage count is found on usage page.
            $this->assertSession()->pageTextContains($entity);
          }
        }
      }
      $this->assertSession()->linkByHrefExists('node/' . $node->id(), 0, 'Link to registering entity found on usage page.');
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

    $this->assertSession()->statusCodeEquals(200);
    // Entity name should be displayed, but not linked if Entity::toUrl
    // throws an exception
    $this->assertSession()->pageTextContains($entity_name);
    $this->assertSession()->linkNotExists($entity_name, 'Linked entity name not added to file usage listing.');
    $this->assertSession()->linkExists($node->getTitle());
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
    ]);
    $file->setPermanent();
    file_put_contents($file->getFileUri(), 'hello world');

    // Save it, inserting a new record.
    $file->save();

    return $file;
  }

}
