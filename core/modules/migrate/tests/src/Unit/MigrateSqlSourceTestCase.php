<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase.
 */

namespace Drupal\Tests\migrate\Unit;

/**
 * Base class for Migrate module source unit tests.
 */
abstract class MigrateSqlSourceTestCase extends MigrateTestCase {

  /**
   * The tested source plugin.
   *
   * @var \Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase.
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
  protected $databaseContents = array();

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
   * @var string
   */
  const ORIGINAL_HIGH_WATER = '';

  /**
   * Expected results after the source parsing.
   *
   * @var array
   */
  protected $expectedResults = array();

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
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    $migration = $this->getMigration();
    $migration->expects($this->any())
      ->method('getHighWater')
      ->will($this->returnValue(static::ORIGINAL_HIGH_WATER));

    // Setup the plugin.
    $plugin_class = static::PLUGIN_CLASS;
    $plugin = new $plugin_class($this->migrationConfiguration['source'], $this->migrationConfiguration['source']['plugin'], array(), $migration, $entity_manager);

    // Do some reflection to set the database and moduleHandler.
    $plugin_reflection = new \ReflectionClass($plugin);
    $database_property = $plugin_reflection->getProperty('database');
    $database_property->setAccessible(TRUE);
    $module_handler_property = $plugin_reflection->getProperty('moduleHandler');
    $module_handler_property->setAccessible(TRUE);

    // Set the database and the module handler onto our plugin.
    $database_property->setValue($plugin, $this->getDatabase($this->databaseContents + array('test_map' => array())));
    $module_handler_property->setValue($plugin, $module_handler);

    $plugin->setStringTranslation($this->getStringTranslationStub());
    $migration->expects($this->any())
      ->method('getSourcePlugin')
      ->will($this->returnValue($plugin));
    $this->source = $plugin;
  }

  /**
   * Test the source returns the same rows as expected.
   */
  public function testRetrieval() {
    $this->queryResultTest($this->source, $this->expectedResults);
  }

  /**
   * @param \Drupal\migrate\Row $row
   * @param string $key
   * @return mixed
   */
  protected function getValue($row, $key) {
    return $row->getSourceProperty($key);
  }

}
