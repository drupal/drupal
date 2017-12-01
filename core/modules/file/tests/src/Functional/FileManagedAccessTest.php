<?php

namespace Drupal\Tests\file\Functional;

use Drupal\file\Entity\File;
use Drupal\user\Entity\Role;

/**
 * Tests access to managed files.
 *
 * @group file
 */
class FileManagedAccessTest extends FileManagedTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Give anonymous users permission to access content, so they can view and
    // download public files.
    $anonymous_role = Role::load(Role::ANONYMOUS_ID);
    $anonymous_role->grantPermission('access content');
    $anonymous_role->save();
  }

  /**
   * Tests if public file is always accessible.
   */
  public function testFileAccess() {
    // Create a new file entity.
    $file = File::create([
      'uid' => 1,
      'filename' => 'drupal.txt',
      'uri' => 'public://drupal.txt',
      'filemime' => 'text/plain',
      'status' => FILE_STATUS_PERMANENT,
    ]);
    file_put_contents($file->getFileUri(), 'hello world');

    // Save it, inserting a new record.
    $file->save();

    // Create authenticated user to check file access.
    $account = $this->createUser(['access site reports', 'access content']);

    $this->assertTrue($file->access('view', $account), 'Public file is viewable to authenticated user');
    $this->assertTrue($file->access('download', $account), 'Public file is downloadable to authenticated user');

    // Create anonymous user to check file access.
    $account = $this->createUser()->getAnonymousUser();

    $this->assertTrue($file->access('view', $account), 'Public file is viewable to anonymous user');
    $this->assertTrue($file->access('download', $account), 'Public file is downloadable to anonymous user');

    // Create a new file entity.
    $file = File::create([
      'uid' => 1,
      'filename' => 'drupal.txt',
      'uri' => 'private://drupal.txt',
      'filemime' => 'text/plain',
      'status' => FILE_STATUS_PERMANENT,
    ]);
    file_put_contents($file->getFileUri(), 'hello world');

    // Save it, inserting a new record.
    $file->save();

    // Create authenticated user to check file access.
    $account = $this->createUser(['access site reports', 'access content']);

    $this->assertFalse($file->access('view', $account), 'Private file is not viewable to authenticated user');
    $this->assertFalse($file->access('download', $account), 'Private file is not downloadable to authenticated user');

    // Create anonymous user to check file access.
    $account = $this->createUser()->getAnonymousUser();

    $this->assertFalse($file->access('view', $account), 'Private file is not viewable to anonymous user');
    $this->assertFalse($file->access('download', $account), 'Private file is not downloadable to anonymous user');
  }

}
