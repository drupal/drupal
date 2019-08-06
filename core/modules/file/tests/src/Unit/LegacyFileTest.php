<?php

namespace Drupal\Tests\file\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\file\FileInterface;
use Drupal\file\FileUsage\DatabaseFileUsageBackend;
use Drupal\file\FileUsage\FileUsageBase;
use PHPUnit\Framework\TestCase;

/**
 * Provides unit tests for file module deprecation errors.
 *
 * @group file
 * @group legacy
 */
class LegacyFileTest extends TestCase {

  /**
   * A mocked ConfigFactoryInterface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class)->reveal();
    $container = new ContainerBuilder();
    $container->set('config.factory', $this->configFactory);
    \Drupal::setContainer($container);
  }

  /**
   * Tests passing legacy arguments to FileUsageBase::__construct().
   *
   * @expectedDeprecation Not passing the $config_factory parameter to Drupal\file\FileUsage\FileUsageBase::__construct is deprecated in drupal:8.4.0 and will trigger a fatal error in drupal:9.0.0. See https://www.drupal.org/project/drupal/issues/2801777
   *
   * @throws \ReflectionException
   */
  public function testFileUsageBaseConstruct() {
    $test_file_usage = new TestFileUsage();
    $reflection = new \ReflectionObject($test_file_usage);
    $config = $reflection->getProperty('configFactory');
    $config->setAccessible(TRUE);
    $this->assertSame($this->configFactory, $config->getValue($test_file_usage));
  }

  /**
   * Tests passing legacy arguments to DatabaseFileUsageBackend::__construct().
   *
   * @expectedDeprecation Passing the database connection as the first argument to Drupal\file\FileUsage\DatabaseFileUsageBackend::__construct is deprecated in drupal:8.8.0 and will throw a fatal error in drupal:9.0.0. Pass the config factory first. See https://www.drupal.org/node/3070148
   *
   * @throws \ReflectionException
   */
  public function testDatabaseFileUsageBackendConstruct() {
    $connection = $this->prophesize(Connection::class)->reveal();
    $database_file_usage = new DatabaseFileUsageBackend($connection);
    $reflection = new \ReflectionObject($database_file_usage);
    $reflection_config = $reflection->getProperty('configFactory');
    $reflection_config->setAccessible(TRUE);
    $reflection_connection = $reflection->getProperty('connection');
    $reflection_connection->setAccessible(TRUE);
    $reflection_table_name = $reflection->getProperty('tableName');
    $reflection_table_name->setAccessible(TRUE);
    $this->assertSame($this->configFactory, $reflection_config->getValue($database_file_usage));
    $this->assertSame($connection, $reflection_connection->getValue($database_file_usage));
    $this->assertSame('file_usage', $reflection_table_name->getValue($database_file_usage));

    $database_file_usage_test_table = new DatabaseFileUsageBackend($connection, 'test_table');
    $this->assertSame('test_table', $reflection_table_name->getValue($database_file_usage_test_table));

    $this->expectException(\InvalidArgumentException::class);
    $database_file_usage_exception = new DatabaseFileUsageBackend('Invalid Argument');
  }

}

/**
 * Provides a pass through to the abstract FileUsageBase() constructor.
 */
class TestFileUsage extends FileUsageBase {

  /**
   * {@inheritdoc}
   */
  public function add(FileInterface $file, $module, $type, $id, $count = 1) {
  }

  /**
   * {@inheritdoc}
   */
  public function delete(FileInterface $file, $module, $type = NULL, $id = NULL, $count = 1) {
  }

  /**
   * {@inheritdoc}
   */
  public function listUsage(FileInterface $file) {
  }

}
