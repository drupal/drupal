<?php

/**
 * @file
 * Contains \Drupal\file\Tests\RetrieveTemporaryFilesTest.
 */

namespace Drupal\file\Tests;

/**
 * Provides tests for retrieving temporary files.
 *
 * @see \Drupal\Core\Entity\ContentEntityDatabaseStorage::retrieveTemporaryFiles()
 */
class RetrieveTemporaryFilesTest extends FileManagedUnitTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Temporary files tests',
      'description' => 'Tests the retrieveTemporaryFiles() function.',
      'group' => 'File Managed API',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $max_age = $this->container->get('config.factory')->get('system.file')->get('temporary_maximum_age');

    // Create an entry for the user with the date change.
    $file_array = array('uid' => 2, 'uri' => 'public://example1.txt', 'status' => 2, 'changed' => REQUEST_TIME);
    db_insert('file_managed')->fields($file_array)->execute();

    // Create an entry for the user with an indication of the old date of the
    // change.
    $file_array = array('uid' => 2, 'uri' => 'public://example2.txt', 'status' => 2, 'changed' => REQUEST_TIME - ($max_age * 2));
    db_insert('file_managed')->fields($file_array)->execute();
  }

  /**
   * Tests finding stale files.
   */
  function testRetrieveTemporaryFiles() {
    $file_storage = $this->container->get('entity.manager')->getStorage('file');

    $count_files = count($file_storage->retrieveTemporaryFiles()->fetchAssoc());

    $this->assertEqual($count_files, 1);
  }

}
