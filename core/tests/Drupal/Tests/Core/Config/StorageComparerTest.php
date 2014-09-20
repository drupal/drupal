<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\StorageComparerTest.
 */

namespace Drupal\Tests\Core\Config;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Config\StorageComparer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Config\StorageComparer
 * @group Config
 */
class StorageComparerTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\Config\StorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $sourceStorage;

  /**
   * @var \Drupal\Core\Config\StorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $targetStorage;

  /**
   * @var \Drupal\Core\Config\ConfigManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configManager;

  /**
   * The storage comparer to test.
   *
   * @var \Drupal\Core\Config\StorageComparer
   */
  protected $storageComparer;

  /**
   * An array of test configuration data keyed by configuration name.
   *
   * @var array
   */
  protected $configData;

  protected function setUp() {
    $this->sourceStorage = $this->getMock('Drupal\Core\Config\StorageInterface');
    $this->targetStorage = $this->getMock('Drupal\Core\Config\StorageInterface');
    $this->configManager = $this->getMock('Drupal\Core\Config\ConfigManagerInterface');
    $this->storageComparer = new StorageComparer($this->sourceStorage, $this->targetStorage, $this->configManager);
  }

  protected function getConfigData() {
    $uuid = new Php();
    // Mock data using minimal data to use ConfigDependencyManger.
    $this->configData = array(
      // Simple config that controls configuration sync.
      'system.site' => array(
        'title' => 'Drupal',
        'uuid' => $uuid->generate(),
      ),
      // Config entity which requires another config entity.
      'field.field.node.article.body' => array(
        'id' => 'node.article.body',
        'uuid' => $uuid->generate(),
        'dependencies' => array(
          'entity' => array(
            'field.storage.node.body'
          ),
        ),
      ),
      // Config entity which is required by another config entity.
      'field.storage.node.body' => array(
        'id' => 'node.body',
        'uuid' => $uuid->generate(),
        'dependencies' => array(
          'module' => array(
            'text',
          ),
        ),
      ),
      // Config entity not which has no dependencies on configuration.
      'views.view.test_view' => array(
        'id' => 'test_view',
        'uuid' => $uuid->generate(),
        'dependencies' => array(
          'module' => array(
            'node',
          ),
        ),
      ),
      // Simple config.
      'system.performance' => array(
        'stale_file_threshold' => 2592000
      ),

    );
    return $this->configData;
  }

  /**
   * @covers ::createChangelist
   */
  public function testCreateChangelistNoChange() {
    $config_data = $this->getConfigData();
    $config_files = array_keys($config_data);
    $this->sourceStorage->expects($this->once())
      ->method('listAll')
      ->will($this->returnValue($config_files));
    $this->targetStorage->expects($this->once())
      ->method('listAll')
      ->will($this->returnValue($config_files));
    $this->sourceStorage->expects($this->once())
      ->method('readMultiple')
      ->will($this->returnValue($config_data));
    $this->targetStorage->expects($this->once())
      ->method('readMultiple')
      ->will($this->returnValue($config_data));
    $this->sourceStorage->expects($this->once())
      ->method('getAllCollectionNames')
      ->will($this->returnValue(array()));
    $this->targetStorage->expects($this->once())
      ->method('getAllCollectionNames')
      ->will($this->returnValue(array()));
    $this->configManager->expects($this->any())
      ->method('supportsConfigurationEntities')
      ->will($this->returnValue(TRUE));

    $this->storageComparer->createChangelist();
    $this->assertEmpty($this->storageComparer->getChangelist('create'));
    $this->assertEmpty($this->storageComparer->getChangelist('delete'));
    $this->assertEmpty($this->storageComparer->getChangelist('update'));
  }

  /**
   * @covers ::createChangelist
   */
  public function testCreateChangelistCreate() {
    $target_data = $source_data = $this->getConfigData();
    unset($target_data['field.storage.node.body']);
    unset($target_data['field.field.node.article.body']);
    unset($target_data['views.view.test_view']);

    $this->sourceStorage->expects($this->once())
      ->method('listAll')
      ->will($this->returnValue(array_keys($source_data)));
    $this->targetStorage->expects($this->once())
      ->method('listAll')
      ->will($this->returnValue(array_keys($target_data)));
    $this->sourceStorage->expects($this->once())
      ->method('readMultiple')
      ->will($this->returnValue($source_data));
    $this->targetStorage->expects($this->once())
      ->method('readMultiple')
      ->will($this->returnValue($target_data));
    $this->sourceStorage->expects($this->once())
      ->method('getAllCollectionNames')
      ->will($this->returnValue(array()));
    $this->targetStorage->expects($this->once())
      ->method('getAllCollectionNames')
      ->will($this->returnValue(array()));
    $this->configManager->expects($this->any())
      ->method('supportsConfigurationEntities')
      ->will($this->returnValue(TRUE));

    $this->storageComparer->createChangelist();
    $expected = array(
      'field.storage.node.body',
      'views.view.test_view',
      'field.field.node.article.body',
    );
    $this->assertEquals($expected, $this->storageComparer->getChangelist('create'));
    $this->assertEmpty($this->storageComparer->getChangelist('delete'));
    $this->assertEmpty($this->storageComparer->getChangelist('update'));
  }

  /**
   * @covers ::createChangelist
   */
  public function testCreateChangelistDelete() {
    $target_data = $source_data = $this->getConfigData();
    unset($source_data['field.storage.node.body']);
    unset($source_data['field.field.node.article.body']);
    unset($source_data['views.view.test_view']);

    $this->sourceStorage->expects($this->once())
      ->method('listAll')
      ->will($this->returnValue(array_keys($source_data)));
    $this->targetStorage->expects($this->once())
      ->method('listAll')
      ->will($this->returnValue(array_keys($target_data)));
    $this->sourceStorage->expects($this->once())
      ->method('readMultiple')
      ->will($this->returnValue($source_data));
    $this->targetStorage->expects($this->once())
      ->method('readMultiple')
      ->will($this->returnValue($target_data));
    $this->sourceStorage->expects($this->once())
      ->method('getAllCollectionNames')
      ->will($this->returnValue(array()));
    $this->targetStorage->expects($this->once())
      ->method('getAllCollectionNames')
      ->will($this->returnValue(array()));
    $this->configManager->expects($this->any())
      ->method('supportsConfigurationEntities')
      ->will($this->returnValue(TRUE));

    $this->storageComparer->createChangelist();
    $expected = array(
      'field.field.node.article.body',
      'views.view.test_view',
      'field.storage.node.body',
    );
    $this->assertEquals($expected, $this->storageComparer->getChangelist('delete'));
    $this->assertEmpty($this->storageComparer->getChangelist('create'));
    $this->assertEmpty($this->storageComparer->getChangelist('update'));
  }

  /**
   * @covers ::createChangelist
   */
  public function testCreateChangelistUpdate() {
    $target_data = $source_data = $this->getConfigData();
    $source_data['system.site']['title'] = 'Drupal New!';
    $source_data['field.field.node.article.body']['new_config_key'] = 'new data';
    $source_data['field.storage.node.body']['new_config_key'] = 'new data';

    $this->sourceStorage->expects($this->once())
      ->method('listAll')
      ->will($this->returnValue(array_keys($source_data)));
    $this->targetStorage->expects($this->once())
      ->method('listAll')
      ->will($this->returnValue(array_keys($target_data)));
    $this->sourceStorage->expects($this->once())
      ->method('readMultiple')
      ->will($this->returnValue($source_data));
    $this->targetStorage->expects($this->once())
      ->method('readMultiple')
      ->will($this->returnValue($target_data));
    $this->sourceStorage->expects($this->once())
      ->method('getAllCollectionNames')
      ->will($this->returnValue(array()));
    $this->targetStorage->expects($this->once())
      ->method('getAllCollectionNames')
      ->will($this->returnValue(array()));
    $this->configManager->expects($this->any())
      ->method('supportsConfigurationEntities')
      ->will($this->returnValue(TRUE));

    $this->storageComparer->createChangelist();
    $expected = array(
      'field.storage.node.body',
      'system.site',
      'field.field.node.article.body',
    );
    $this->assertEquals($expected, $this->storageComparer->getChangelist('update'));
    $this->assertEmpty($this->storageComparer->getChangelist('create'));
    $this->assertEmpty($this->storageComparer->getChangelist('delete'));
  }

}
