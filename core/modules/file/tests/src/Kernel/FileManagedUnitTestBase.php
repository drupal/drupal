<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Base class for file unit tests that use the file_test module to test uploads and
 * hooks.
 */
abstract class FileManagedUnitTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file_test', 'file', 'system', 'field', 'user'];

  protected function setUp() {
    parent::setUp();
    // Clear out any hook calls.
    file_test_reset();

    $this->installConfig(['system']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('file', ['file_usage']);

    // Make sure that a user with uid 1 exists, self::createFile() relies on
    // it.
    $user = User::create(['uid' => 1, 'name' => $this->randomMachineName()]);
    $user->enforceIsNew();
    $user->save();
    \Drupal::currentUser()->setAccount($user);
  }

  /**
   * Assert that all of the specified hook_file_* hooks were called once, other
   * values result in failure.
   *
   * @param array $expected
   *   Array with string containing with the hook name, e.g. 'load', 'save',
   *   'insert', etc.
   */
  public function assertFileHooksCalled($expected) {
    \Drupal::state()->resetCache();

    // Determine which hooks were called.
    $actual = array_keys(array_filter(file_test_get_all_calls()));

    // Determine if there were any expected that were not called.
    $uncalled = array_diff($expected, $actual);
    if (count($uncalled)) {
      $this->assertTrue(FALSE, new FormattableMarkup('Expected hooks %expected to be called but %uncalled was not called.', ['%expected' => implode(', ', $expected), '%uncalled' => implode(', ', $uncalled)]));
    }
    else {
      $this->assertTrue(TRUE, new FormattableMarkup('All the expected hooks were called: %expected', ['%expected' => empty($expected) ? '(none)' : implode(', ', $expected)]));
    }

    // Determine if there were any unexpected calls.
    $unexpected = array_diff($actual, $expected);
    if (count($unexpected)) {
      $this->assertTrue(FALSE, new FormattableMarkup('Unexpected hooks were called: %unexpected.', ['%unexpected' => empty($unexpected) ? '(none)' : implode(', ', $unexpected)]));
    }
    else {
      $this->assertTrue(TRUE, 'No unexpected hooks were called.');
    }
  }

  /**
   * Assert that a hook_file_* hook was called a certain number of times.
   *
   * @param string $hook
   *   String with the hook name, e.g. 'load', 'save', 'insert', etc.
   * @param int $expected_count
   *   Optional integer count.
   * @param string $message
   *   Optional translated string message.
   */
  public function assertFileHookCalled($hook, $expected_count = 1, $message = NULL) {
    $actual_count = count(file_test_get_calls($hook));

    if (!isset($message)) {
      if ($actual_count == $expected_count) {
        $message = new FormattableMarkup('hook_file_@name was called correctly.', ['@name' => $hook]);
      }
      elseif ($expected_count == 0) {
        $message = \Drupal::translation()->formatPlural($actual_count, 'hook_file_@name was not expected to be called but was actually called once.', 'hook_file_@name was not expected to be called but was actually called @count times.', ['@name' => $hook, '@count' => $actual_count]);
      }
      else {
        $message = new FormattableMarkup('hook_file_@name was expected to be called %expected times but was called %actual times.', ['@name' => $hook, '%expected' => $expected_count, '%actual' => $actual_count]);
      }
    }
    $this->assertEquals($expected_count, $actual_count, $message);
  }

  /**
   * Asserts that two files have the same values (except timestamp).
   *
   * @param \Drupal\file\FileInterface $before
   *   File object to compare.
   * @param \Drupal\file\FileInterface $after
   *   File object to compare.
   */
  public function assertFileUnchanged(FileInterface $before, FileInterface $after) {
    $this->assertEquals($before->id(), $after->id(), 'File id is the same');
    $this->assertEquals($before->getOwner()->id(), $after->getOwner()->id(), 'File owner is the same');
    $this->assertEquals($before->getFilename(), $after->getFilename(), 'File name is the same');
    $this->assertEquals($before->getFileUri(), $after->getFileUri(), 'File path is the same');
    $this->assertEquals($before->getMimeType(), $after->getMimeType(), 'File MIME type is the same');
    $this->assertEquals($before->getSize(), $after->getSize(), 'File size is the same');
    $this->assertEquals($before->isPermanent(), $after->isPermanent(), 'File status is the same');
  }

  /**
   * Asserts that two files are not the same by comparing the fid and filepath.
   *
   * @param \Drupal\file\FileInterface $file1
   *   File object to compare.
   * @param \Drupal\file\FileInterface $file2
   *   File object to compare.
   */
  public function assertDifferentFile(FileInterface $file1, FileInterface $file2) {
    $this->assertNotEquals($file1->id(), $file2->id(), 'Files have different ids');
    $this->assertNotEquals($file1->getFileUri(), $file2->getFileUri(), 'Files have different paths');
  }

  /**
   * Asserts that two files are the same by comparing the fid and filepath.
   *
   * @param \Drupal\file\FileInterface $file1
   *   File object to compare.
   * @param \Drupal\file\FileInterface $file2
   *   File object to compare.
   */
  public function assertSameFile(FileInterface $file1, FileInterface $file2) {
    $this->assertEquals($file1->id(), $file2->id(), 'Files have the same ids');
    $this->assertEquals($file1->getFileUri(), $file2->getFileUri(), 'Files have the same path');
  }

  /**
   * Create a file and save it to the files table and assert that it occurs
   * correctly.
   *
   * @param string $filepath
   *   Optional string specifying the file path. If none is provided then a
   *   randomly named file will be created in the site's files directory.
   * @param string $contents
   *   Optional contents to save into the file. If a NULL value is provided an
   *   arbitrary string will be used.
   * @param string $scheme
   *   Optional string indicating the stream scheme to use. Drupal core includes
   *   public, private, and temporary. The public wrapper is the default.
   *
   * @return \Drupal\file\FileInterface
   *   File entity.
   */
  public function createFile($filepath = NULL, $contents = NULL, $scheme = NULL) {
    // Don't count hook invocations caused by creating the file.
    \Drupal::state()->set('file_test.count_hook_invocations', FALSE);
    $file = File::create([
      'uri' => $this->createUri($filepath, $contents, $scheme),
      'uid' => 1,
    ]);
    $file->save();
    // Write the record directly rather than using the API so we don't invoke
    // the hooks.
    // Verify that the file was added to the database.
    $this->assertGreaterThan(0, $file->id());

    \Drupal::state()->set('file_test.count_hook_invocations', TRUE);
    return $file;
  }

  /**
   * Creates a file and returns its URI.
   *
   * @param string $filepath
   *   Optional string specifying the file path. If none is provided then a
   *   randomly named file will be created in the site's files directory.
   * @param string $contents
   *   Optional contents to save into the file. If a NULL value is provided an
   *   arbitrary string will be used.
   * @param string $scheme
   *   Optional string indicating the stream scheme to use. Drupal core includes
   *   public, private, and temporary. The public wrapper is the default.
   *
   * @return string
   *   File URI.
   */
  public function createUri($filepath = NULL, $contents = NULL, $scheme = NULL) {
    if (!isset($filepath)) {
      // Prefix with non-latin characters to ensure that all file-related
      // tests work with international filenames.
      // cSpell:disable-next-line
      $filepath = 'Файл для тестирования ' . $this->randomMachineName();
    }
    if (!isset($scheme)) {
      $scheme = 'public';
    }
    $filepath = $scheme . '://' . $filepath;

    if (!isset($contents)) {
      $contents = "file_put_contents() doesn't seem to appreciate empty strings so let's put in some data.";
    }

    file_put_contents($filepath, $contents);
    $this->assertFileExists($filepath);
    return $filepath;
  }

}
