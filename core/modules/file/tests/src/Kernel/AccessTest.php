<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests for the File access control.
 *
 * @group file
 */
class AccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file', 'system', 'user'];

  /**
   * An authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user1;

  /**
   * An authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user2;

  /**
   * The file object used in the test.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('system', 'sequences');
  }

  /**
   * Tests access to delete and update file permissions.
   */
  public function testFileAccess() {
    $user1 = $this->createUser([
      'delete any files',
      'edit any files',
    ]);

    $user2 = $this->createUser([
      'delete own files',
      'edit own files',
    ]);

    $file1 = File::create([
      'uid' => $user1->id(),
      'filename' => 'druplicon.txt',
      'filemime' => 'text/plain',
    ]);

    $file2 = File::create([
      'uid' => $user2->id(),
      'filename' => 'druplicon.txt',
      'filemime' => 'text/plain',
    ]);

    // Anonymous users can create a file by default.
    $this->assertFalse($file1->access('create'));

    // Authenticated users can create a file by default.
    $this->assertFalse($file1->access('create', $user1));

    // User with "* any files" permissions should access all files.
    $this->assertTrue($file1->access('delete', $user1));
    $this->assertTrue($file1->access('update', $user1));
    $this->assertTrue($file2->access('delete', $user1));
    $this->assertTrue($file2->access('update', $user1));

    // User with "* own files" permissions should access only own files.
    $this->assertFalse($file1->access('delete', $user2));
    $this->assertFalse($file1->access('update', $user2));
    $this->assertTrue($file2->access('delete', $user2));
    $this->assertTrue($file2->access('update', $user2));

    // User without permissions should not be able to delete/edit files even if
    // the user is an owner of the file.
    $user3 = $this->createUser();

    $file3 = File::create([
      'uid' => $user3->id(),
      'filename' => 'druplicon.txt',
      'filemime' => 'text/plain',
    ]);

    $this->assertFalse($file3->access('delete', $user3));
    $this->assertFalse($file3->access('update', $user3));
  }

  /**
   * Tests file entity field access.
   *
   * @see \Drupal\file\FileAccessControlHandler::checkFieldAccess()
   */
  public function testCheckFieldAccess() {
    $this->setUpCurrentUser();
    /** @var \Drupal\file\FileInterface $file */
    $file = File::create([
      'uri' => 'public://test.png',
    ]);
    // While creating a file entity access will be allowed for create-only
    // fields.
    $this->assertTrue($file->get('uri')->access('edit'));
    $this->assertTrue($file->get('filemime')->access('edit'));
    $this->assertTrue($file->get('filesize')->access('edit'));
    // Access to the status field is denied whilst creating a file entity.
    $this->assertFalse($file->get('status')->access('edit'));
    $file->save();
    // After saving the entity is no longer new and, therefore, access to
    // create-only fields and the status field will be denied.
    $this->assertFalse($file->get('uri')->access('edit'));
    $this->assertFalse($file->get('filemime')->access('edit'));
    $this->assertFalse($file->get('filesize')->access('edit'));
    $this->assertFalse($file->get('status')->access('edit'));
  }

  /**
   * Tests cacheability metadata.
   */
  public function testFileCacheability() {
    $file = File::create([
      'filename' => 'green-scarf',
      'uri' => 'private://green-scarf',
      'filemime' => 'text/plain',
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $file->save();
    \Drupal::service('session')->set('anonymous_allowed_file_ids', [$file->id() => $file->id()]);

    $account = User::getAnonymousUser();
    $file->setOwnerId($account->id())->save();
    $this->assertSame(['session', 'user'], $file->access('view', $account, TRUE)->getCacheContexts());
    $this->assertSame(['session', 'user'], $file->access('download', $account, TRUE)->getCacheContexts());

    $account = $this->createUser();
    $file->setOwnerId($account->id())->save();
    $this->assertSame(['user'], $file->access('view', $account, TRUE)->getCacheContexts());
    $this->assertSame(['user'], $file->access('download', $account, TRUE)->getCacheContexts());
  }

}
