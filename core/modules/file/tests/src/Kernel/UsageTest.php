<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\Core\Database\Database;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests file usage functions.
 *
 * @group file
 */
class UsageTest extends FileManagedUnitTestBase {

  /**
   * Tests \Drupal\file\FileUsage\DatabaseFileUsageBackend::listUsage().
   */
  public function testGetUsage() {
    $file = $this->createFile();
    $connection = Database::getConnection();
    $connection->insert('file_usage')
      ->fields([
        'fid' => $file->id(),
        'module' => 'testing',
        'type' => 'foo',
        'id' => 1,
        'count' => 1,
      ])
      ->execute();
    $connection->insert('file_usage')
      ->fields([
        'fid' => $file->id(),
        'module' => 'testing',
        'type' => 'bar',
        'id' => 2,
        'count' => 2,
      ])
      ->execute();

    $usage = $this->container->get('file.usage')->listUsage($file);

    $this->assertCount(2, $usage['testing'], 'Returned the correct number of items.');
    $this->assertTrue(isset($usage['testing']['foo'][1]), 'Returned the correct id.');
    $this->assertTrue(isset($usage['testing']['bar'][2]), 'Returned the correct id.');
    $this->assertEquals(1, $usage['testing']['foo'][1], 'Returned the correct count.');
    $this->assertEquals(2, $usage['testing']['bar'][2], 'Returned the correct count.');
  }

  /**
   * Tests \Drupal\file\FileUsage\DatabaseFileUsageBackend::add().
   */
  public function testAddUsage() {
    $file = $this->createFile();
    $file_usage = $this->container->get('file.usage');
    $file_usage->add($file, 'testing', 'foo', 1);
    // Add the file twice to ensure that the count is incremented rather than
    // creating additional records.
    $file_usage->add($file, 'testing', 'bar', 2);
    $file_usage->add($file, 'testing', 'bar', 2);

    $usage = Database::getConnection()->select('file_usage', 'f')
      ->fields('f')
      ->condition('f.fid', $file->id())
      ->execute()
      ->fetchAllAssoc('id');
    $this->assertCount(2, $usage, 'Created two records');
    $this->assertEquals('testing', $usage[1]->module, 'Correct module');
    $this->assertEquals('testing', $usage[2]->module, 'Correct module');
    $this->assertEquals('foo', $usage[1]->type, 'Correct type');
    $this->assertEquals('bar', $usage[2]->type, 'Correct type');
    $this->assertEquals(1, $usage[1]->count, 'Correct count');
    $this->assertEquals(2, $usage[2]->count, 'Correct count');
  }

  /**
   * Tests file usage deletion when files are made temporary.
   */
  public function testRemoveUsageTemporary() {
    $this->config('file.settings')
      ->set('make_unused_managed_files_temporary', TRUE)
      ->save();
    $file = $this->doTestRemoveUsage();
    $this->assertTrue($file->isTemporary());
  }

  /**
   * Tests file usage deletion when files are made temporary.
   */
  public function testRemoveUsageNonTemporary() {
    $this->config('file.settings')
      ->set('make_unused_managed_files_temporary', FALSE)
      ->save();
    $file = $this->doTestRemoveUsage();
    $this->assertFalse($file->isTemporary());
  }

  /**
   * Tests \Drupal\file\FileUsage\DatabaseFileUsageBackend::delete().
   */
  public function doTestRemoveUsage() {
    $file = $this->createFile();
    $file->setPermanent();
    $file_usage = $this->container->get('file.usage');
    $connection = Database::getConnection();
    $connection->insert('file_usage')
      ->fields([
        'fid' => $file->id(),
        'module' => 'testing',
        'type' => 'bar',
        'id' => 2,
        'count' => 3,
      ])
      ->execute();

    // Normal decrement.
    $file_usage->delete($file, 'testing', 'bar', 2);
    $count = $connection->select('file_usage', 'f')
      ->fields('f', ['count'])
      ->condition('f.fid', $file->id())
      ->execute()
      ->fetchField();
    $this->assertEquals(2, $count, 'The count was decremented correctly.');

    // Multiple decrement and removal.
    $file_usage->delete($file, 'testing', 'bar', 2, 2);
    $count = $connection->select('file_usage', 'f')
      ->fields('f', ['count'])
      ->condition('f.fid', $file->id())
      ->execute()
      ->fetchField();
    $this->assertFalse($count, 'The count was removed entirely when empty.');

    // Non-existent decrement.
    $file_usage->delete($file, 'testing', 'bar', 2);
    $count = $connection->select('file_usage', 'f')
      ->fields('f', ['count'])
      ->condition('f.fid', $file->id())
      ->execute()
      ->fetchField();
    $this->assertFalse($count, 'Decrementing non-exist record complete.');
    return $file;
  }

  /**
   * Create files for all the possible combinations of age and status.
   *
   * We are using UPDATE statements because using the API would set the
   * timestamp.
   */
  public function createTempFiles() {
    /** @var \Drupal\file\FileRepositoryInterface $fileRepository */
    $fileRepository = \Drupal::service('file.repository');

    // Temporary file that is old.
    $destination = "public://";
    $temp_old = $fileRepository->writeData('', $destination);
    $connection = Database::getConnection();
    $connection->update('file_managed')
      ->fields([
        'status' => 0,
        'changed' => REQUEST_TIME - $this->config('system.file')->get('temporary_maximum_age') - 1,
      ])
      ->condition('fid', $temp_old->id())
      ->execute();
    $this->assertFileExists($temp_old->getFileUri());

    // Temporary file that is new.
    $temp_new = $fileRepository->writeData('', $destination);
    $connection->update('file_managed')
      ->fields(['status' => 0])
      ->condition('fid', $temp_new->id())
      ->execute();
    $this->assertFileExists($temp_new->getFileUri());

    // Permanent file that is old.
    $perm_old = $fileRepository->writeData('', $destination);
    $connection->update('file_managed')
      ->fields(['changed' => REQUEST_TIME - $this->config('system.file')->get('temporary_maximum_age') - 1])
      ->condition('fid', $temp_old->id())
      ->execute();
    $this->assertFileExists($perm_old->getFileUri());

    // Permanent file that is new.
    $perm_new = $fileRepository->writeData('', $destination);
    $this->assertFileExists($perm_new->getFileUri());
    return [$temp_old, $temp_new, $perm_old, $perm_new];
  }

  /**
   * Ensure that temporary files are removed by default.
   */
  public function testTempFileCleanupDefault() {
    [$temp_old, $temp_new, $perm_old, $perm_new] = $this->createTempFiles();

    // Run cron and then ensure that only the old, temp file was deleted.
    $this->container->get('cron')->run();
    $this->assertFileDoesNotExist($temp_old->getFileUri());
    $this->assertFileExists($temp_new->getFileUri());
    $this->assertFileExists($perm_old->getFileUri());
    $this->assertFileExists($perm_new->getFileUri());
  }

  /**
   * Ensure that temporary files are kept as configured.
   */
  public function testTempFileNoCleanup() {
    [$temp_old, $temp_new, $perm_old, $perm_new] = $this->createTempFiles();

    // Set the max age to 0, meaning no temporary files will be deleted.
    $this->config('system.file')
      ->set('temporary_maximum_age', 0)
      ->save();

    // Run cron and then ensure that no file was deleted.
    $this->container->get('cron')->run();
    $this->assertFileExists($temp_old->getFileUri());
    $this->assertFileExists($temp_new->getFileUri());
    $this->assertFileExists($perm_old->getFileUri());
    $this->assertFileExists($perm_new->getFileUri());
  }

  /**
   * Ensure that temporary files are kept as configured.
   */
  public function testTempFileCustomCleanup() {
    [$temp_old, $temp_new, $perm_old, $perm_new] = $this->createTempFiles();

    // Set the max age to older than default.
    $this->config('system.file')
      ->set('temporary_maximum_age', 21600 + 2)
      ->save();

    // Run cron and then ensure that more files were deleted.
    $this->container->get('cron')->run();
    $this->assertFileExists($temp_old->getFileUri());
    $this->assertFileExists($temp_new->getFileUri());
    $this->assertFileExists($perm_old->getFileUri());
    $this->assertFileExists($perm_new->getFileUri());
  }

  /**
   * Tests file usage with translated entities.
   */
  public function testFileUsageWithEntityTranslation() {
    /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
    $file_usage = $this->container->get('file.usage');

    $this->enableModules(['node', 'language']);
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);

    ConfigurableLanguage::create([
      'id' => 'en',
      'label' => 'English',
    ])->save();
    ConfigurableLanguage::create([
      'id' => 'ro',
      'label' => 'Romanian',
    ])->save();

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();
    ContentLanguageSettings::loadByEntityTypeBundle('node', 'page')
      ->setLanguageAlterable(FALSE)
      ->setDefaultLangcode('en')
      ->save();
    // Create a file field attached to 'page' node-type.
    FieldStorageConfig::create([
      'type' => 'file',
      'entity_type' => 'node',
      'field_name' => 'file',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => 'file',
      'label' => 'File',
    ])->save();

    // Create a node, attach a file and add a Romanian translation.
    $node = Node::create(['type' => 'page', 'title' => 'Page']);
    $node
      ->set('file', $file = $this->createFile())
      ->addTranslation('ro', $node->getTranslation('en')->toArray())
      ->save();

    // Check that the file is used twice.
    $usage = $file_usage->listUsage($file);
    $this->assertEquals(2, $usage['file']['node'][$node->id()]);

    // Remove the Romanian translation.
    $node->removeTranslation('ro');
    $node->save();

    // Check that one usage has been removed and is used only once now.
    $usage = $file_usage->listUsage($file);
    $this->assertEquals(1, $usage['file']['node'][$node->id()]);
  }

}
