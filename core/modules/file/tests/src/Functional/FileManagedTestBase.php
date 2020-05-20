<?php

namespace Drupal\Tests\file\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Base class for file tests that use the file_test module to test uploads and
 * hooks.
 */
abstract class FileManagedTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file_test', 'file'];

  protected function setUp() {
    parent::setUp();
    // Clear out any hook calls.
    file_test_reset();
  }

  /**
   * Assert that all of the specified hook_file_* hooks were called once, other
   * values result in failure.
   *
   * @param string[] $expected
   *   An array of strings containing with the hook name; for example, 'load',
   *   'save', 'insert', etc.
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
   *   String with the hook name; for instance, 'load', 'save', 'insert', etc.
   * @param int $expected_count
   *   Optional integer count.
   * @param string|null $message
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
    $this->assertEqual($actual_count, $expected_count, $message);
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
    $this->assertEqual($before->id(), $after->id(), t('File id is the same: %file1 == %file2.', ['%file1' => $before->id(), '%file2' => $after->id()]), 'File unchanged');
    $this->assertEqual($before->getOwner()->id(), $after->getOwner()->id(), t('File owner is the same: %file1 == %file2.', ['%file1' => $before->getOwner()->id(), '%file2' => $after->getOwner()->id()]), 'File unchanged');
    $this->assertEqual($before->getFilename(), $after->getFilename(), t('File name is the same: %file1 == %file2.', ['%file1' => $before->getFilename(), '%file2' => $after->getFilename()]), 'File unchanged');
    $this->assertEqual($before->getFileUri(), $after->getFileUri(), t('File path is the same: %file1 == %file2.', ['%file1' => $before->getFileUri(), '%file2' => $after->getFileUri()]), 'File unchanged');
    $this->assertEqual($before->getMimeType(), $after->getMimeType(), t('File MIME type is the same: %file1 == %file2.', ['%file1' => $before->getMimeType(), '%file2' => $after->getMimeType()]), 'File unchanged');
    $this->assertEqual($before->getSize(), $after->getSize(), t('File size is the same: %file1 == %file2.', ['%file1' => $before->getSize(), '%file2' => $after->getSize()]), 'File unchanged');
    $this->assertEqual($before->isPermanent(), $after->isPermanent(), t('File status is the same: %file1 == %file2.', ['%file1' => $before->isPermanent(), '%file2' => $after->isPermanent()]), 'File unchanged');
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
    $this->assertNotEqual($file1->id(), $file2->id(), t('Files have different ids: %file1 != %file2.', ['%file1' => $file1->id(), '%file2' => $file2->id()]), 'Different file');
    $this->assertNotEqual($file1->getFileUri(), $file2->getFileUri(), t('Files have different paths: %file1 != %file2.', ['%file1' => $file1->getFileUri(), '%file2' => $file2->getFileUri()]), 'Different file');
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
    $this->assertEqual($file1->id(), $file2->id(), t('Files have the same ids: %file1 == %file2.', ['%file1' => $file1->id(), '%file2-fid' => $file2->id()]), 'Same file');
    $this->assertEqual($file1->getFileUri(), $file2->getFileUri(), t('Files have the same path: %file1 == %file2.', ['%file1' => $file1->getFileUri(), '%file2' => $file2->getFileUri()]), 'Same file');
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
    $this->assertTrue($file->id() > 0, 'The file was added to the database.', 'Create test file');

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
