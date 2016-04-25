<?php

/**
 * @file
 * Contains \Drupal\Tests\file\Kernel\Migrate\EntityFileTest.
 */

namespace Drupal\Tests\file\Kernel\Migrate;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\migrate\Row;
use Drupal\file\Plugin\migrate\destination\EntityFile;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\migrate\MigrateException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the entity file destination plugin.
 *
 * @group file
 */
class EntityFileTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('system', 'entity_test', 'user', 'file');

  /**
   * @var \Drupal\Tests\file\Kernel\Migrate\TestEntityFile $destination
   */
  protected $destination;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    \Drupal::getContainer()->get('stream_wrapper_manager')->registerWrapper('public', 'Drupal\Core\StreamWrapper\PublicStream', StreamWrapperInterface::NORMAL);
    $this->destination = new TestEntityFile([]);
    $this->installEntitySchema('file');

    file_put_contents('/tmp/test-file.jpg', '');
  }

  /**
   * Test successful imports/copies.
   */
  public function testSuccessfulCopies() {
    foreach ($this->localFileDataProvider() as $data) {
      list($row_values, $destination_path, $expected, $source_base_path) = $data;

      $this->doImport($row_values, $destination_path, $source_base_path);
      $message = $expected ? sprintf('File %s exists', $destination_path) : sprintf('File %s does not exist', $destination_path);
      $this->assertIdentical($expected, is_file($destination_path), $message);
    }
  }

  /**
   * The data provider for testing the file destination.
   *
   * @return array
   *   An array of file permutations to test.
   */
  protected function localFileDataProvider() {
    return [
      // Test a local to local copy.
      [['filepath' => 'core/modules/simpletest/files/image-test.jpg'], 'public://file1.jpg', TRUE, $this->root . '/'],
      // Test a temporary file using an absolute path.
      [['filepath' => '/tmp/test-file.jpg'], 'temporary://test.jpg', TRUE, ''],
      // Test a temporary file using a relative path.
      [['filepath' => 'test-file.jpg'], 'temporary://core/modules/simpletest/files/test.jpg', TRUE, '/tmp/'],
      // Test a remote path to local.
      [['filepath' => 'core/modules/simpletest/files/image-test.jpg'], 'public://remote-file.jpg', TRUE, $this->root . '/'],
      // Test a remote path to local inside a folder that doesn't exist.
      [['filepath' => 'core/modules/simpletest/files/image-test.jpg'], 'public://folder/remote-file.jpg', TRUE, $this->root . '/'],
    ];
  }

  /**
   * Test that non-existent files throw an exception.
   */
  public function testNonExistentSourceFile() {
    $destination = '/non/existent/file';
    try {
      // If this test passes, doImport() will raise a MigrateException and
      // we'll never reach fail().
      $this->doImport(['filepath' => $destination], 'public://wontmatter.jpg');
      $this->fail('Expected Drupal\migrate\MigrateException when importing ' . $destination);
    }
    catch (MigrateException $e) {
      $this->assertIdentical($e->getMessage(), "File '$destination' does not exist.");
    }
  }

  /**
   * Tests various invocations of the writeFile() method.
   */
  public function testWriteFile() {
    $plugin = $this->destination;
    $method = new \ReflectionMethod($plugin, 'writeFile');
    $method->setAccessible(TRUE);

    touch('temporary://baz.txt');

    // Moving an actual file should return TRUE.
    $plugin->configuration['move'] = TRUE;
    $this->assertTrue($method->invoke($plugin, 'temporary://baz.txt', 'public://foo.txt'));

    // Trying to move a non-existent file should return FALSE.
    $this->assertFalse($method->invoke($plugin, 'temporary://invalid.txt', 'public://invalid.txt'));

    // Copying over a file that already exists should replace the existing file.
    $plugin->configuration['move'] = FALSE;
    touch('temporary://baz.txt');
    $this->assertTrue($method->invoke($plugin, 'temporary://baz.txt', 'public://foo.txt'));
    // Copying over a file that already exists should rename the resulting file
    // if FILE_EXISTS_RENAME is specified.
    $method->invoke($plugin, 'temporary://baz.txt', 'public://foo.txt', FILE_EXISTS_RENAME);
    $this->assertTrue(file_exists('public://foo_0.txt'));

    // Trying to copy a non-existent file should return FALSE.
    $this->assertFalse($method->invoke($plugin, 'temporary://invalid.txt', 'public://invalid.txt'));
  }

  /**
   * Tests various invocations of the getOverwriteMode() method.
   */
  public function testGetOverwriteMode() {
    $plugin = $this->destination;
    $method = new \ReflectionMethod($plugin, 'getOverwriteMode');
    $method->setAccessible(TRUE);

    $row = new Row([], []);
    // If the plugin is not configured to rename the destination file, we should
    // always get FILE_EXISTS_REPLACE.
    $this->assertIdentical(FILE_EXISTS_REPLACE, $method->invoke($plugin, $row));

    // When the plugin IS configured to rename the destination file, it should
    // return FILE_EXISTS_RENAME if the destination entity already exists,
    // and FILE_EXISTS_REPLACE otherwise.
    $plugin->configuration['rename'] = TRUE;
    $plugin->storage = \Drupal::entityManager()->getStorage('file');
    /** @var \Drupal\file\FileInterface $file */
    $file = $plugin->storage->create();
    touch('public://foo.txt');
    $file->setFileUri('public://foo.txt');
    $file->save();
    $row->setDestinationProperty($plugin->storage->getEntityType()->getKey('id'), $file->id());
    $this->assertIdentical(FILE_EXISTS_RENAME, $method->invoke($plugin, $row));
    unlink('public://foo.txt');
  }

  /**
   * Tests various invocations of the getDirectory() method.
   */
  public function testGetDirectory() {
    $plugin = $this->destination;
    $method = new \ReflectionMethod($plugin, 'getDirectory');
    $method->setAccessible(TRUE);

    $this->assertSame('public://foo', $method->invoke($plugin, 'public://foo/baz.txt'));
    $this->assertSame('/path/to', $method->invoke($plugin, '/path/to/foo.txt'));
    // A directory like public:// (no path) needs to resolve to a physical path.
    $fs = \Drupal::getContainer()->get('file_system');
    $this->assertSame($fs->realpath('public://'), $method->invoke($plugin, 'public://foo.txt'));
  }

  /**
   * Tests various invocations of the isLocationUnchanged() method.
   */
  public function testIsLocationUnchanged() {
    $plugin = $this->destination;
    $method = new \ReflectionMethod($plugin, 'isLocationUnchanged');
    $method->setAccessible(TRUE);

    $temporary_file = '/tmp/foo.txt';
    touch($temporary_file);
    $this->assertTrue($method->invoke($plugin, $temporary_file, 'temporary://foo.txt'));
    unlink($temporary_file);
  }

  /**
   * Tests various invocations of the isLocalUri() method.
   */
  public function testIsLocalUri() {
    $plugin = $this->destination;
    $method = new \ReflectionMethod($plugin, 'isLocalUri');
    $method->setAccessible(TRUE);

    $this->assertTrue($method->invoke($plugin, 'public://foo.txt'));
    $this->assertTrue($method->invoke($plugin, 'public://path/to/foo.txt'));
    $this->assertTrue($method->invoke($plugin, 'temporary://foo.txt'));
    $this->assertTrue($method->invoke($plugin, 'temporary://path/to/foo.txt'));
    $this->assertTrue($method->invoke($plugin, 'foo.txt'));
    $this->assertTrue($method->invoke($plugin, '/path/to/files/foo.txt'));
    $this->assertTrue($method->invoke($plugin, 'relative/path/to/foo.txt'));
    $this->assertFalse($method->invoke($plugin, 'http://www.example.com/foo.txt'));
  }

  /**
   * Do an import using the destination.
   *
   * @param array $row_values
   *   An array of row values.
   * @param string $destination_path
   *   The destination path to copy to.
   * @param string $source_base_path
   *   The source base path.
   * @return array
   *   An array of saved entities ids.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function doImport($row_values, $destination_path, $source_base_path = '') {
    $row = new Row($row_values, []);
    $row->setDestinationProperty('uri', $destination_path);
    $this->destination->configuration['source_base_path'] = $source_base_path;

    // Importing asserts there are no errors, then we just check the file has
    // been copied into place.
    return $this->destination->import($row, array());
  }

}

class TestEntityFile extends EntityFile {

  /**
   * This is needed to be passed to $this->save().
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  public $mockEntity;

  /**
   * Make this public for easy writing during tests.
   *
   * @var array
   */
  public $configuration;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  public $storage;

  public function __construct($configuration = []) {
    $configuration +=  array(
      'source_base_path' => '',
      'source_path_property' => 'filepath',
      'destination_path_property' => 'uri',
      'move' => FALSE,
      'urlencode' => FALSE,
    );
    $this->configuration = $configuration;
    // We need a mock entity to be passed to save to prevent strict exceptions.
    $this->mockEntity = EntityTest::create();
    $this->streamWrapperManager = \Drupal::getContainer()->get('stream_wrapper_manager');
    $this->fileSystem = \Drupal::getContainer()->get('file_system');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntity(Row $row, array $old_destination_id_values) {
    return $this->mockEntity;
  }

  /**
   * {@inheritdoc}
   */
  protected function save(ContentEntityInterface $entity, array $old_destination_id_values = array()) {}

}
