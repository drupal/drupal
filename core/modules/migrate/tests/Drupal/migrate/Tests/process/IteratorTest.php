<?php
/**
 * @file
 * Contains Drupal\migrate\Tests\process\IteratorTest.
 */

namespace Drupal\migrate\Tests\process;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\migrate\process\Get;
use Drupal\migrate\Plugin\migrate\process\Iterator;
use Drupal\migrate\Plugin\migrate\process\StaticMap;
use Drupal\migrate\Row;
use Drupal\migrate\Tests\MigrateTestCase;

/**
 * Tests the iterator process plugin.
 *
 * @group migrate
 * @group Drupal
 */
class IteratorTest extends MigrateTestCase {

  /**
   * The iterator plugin being tested.
   *
   * @var \Drupal\migrate\Plugin\migrate\process\TestIterator
   */
  protected $plugin;

  /**
   * @var array
   */
  protected $migrationConfiguration = array(
    'id' => 'test',
  );

  /**
   * @var bool
   */
  protected $mapJoinable = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Iterator process plugin',
      'description' => 'Tests the iterator process plugin.',
      'group' => 'Migrate',
    );
  }

  /**
   * Tests the iterator process plugin.
   */
  public function testIterator() {
    $migration = $this->getMigration();
    // Set up the properties for the iterator.
    $configuration = array(
      'process' => array(
        'foo' => 'source_foo',
        'id' => 'source_id',
      ),
      'key' => '@id',
    );
    $plugin = new Iterator($configuration, 'iterator', array());
    // Manually create the plugins. Migration::getProcessPlugins does this
    // normally but the plugin system is not available.
    foreach ($configuration['process'] as $destination => $source) {
      $iterator_plugins[$destination][] = new Get(array('source' => $source), 'get', array());
    }
    $migration->expects($this->at(1))
      ->method('getProcessPlugins')
      ->will($this->returnValue($iterator_plugins));
    // Set up the key plugins.
    $key_plugin['key'][] = new Get(array('source' => '@id'), 'get', array());
    $migration->expects($this->at(2))
      ->method('getProcessPlugins')
      ->will($this->returnValue($key_plugin));
    $migrate_executable = new MigrateExecutable($migration, $this->getMock('Drupal\migrate\MigrateMessageInterface'));

    // The current value of the pipeline.
    $current_value = array(
      array(
        'source_foo' => 'test',
        'source_id' => 42,
      ),
    );
    // This is not used but the interface requires it, so create an empty row.
    $row = new Row(array(), array());

    // After transformation, check to make sure that source_foo and source_id's
    // values ended up in the proper destinations, and that the value of the
    // key (@id) is the same as the destination ID (42).
    $new_value = $plugin->transform($current_value, $migrate_executable, $row, 'test');
    $this->assertSame(count($new_value), 1);
    $this->assertSame(count($new_value[42]), 2);
    $this->assertSame($new_value[42]['foo'], 'test');
    $this->assertSame($new_value[42]['id'], 42);
  }
}
