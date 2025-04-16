<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\File;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;

/**
 * Provides file-specific assertions and helper functions.
 */
abstract class FileTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * A stream wrapper scheme to register for the test.
   *
   * @var string
   */
  protected $scheme;

  /**
   * A fully-qualified stream wrapper class name to register for the test.
   *
   * @var string
   */
  protected $classname;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // \Drupal\KernelTests\KernelTestBase::bootKernel() sets a global override
    // for the default scheme because core relies on it in
    // file_default_scheme(). As we are creating the configuration here remove
    // the global override.
    unset($GLOBALS['config']['system.file']);
    \Drupal::configFactory()->getEditable('system.file')
      ->set('default_scheme', 'public')
      ->set('allow_insecure_uploads', FALSE)
      ->set('temporary_maximum_age', 21600)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    $container->register('stream_wrapper.private', 'Drupal\Core\StreamWrapper\PrivateStream')
      ->addTag('stream_wrapper', ['scheme' => 'private']);

    if (isset($this->scheme)) {
      $container->register('stream_wrapper.' . $this->scheme, $this->classname)
        ->addTag('stream_wrapper', ['scheme' => $this->scheme]);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpFilesystem() {
    $public_file_directory = $this->siteDirectory . '/files';
    $private_file_directory = $this->siteDirectory . '/private';

    mkdir($this->siteDirectory, 0775);
    mkdir($this->siteDirectory . '/files', 0775);
    mkdir($this->siteDirectory . '/private', 0775);
    mkdir($this->siteDirectory . '/files/config/sync', 0775, TRUE);

    $this->setSetting('file_public_path', $public_file_directory);
    $this->setSetting('file_private_path', $private_file_directory);
    $this->setSetting('config_sync_directory', $this->siteDirectory . '/files/config/sync');
  }

  /**
   * Helper function to test the permissions of a file.
   *
   * @param string $filepath
   *   String file path.
   * @param int $expected_mode
   *   Octal integer like 0664 or 0777.
   * @param string|null $message
   *   Optional message.
   */
  public function assertFilePermissions($filepath, $expected_mode, $message = NULL) {
    // Clear out PHP's file stat cache to be sure we see the current value.
    clearstatcache(TRUE, $filepath);

    // Mask out all but the last three octets.
    $actual_mode = fileperms($filepath) & 0777;

    // PHP on Windows has limited support for file permissions. Usually each of
    // "user", "group" and "other" use one octal digit (3 bits) to represent the
    // read/write/execute bits. On Windows, chmod() ignores the "group" and
    // "other" bits, and fileperms() returns the "user" bits in all three
    // positions. $expected_mode is updated to reflect this.
    if (str_starts_with(PHP_OS, 'WIN')) {
      // Reset the "group" and "other" bits.
      $expected_mode = $expected_mode & 0700;
      // Shift the "user" bits to the "group" and "other" positions also.
      $expected_mode = $expected_mode | $expected_mode >> 3 | $expected_mode >> 6;
    }

    if (!isset($message)) {
      $message = sprintf('Expected file permission to be %s, actually were %s.', decoct($actual_mode), decoct($expected_mode));
    }
    $this->assertEquals($expected_mode, $actual_mode, $message);
  }

  /**
   * Helper function to test the permissions of a directory.
   *
   * @param string $directory
   *   String directory path.
   * @param int $expected_mode
   *   Octal integer like 0664 or 0777.
   * @param string|null $message
   *   Optional message.
   */
  public function assertDirectoryPermissions($directory, $expected_mode, $message = NULL) {
    // Clear out PHP's file stat cache to be sure we see the current value.
    clearstatcache(TRUE, $directory);

    // Mask out all but the last three octets.
    $actual_mode = fileperms($directory) & 0777;
    $expected_mode = $expected_mode & 0777;

    // PHP on Windows has limited support for file permissions. Usually each of
    // "user", "group" and "other" use one octal digit (3 bits) to represent the
    // read/write/execute bits. On Windows, chmod() ignores the "group" and
    // "other" bits, and fileperms() returns the "user" bits in all three
    // positions. $expected_mode is updated to reflect this.
    if (str_starts_with(PHP_OS, 'WIN')) {
      // Reset the "group" and "other" bits.
      $expected_mode = $expected_mode & 0700;
      // Shift the "user" bits to the "group" and "other" positions also.
      $expected_mode = $expected_mode | $expected_mode >> 3 | $expected_mode >> 6;
    }

    if (!isset($message)) {
      $message = sprintf('Expected directory permission to be %s, actually were %s.', decoct($expected_mode), decoct($actual_mode));
    }
    $this->assertEquals($expected_mode, $actual_mode, $message);
  }

  /**
   * Create a directory and assert it exists.
   *
   * @param string $path
   *   Optional string with a directory path. If none is provided, a random
   *   name in the site's files directory will be used.
   *
   * @return string
   *   The path to the directory.
   */
  public function createDirectory($path = NULL) {
    // A directory to operate on.
    if (!isset($path)) {
      $path = 'public://' . $this->randomMachineName();
    }
    $this->assertTrue(\Drupal::service('file_system')->mkdir($path));
    $this->assertDirectoryExists($path);
    return $path;
  }

  /**
   * Create a file and return the URI of it.
   *
   * @param string|null $filepath
   *   Optional string specifying the file path. If none is provided then a
   *   randomly named file will be created in the site's files directory.
   * @param string|null $contents
   *   Optional contents to save into the file. If a NULL value is provided an
   *   arbitrary string will be used.
   * @param string|null $scheme
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
