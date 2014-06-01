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
   * The file storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * The temporary_maximum_age setting of files.
   *
   * @var int
   */
  protected $maxAge;

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

    $this->maxAge = $this->container->get('config.factory')->get('system.file')->get('temporary_maximum_age');
    $this->fileStorage = $this->container->get('entity.manager')->getStorage('file');
    // Create an entry for the user with the date change.
    $file = $this->fileStorage->create(array('uid' => 2, 'uri' => $this->createUri(), 'status' => 2));
    $file->save();
  }

  /**
   * Tests finding stale files.
   */
  function testRetrieveTemporaryFiles() {
    $this->assertEqual($this->fileStorage->retrieveTemporaryFiles(), [], 'No file is to be deleted.');

    // Create an entry for the user with an indication of the old date of the
    // change. As the changed field always saves the request time, we do have
    // update it with a direct db query.
    $file = $this->fileStorage->create(array('uid' => 2, 'uri' => $this->createUri(), 'status' => 2));
    $file->save();
    db_update('file_managed')
      ->fields(array('changed' => REQUEST_TIME - ($this->maxAge * 2)))
      ->condition('fid', $file->id())
      ->execute();

    $this->assertEqual($this->fileStorage->retrieveTemporaryFiles(), [$file->id()], 'One file is to be deleted.');
  }

}
