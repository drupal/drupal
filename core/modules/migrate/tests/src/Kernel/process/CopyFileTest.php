<?php

namespace Drupal\Tests\migrate\Kernel\process;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\FileCopy;
use Drupal\migrate\Row;

/**
 * Tests the copy_file process plugin.
 *
 * @group migrate
 */
class CopyFileTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate', 'system'];

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->fileSystem = $this->container->get('file_system');
    $this->container->get('stream_wrapper_manager')->registerWrapper('temporary', 'Drupal\Core\StreamWrapper\TemporaryStream', StreamWrapperInterface::LOCAL_NORMAL);
  }

  /**
   * Test successful imports/copies.
   */
  public function testSuccessfulCopies() {
    $file = $this->createUri(NULL, NULL, 'temporary');
    $file_absolute = $this->fileSystem->realpath($file);
    $data_sets = [
      // Test a local to local copy.
      [
        $this->root . '/core/modules/simpletest/files/image-test.jpg',
        'public://file1.jpg'
      ],
      // Test a temporary file using an absolute path.
      [
        $file_absolute,
        'temporary://test.jpg'
      ],
      // Test a temporary file using a relative path.
      [
        $file_absolute,
        'temporary://core/modules/simpletest/files/test.jpg'
      ],
    ];
    foreach ($data_sets as $data) {
      list($source_path, $destination_path) = $data;
      $actual_destination = $this->doImport($source_path, $destination_path);
      $message = sprintf('File %s exists', $destination_path);
      $this->assertFileExists($destination_path, $message);
      // Make sure we didn't accidentally do a move.
      $this->assertFileExists($source_path, $message);
      $this->assertSame($actual_destination, $destination_path, 'The import returned the copied filename.');
    }
  }

  /**
   * Test successful file reuse.
   */
  public function testSuccessfulReuse() {
    $source_path = $this->root . '/core/modules/simpletest/files/image-test.jpg';
    $destination_path = 'public://file1.jpg';
    $file_reuse = file_unmanaged_copy($source_path, $destination_path);
    $timestamp = (new \SplFileInfo($file_reuse))->getMTime();
    $this->assertInternalType('int', $timestamp);

    // We need to make sure the modified timestamp on the file is sooner than
    // the attempted migration.
    sleep(1);
    $configuration = ['reuse' => TRUE];
    $this->doImport($source_path, $destination_path, $configuration);
    clearstatcache(TRUE, $destination_path);
    $modified_timestamp = (new \SplFileInfo($destination_path))->getMTime();
    $this->assertEquals($timestamp, $modified_timestamp);

    $configuration = ['reuse' => FALSE];
    $this->doImport($source_path, $destination_path, $configuration);
    clearstatcache(TRUE, $destination_path);
    $modified_timestamp = (new \SplFileInfo($destination_path))->getMTime();
    $this->assertGreaterThan($timestamp, $modified_timestamp);
  }

  /**
   * Test successful moves.
   */
  public function testSuccessfulMoves() {
    $file_1 = $this->createUri(NULL, NULL, 'temporary');
    $file_1_absolute = $this->fileSystem->realpath($file_1);
    $file_2 = $this->createUri(NULL, NULL, 'temporary');
    $file_2_absolute = $this->fileSystem->realpath($file_2);
    $local_file = $this->createUri(NULL, NULL, 'public');
    $data_sets = [
      // Test a local to local copy.
      [
        $local_file,
        'public://file1.jpg'
      ],
      // Test a temporary file using an absolute path.
      [
        $file_1_absolute,
        'temporary://test.jpg'
      ],
      // Test a temporary file using a relative path.
      [
        $file_2_absolute,
        'temporary://core/modules/simpletest/files/test.jpg'
      ],
    ];
    foreach ($data_sets as $data) {
      list($source_path, $destination_path) = $data;
      $actual_destination = $this->doImport($source_path, $destination_path, ['move' => TRUE]);
      $message = sprintf('File %s exists', $destination_path);
      $this->assertFileExists($destination_path, $message);
      $message = sprintf('File %s does not exist', $source_path);
      $this->assertFileNotExists($source_path, $message);
      $this->assertSame($actual_destination, $destination_path, 'The importer returned the moved filename.');
    }
  }

  /**
   * Test that non-existent files throw an exception.
   *
   * @expectedException \Drupal\migrate\MigrateException
   *
   * @expectedExceptionMessage File '/non/existent/file' does not exist
   */
  public function testNonExistentSourceFile() {
    $source = '/non/existent/file';
    $this->doImport($source, 'public://wontmatter.jpg');
  }

  /**
   * Test the 'rename' overwrite mode.
   */
  public function testRenameFile() {
    $source = $this->createUri(NULL, NULL, 'temporary');
    $destination = $this->createUri('foo.txt', NULL, 'public');
    $expected_destination = 'public://foo_0.txt';
    $actual_destination = $this->doImport($source, $destination, ['rename' => TRUE]);
    $this->assertFileExists($expected_destination, 'File was renamed on import');
    $this->assertSame($actual_destination, $expected_destination, 'The importer returned the renamed filename.');
  }

  /**
   * Do an import using the destination.
   *
   * @param string $source_path
   *   Source path to copy from.
   * @param string $destination_path
   *   The destination path to copy to.
   * @param array $configuration
   *   Process plugin configuration settings.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function doImport($source_path, $destination_path, $configuration = []) {
    $plugin = FileCopy::create($this->container, $configuration, 'file_copy', []);
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();
    $row = new Row();

    $result = $plugin->transform([$source_path, $destination_path], $executable, $row, 'foobaz');

    // Return the imported file Uri.
    return $result;
  }

  /**
   * Create a file and return the URI of it.
   *
   * @param $filepath
   *   Optional string specifying the file path. If none is provided then a
   *   randomly named file will be created in the site's files directory.
   * @param $contents
   *   Optional contents to save into the file. If a NULL value is provided an
   *   arbitrary string will be used.
   * @param $scheme
   *   Optional string indicating the stream scheme to use. Drupal core includes
   *   public, private, and temporary. The public wrapper is the default.
   * @return
   *   File URI.
   */
  protected function createUri($filepath = NULL, $contents = NULL, $scheme = NULL) {
    if (!isset($filepath)) {
      // Prefix with non-latin characters to ensure that all file-related
      // tests work with international filenames.
      $filepath = 'Файл для тестирования ' . $this->randomMachineName();
    }
    if (empty($scheme)) {
      $scheme = file_default_scheme();
    }
    $filepath = $scheme . '://' . $filepath;

    if (empty($contents)) {
      $contents = "file_put_contents() doesn't seem to appreciate empty strings so let's put in some data.";
    }

    file_put_contents($filepath, $contents);
    $this->assertFileExists($filepath, t('The test file exists on the disk.'));
    return $filepath;
  }

}
