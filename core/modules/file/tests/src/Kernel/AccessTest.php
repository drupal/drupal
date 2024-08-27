<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel;

use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests for the File access control.
 *
 * @group file
 */
class AccessTest extends KernelTestBase {

  use UserCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('file', ['file_usage']);
  }

  /**
   * Tests 'update' and 'delete' access to file entities.
   */
  public function testFileAccess(): void {
    // Create a user so the tested users do not have the magic ID of user 1.
    $this->createUser();

    $user_any = $this->createUser([
      'delete any file',
    ]);
    $this->assertGreaterThan(1, (int) $user_any->id());

    $user_own = $this->createUser([
      'delete own files',
    ]);

    $test_files = $this->getTestFiles('text');
    $file1 = File::create((array) $test_files[0]);
    $file1->set('uid', $user_any->id());
    $file1->save();
    $file2 = File::create((array) $test_files[1]);
    $file2->set('uid', $user_own->id());
    $file2->save();

    // User with "* any file" permissions should delete all files and update
    // their own.
    $this->assertTrue($file1->access('delete', $user_any));
    $this->assertTrue($file1->access('update', $user_any));
    $this->assertTrue($file2->access('delete', $user_any));
    $this->assertFalse($file2->access('update', $user_any));

    // User with "* own files" permissions should access only own files.
    $this->assertFalse($file1->access('delete', $user_own));
    $this->assertFalse($file1->access('update', $user_own));
    $this->assertTrue($file2->access('delete', $user_own));
    $this->assertTrue($file2->access('update', $user_own));

    // Ensure cacheability metadata is correct.
    /** @var \Drupal\Core\Access\AccessResult $access */
    $access = $file2->access('delete', $user_any, TRUE);
    $this->assertSame(['user.permissions'], $access->getCacheContexts());
    $this->assertSame([], $access->getCacheTags());
    /** @var \Drupal\Core\Access\AccessResult $access */
    $access = $file2->access('delete', $user_own, TRUE);
    $this->assertSame(['user.permissions', 'user'], $access->getCacheContexts());
    $this->assertSame(['file:2'], $access->getCacheTags());
    /** @var \Drupal\Core\Access\AccessResult $access */
    $access = $file2->access('update', $user_any, TRUE);
    $this->assertSame([], $access->getCacheContexts());
    $this->assertSame([], $access->getCacheTags());
    /** @var \Drupal\Core\Access\AccessResult $access */
    $access = $file2->access('update', $user_own, TRUE);
    $this->assertSame([], $access->getCacheContexts());
    $this->assertSame([], $access->getCacheTags());

    // User without permissions should not be able to delete files even if they
    // are the owner.
    $user_none = $this->createUser();
    $file3 = File::create([
      'uid' => $user_none->id(),
      'filename' => 'druplicon.txt',
      'filemime' => 'text/plain',
    ]);
    $this->assertFalse($file3->access('delete', $user_none));
    $this->assertTrue($file3->access('update', $user_none));

    // Create a file with no user entity.
    $file4 = File::create([
      'filename' => 'druplicon.txt',
      'filemime' => 'text/plain',
    ]);
    $this->assertFalse($file4->access('delete', $user_own));
    $this->assertFalse($file4->access('update', $user_own));
    $this->assertTrue($file4->access('delete', $user_any));
    $this->assertFalse($file4->access('update', $user_any));
  }

  /**
   * Tests file entity field access.
   *
   * @see \Drupal\file\FileAccessControlHandler::checkFieldAccess()
   */
  public function testCheckFieldAccess(): void {
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
   * Tests create access is always denied even for user 1.
   *
   * @see \Drupal\file\FileAccessControlHandler::checkCreateAccess()
   */
  public function testCreateAccess(): void {
    $user1 = $this->createUser([
      'delete own files',
    ]);

    $this->assertSame('1', $user1->id());

    $file = File::create([
      'uid' => $user1->id(),
      'filename' => 'druplicon.txt',
      'filemime' => 'text/plain',
    ]);
    $this->assertFalse($file->access('create'));

    \Drupal::currentUser()->setAccount($user1);
    $this->assertFalse($file->access('create'));
  }

  /**
   * Tests cacheability metadata.
   */
  public function testFileCacheability(): void {
    $file = File::create([
      'filename' => 'green-scarf',
      'uri' => 'private://green-scarf',
      'filemime' => 'text/plain',
    ]);
    $file->setPermanent();
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
