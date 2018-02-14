<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\migrate\process\Get;
use Drupal\migrate\Plugin\migrate\process\SubProcess;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Unit\MigrateTestCase;

/**
 * Tests the sub_process process plugin.
 *
 * @group migrate
 */
class SubProcessTest extends MigrateTestCase {

  /**
   * The sub_process plugin being tested.
   *
   * @var \Drupal\migrate\Plugin\migrate\process\SubProcess
   */
  protected $plugin;

  /**
   * @var array
   */
  protected $migrationConfiguration = [
    'id' => 'test',
  ];

  /**
   * Tests the sub_process process plugin.
   */
  public function testSubProcess() {
    $migration = $this->getMigration();
    // Set up the properties for the sub_process.
    $configuration = [
      'process' => [
        'foo' => 'source_foo',
        'id' => 'source_id',
      ],
      'key' => '@id',
    ];
    $plugin = new SubProcess($configuration, 'sub_process', []);
    // Manually create the plugins. Migration::getProcessPlugins does this
    // normally but the plugin system is not available.
    foreach ($configuration['process'] as $destination => $source) {
      $sub_process_plugins[$destination][] = new Get(['source' => $source], 'get', []);
    }
    $migration->expects($this->at(1))
      ->method('getProcessPlugins')
      ->willReturn($sub_process_plugins);
    // Set up the key plugins.
    $key_plugin['key'][] = new Get(['source' => '@id'], 'get', []);
    $migration->expects($this->at(2))
      ->method('getProcessPlugins')
      ->will($this->returnValue($key_plugin));
    $event_dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $migrate_executable = new MigrateExecutable($migration, $this->getMock('Drupal\migrate\MigrateMessageInterface'), $event_dispatcher);

    // The current value of the pipeline.
    $current_value = [
      [
        'source_foo' => 'test',
        'source_id' => 42,
      ],
    ];
    // This is not used but the interface requires it, so create an empty row.
    $row = new Row();

    // After transformation, check to make sure that source_foo and source_id's
    // values ended up in the proper destinations, and that the value of the
    // key (@id) is the same as the destination ID (42).
    $new_value = $plugin->transform($current_value, $migrate_executable, $row, 'test');
    $this->assertSame(1, count($new_value));
    $this->assertSame(2, count($new_value[42]));
    $this->assertSame('test', $new_value[42]['foo']);
    $this->assertSame(42, $new_value[42]['id']);
  }

}
