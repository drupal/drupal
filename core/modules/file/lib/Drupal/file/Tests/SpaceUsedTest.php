<?php

/**
 * @file
 * Definition of Drupal\file\Tests\SpaceUsedTest.
 */

namespace Drupal\file\Tests;

/**
 *  This will run tests against the $file_managed->spaceUsed() function.
 */
class SpaceUsedTest extends FileManagedUnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'File space used tests',
      'description' => 'Tests the spaceUsed() function.',
      'group' => 'File Managed API',
    );
  }

  function setUp() {
    parent::setUp();

    // Create records for a couple of users with different sizes.
    $file = array('uid' => 2, 'uri' => 'public://example1.txt', 'filesize' => 50, 'status' => FILE_STATUS_PERMANENT);
    db_insert('file_managed')->fields($file)->execute();
    $file = array('uid' => 2, 'uri' => 'public://example2.txt', 'filesize' => 20, 'status' => FILE_STATUS_PERMANENT);
    db_insert('file_managed')->fields($file)->execute();
    $file = array('uid' => 3, 'uri' => 'public://example3.txt', 'filesize' => 100, 'status' => FILE_STATUS_PERMANENT);
    db_insert('file_managed')->fields($file)->execute();
    $file = array('uid' => 3, 'uri' => 'public://example4.txt', 'filesize' => 200, 'status' => FILE_STATUS_PERMANENT);
    db_insert('file_managed')->fields($file)->execute();

    // Now create some non-permanent files.
    $file = array('uid' => 2, 'uri' => 'public://example5.txt', 'filesize' => 1, 'status' => 0);
    db_insert('file_managed')->fields($file)->execute();
    $file = array('uid' => 3, 'uri' => 'public://example6.txt', 'filesize' => 3, 'status' => 0);
    db_insert('file_managed')->fields($file)->execute();
  }

  /**
   * Test different users with the default status.
   */
  function testFileSpaceUsed() {
    $file = $this->container->get('entity.manager')->getStorageController('file');
    // Test different users with default status.
    $this->assertEqual($file->spaceUsed(2), 70);
    $this->assertEqual($file->spaceUsed(3), 300);
    $this->assertEqual($file->spaceUsed(), 370);

    // Test the status fields
    $this->assertEqual($file->spaceUsed(NULL, 0), 4);
    $this->assertEqual($file->spaceUsed(NULL, FILE_STATUS_PERMANENT), 370);

    // Test both the user and status.
    $this->assertEqual($file->spaceUsed(1, 0), 0);
    $this->assertEqual($file->spaceUsed(1, FILE_STATUS_PERMANENT), 0);
    $this->assertEqual($file->spaceUsed(2, 0), 1);
    $this->assertEqual($file->spaceUsed(2, FILE_STATUS_PERMANENT), 70);
    $this->assertEqual($file->spaceUsed(3, 0), 3);
    $this->assertEqual($file->spaceUsed(3, FILE_STATUS_PERMANENT), 300);
  }
}
