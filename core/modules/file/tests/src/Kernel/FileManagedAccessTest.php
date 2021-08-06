<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests access to managed files.
 *
 * @group file
 */
class FileManagedAccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'system',
    'user',
  ];

  /**
   * Tests if public file is always accessible.
   */
  public function testFileAccess() {
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig('user');

    $anonymous = User::create(['uid' => 0, 'name' => '']);
    $anonymous->save();
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['access content']);

    // Create an authenticated user to check file access.
    $account = $this->createUser(['access site reports', 'access content'], NULL, FALSE, ['uid' => 2]);

    // Create a new file entity in the public:// stream wrapper.
    $file_public = File::create([
      'uid' => 1,
      'filename' => 'drupal.txt',
      'uri' => 'public://drupal.txt',
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file_public->save();

    $this->assertTrue($file_public->access('view', $account));
    $this->assertTrue($file_public->access('download', $account));

    $this->assertTrue($file_public->access('view', $anonymous));
    $this->assertTrue($file_public->access('download', $anonymous));

    // Create a new file entity in the private:// stream wrapper.
    $file_private = File::create([
      'uid' => 1,
      'filename' => 'drupal.txt',
      'uri' => 'private://drupal.txt',
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file_private->save();

    $this->assertFalse($file_private->access('view', $account));
    $this->assertFalse($file_private->access('download', $account));

    $this->assertFalse($file_private->access('view', $anonymous));
    $this->assertFalse($file_private->access('download', $anonymous));
  }

}
