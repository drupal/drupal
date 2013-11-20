<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\MigrateSqlSourceTestCase.
 */

namespace Drupal\migrate\Tests;

/**
 * Provides setup and helper methods for Migrate module source tests.
 */
abstract class MigrateSqlSourceTestCase extends MigrateTestCase {

  /**
   * The tested source plugin.
   *
   * @var \Drupal\migrate\Plugin\migrate\source\d6\Comment.
   */
  protected $source;

  protected $databaseContents = array();

  const PLUGIN_CLASS = '';

  const ORIGINAL_HIGHWATER = '';

  protected $expectedResults = array();

  /**
   * @var \Drupal\migrate\Plugin\MigrateSourceInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $module_handler = $this->getMockBuilder('Drupal\Core\Extension\ModuleHandlerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $migration = $this->getMigration();
    $migration->expects($this->any())
      ->method('getHighwater')
      ->will($this->returnValue(static::ORIGINAL_HIGHWATER));
    // Need the test class, not the original because we need a setDatabase method. This is not pretty :/
    $plugin_class  = preg_replace('/^(Drupal\\\\\w+\\\\)Plugin\\\\migrate(\\\\source(\\\\.+)?\\\\)([^\\\\]+)$/', '\1Tests\2Test\4', static::PLUGIN_CLASS);
    $plugin = new $plugin_class($this->migrationConfiguration['source'], $this->migrationConfiguration['source']['plugin'], array(), $migration);
    $plugin->setDatabase($this->getDatabase($this->databaseContents + array('test_map' => array())));
    $plugin->setModuleHandler($module_handler);
    $migration->expects($this->any())
      ->method('getSourcePlugin')
      ->will($this->returnValue($plugin));
    $migrateExecutable = $this->getMockBuilder('Drupal\migrate\MigrateExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $this->source = new TestSource($migration, $migrateExecutable);

    $cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->source->setCache($cache);
  }

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

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'SQL source test',
      'description' => 'Tests for SQL source plugin.',
      'group' => 'Migrate',
    );
  }

}
