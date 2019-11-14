<?php

namespace Drupal\Tests\migrate\Unit;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;

/**
 * Base class for Migrate module source unit tests.
 *
 * @deprecated in drupal:8.2.0 and is removed from drupal:9.0.0. Use
 * \Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase instead.
 */
abstract class MigrateSqlSourceTestCase extends MigrateTestCase {

  /**
   * The tested source plugin.
   *
   * @var \Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase
   */
  protected $source;

  /**
   * The database contents.
   *
   * Database contents represents a mocked database. It should contain an
   * associative array with the table name as key, and as many nested arrays as
   * the number of mocked rows. Each of those faked rows must be another array
   * with the column name as the key and the value as the cell.
   *
   * @var array
   */
  protected $databaseContents = [];

  /**
   * The plugin class under test.
   *
   * The plugin system is not working during unit testing so the source plugin
   * class needs to be manually specified.
   *
   * @var string
   */
  const PLUGIN_CLASS = '';

  /**
   * The high water mark at the beginning of the import operation.
   *
   * Once the migration is run, we save a mark of the migrated sources, so the
   * migration can run again and update only new sources or changed sources.
   *
   * @var mixed
   */
  const ORIGINAL_HIGH_WATER = NULL;

  /**
   * Expected results after the source parsing.
   *
   * @var array
   */
  protected $expectedResults = [];

  /**
   * Expected count of source rows.
   *
   * @var int
   */
  protected $expectedCount = 0;

  /**
   * The source plugin instance under test.
   *
   * @var \Drupal\migrate\Plugin\MigrateSourceInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $module_handler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $state = $this->createMock('Drupal\Core\State\StateInterface');
    $entity_manager = $this->createMock('Drupal\Core\Entity\EntityManagerInterface');

    // Mock a key-value store to return high-water values.
    $key_value = $this->createMock(KeyValueStoreInterface::class);

    // SourcePluginBase does not yet support full dependency injection so we
    // need to make sure that \Drupal::keyValue() works as expected by mocking
    // the keyvalue service.
    $key_value_factory = $this->createMock(KeyValueFactoryInterface::class);
    $key_value_factory
      ->method('get')
      ->with('migrate:high_water')
      ->willReturn($key_value);

    try {
      $container = \Drupal::getContainer();
    }
    catch (ContainerNotInitializedException $e) {
      $container = new ContainerBuilder();
    }
    $container->set('keyvalue', $key_value_factory);
    \Drupal::setContainer($container);

    $migration = $this->getMigration();

    // Set the high water value.
    \Drupal::keyValue('migrate:high_water')
      ->expects($this->any())
      ->method('get')
      ->willReturn(static::ORIGINAL_HIGH_WATER);

    // Setup the plugin.
    $plugin_class = static::PLUGIN_CLASS;
    $plugin = new $plugin_class($this->migrationConfiguration['source'], $this->migrationConfiguration['source']['plugin'], [], $migration, $state, $entity_manager);

    // Do some reflection to set the database and moduleHandler.
    $plugin_reflection = new \ReflectionClass($plugin);
    $database_property = $plugin_reflection->getProperty('database');
    $database_property->setAccessible(TRUE);
    $module_handler_property = $plugin_reflection->getProperty('moduleHandler');
    $module_handler_property->setAccessible(TRUE);

    // Set the database and the module handler onto our plugin.
    $database_property->setValue($plugin, $this->getDatabase($this->databaseContents + ['test_map' => []]));
    $module_handler_property->setValue($plugin, $module_handler);

    $plugin->setStringTranslation($this->getStringTranslationStub());
    $migration->expects($this->any())
      ->method('getSourcePlugin')
      ->will($this->returnValue($plugin));
    $this->source = $plugin;
    $this->expectedCount = count($this->expectedResults);
  }

  /**
   * Tests that the source returns the same rows as expected.
   */
  public function testRetrieval() {
    $this->assertInstanceOf(SelectInterface::class, $this->source->query());
    $this->queryResultTest($this->source, $this->expectedResults);
  }

  /**
   * Tests that the source returns the row count expected.
   */
  public function testSourceCount() {
    $count = $this->source->count();
    $this->assertTrue(is_numeric($count));
    $this->assertEquals($this->expectedCount, $count);
  }

  /**
   * Tests the source defines a valid ID.
   */
  public function testSourceId() {
    $this->assertNotEmpty($this->source->getIds());
  }

  /**
   * Gets the value on a row for a given key.
   *
   * @param \Drupal\migrate\Row $row
   *   The row identifier.
   * @param string $key
   *   The key identifier.
   *
   * @return mixed
   *   The value on a row for a given key.
   */
  protected function getValue($row, $key) {
    return $row->getSourceProperty($key);
  }

}
