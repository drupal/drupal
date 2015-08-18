<?php

/**
 * @file
 * Contains \Drupal\file\Tests\FileManagedAccessTest.
 */

namespace Drupal\file\Tests;

use Drupal\file\Entity\File;

/**
 * Tests access to managed files.
 *
 * @group file
 */
class FileManagedAccessTest extends FileManagedTestBase {

  /**
   * Tests if public file is always accessible.
   */
  function testFileAccess() {
    // Create a new file entity.
    $file = File::create(array(
      'uid' => 1,
      'filename' => 'drupal.txt',
      'uri' => 'public://drupal.txt',
      'filemime' => 'text/plain',
      'status' => FILE_STATUS_PERMANENT,
    ));
    file_put_contents($file->getFileUri(), 'hello world');

    // Save it, inserting a new record.
    $file->save();

    // Create authenticated user to check file access.
    $account = $this->createUser(array('access site reports'));

    $this->assertTrue($file->access('view', $account), 'Public file is viewable to authenticated user');
    $this->assertTrue($file->access('download', $account), 'Public file is downloadable to authenticated user');

    // Create anonymous user to check file access.
    $account = $this->createUser()->getAnonymousUser();

    $this->assertTrue($file->access('view', $account), 'Public file is viewable to anonymous user');
    $this->assertTrue($file->access('download', $account), 'Public file is downloadable to anonymous user');

    // Create a new file entity.
    $file = File::create(array(
      'uid' => 1,
      'filename' => 'drupal.txt',
      'uri' => 'private://drupal.txt',
      'filemime' => 'text/plain',
      'status' => FILE_STATUS_PERMANENT,
    ));
    file_put_contents($file->getFileUri(), 'hello world');

    // Save it, inserting a new record.
    $file->save();

    // Create authenticated user to check file access.
    $account = $this->createUser(array('access site reports'));

    $this->assertFalse($file->access('view', $account), 'Private file is not viewable to authenticated user');
    $this->assertFalse($file->access('download', $account), 'Private file is not downloadable to authenticated user');

    // Create anonymous user to check file access.
    $account = $this->createUser()->getAnonymousUser();

    $this->assertFalse($file->access('view', $account), 'Private file is not viewable to anonymous user');
    $this->assertFalse($file->access('download', $account), 'Private file is not downloadable to anonymous user');
  }
}
