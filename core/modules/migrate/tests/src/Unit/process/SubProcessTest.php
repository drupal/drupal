<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\migrate\process\Get;
use Drupal\migrate\Plugin\migrate\process\SubProcess;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Unit\MigrateTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   *
   * @dataProvider providerTestSubProcess
   */
  public function testSubProcess($process_configuration, $source_values = []) {
    $migration = $this->getMigration($process_configuration);
    // Set up the properties for the sub_process.
    $plugin = new SubProcess($process_configuration, 'sub_process', []);
    // Manually create the plugins. Migration::getProcessPlugins does this
    // normally but the plugin system is not available.
    foreach ($process_configuration['process'] as $destination => $source) {
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
    $event_dispatcher = $this->getMock(EventDispatcherInterface::class);
    $migrate_executable = new MigrateExecutable($migration, $this->getMock(MigrateMessageInterface::class), $event_dispatcher);

    // The current value of the pipeline.
    $current_value = [
      [
        'source_foo' => 'test',
        'source_id' => 42,
      ] + $source_values,
    ];
    // This is not used but the interface requires it, so create an empty row.
    $row = new Row($source_values);

    // After transformation, check to make sure that source_foo and source_id's
    // values ended up in the proper destinations, and that the value of the
    // key (@id) is the same as the destination ID (42).
    $new_value = $plugin->transform($current_value, $migrate_executable, $row, 'test');
    $this->assertCount(1, $new_value);
    $this->assertCount(count($process_configuration['process']), $new_value[42]);
    $this->assertSame('test', $new_value[42]['foo']);
    if ($source_values) {
      $this->assertSame('source_baz', $new_value[42]['baaa']);
    }
    $this->assertSame(42, $new_value[42]['id']);
  }

  /**
   * Data provider for testSubProcess().
   */
  public function providerTestSubProcess() {
    return [
      'no source context' => [
        'process configuration' => [
          'process' => [
            'foo' => 'source_foo',
            'id' => 'source_id',
          ],
          'key' => '@id',
        ],
      ],
      'default source key' => [
        'process configuration' => [
          'process' => [
            'foo' => 'source_foo',
            'id' => 'source_id',
            'baaa' => 'source/baf',
          ],
          'key' => '@id',
          'include_source' => TRUE,
        ],
        'source values' => [
          'baf' => 'source_baz',
        ],
      ],
      'renamed source key' => [
        'process configuration' => [
          'process' => [
            'foo' => 'source_foo',
            'id' => 'source_id',
            'baaa' => 'my_source/baf',
          ],
          'key' => '@id',
          'include_source' => TRUE,
          'source_key' => 'my_source',
        ],
        'source values' => [
          'baf' => 'source_baz',
        ],
      ],
    ];
  }

}
