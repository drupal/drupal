<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\destination\EntityFileTest.
 */

namespace Drupal\Tests\migrate\Unit\destination;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\EntityFile;
use Drupal\simpletest\KernelTestBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\migrate\MigrateException;

/**
 * Tests the entity file destination plugin.
 *
 * @group migrate
 */
class EntityFileTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('system', 'entity_test', 'user');

  /**
   * @var \Drupal\Tests\migrate\Unit\destination\TestEntityFile $destination
   */
  protected $destination;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->destination = new TestEntityFile([]);

    $this->installConfig(['system']);
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
    global $base_url;
    return [
      // Test a local to local copy.
      [['filepath' => 'core/modules/simpletest/files/image-test.jpg'], 'public://file1.jpg', TRUE, DRUPAL_ROOT . '/'],
      // Test a temporary file using an absolute path.
      [['filepath' => '/tmp/test-file.jpg'], 'temporary://test.jpg', TRUE, ''],
      // Test a remote path to local.
      [['filepath' => 'core/modules/simpletest/files/image-test.jpg'], 'public://remote-file.jpg', TRUE, $base_url . '/'],
      // Test a remote path to local inside a folder that doesn't exist.
      [['filepath' => 'core/modules/simpletest/files/image-test.jpg'], 'public://folder/remote-file.jpg', TRUE, DRUPAL_ROOT . '/'],
    ];
  }


  /**
   * Test that non-existent files throw an exception.
   */
  public function testNonExistentSourceFile() {
    try {
      $this->doImport(['filepath' => '/none/existent/file'], 'public://wontmatter.jpg');
    }
    catch (MigrateException $e) {}
    $destination = '/none/existent/file';
    $this->assertIdentical($e->getMessage(), 'File <em class="placeholder">' . $destination . '</em> could not be copied to <em class="placeholder">public://wontmatter.jpg</em>.');
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

  public function __construct($configuration) {
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
