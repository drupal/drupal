<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\migrate\process\Get;
use Drupal\migrate\Plugin\migrate\process\SubProcess;
use Drupal\migrate\Row;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

// cspell:ignore baaa

/**
 * Tests the sub_process process plugin.
 *
 * @group migrate
 */
class SubProcessTest extends MigrateProcessTestCase {

  /**
   * The sub_process plugin being tested.
   *
   * @var \Drupal\migrate\Plugin\migrate\process\SubProcess
   */
  protected $plugin;

  /**
   * The default migration configuration.
   *
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
  public function testSubProcess($process_configuration, $source_values = []): void {
    $migration = $this->getMigration();
    // Set up the properties for the sub_process.
    $plugin = new SubProcess($process_configuration, 'sub_process', []);
    // Manually create the plugins. Migration::getProcessPlugins does this
    // normally but the plugin system is not available.
    foreach ($process_configuration['process'] as $destination => $source) {
      $sub_process_plugins[$destination][] = new Get(['source' => $source], 'get', []);
    }
    // Set up the key plugins.
    $key_plugin['key'][] = new Get(['source' => '@id'], 'get', []);
    $migration->expects($this->exactly(2))
      ->method('getProcessPlugins')
      ->willReturnOnConsecutiveCalls(
        $sub_process_plugins,
        $key_plugin,
      );
    $event_dispatcher = $this->createMock(EventDispatcherInterface::class);
    $migrate_executable = new MigrateExecutable($migration, $this->createMock(MigrateMessageInterface::class), $event_dispatcher);

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
    $this->assertSameSize($process_configuration['process'], $new_value[42]);
    $this->assertSame('test', $new_value[42]['foo']);
    if ($source_values) {
      $this->assertSame('source_baz', $new_value[42]['baaa']);
    }
    $this->assertSame(42, $new_value[42]['id']);
  }

  /**
   * Data provider for testSubProcess().
   */
  public static function providerTestSubProcess() {
    return [
      'no source context' => [
        'process_configuration' => [
          'process' => [
            'foo' => 'source_foo',
            'id' => 'source_id',
          ],
          'key' => '@id',
        ],
      ],
      'default source key' => [
        'process_configuration' => [
          'process' => [
            'foo' => 'source_foo',
            'id' => 'source_id',
            'baaa' => 'source/baf',
          ],
          'key' => '@id',
          'include_source' => TRUE,
        ],
        'source_values' => [
          'baf' => 'source_baz',
        ],
      ],
      'renamed source key' => [
        'process_configuration' => [
          'process' => [
            'foo' => 'source_foo',
            'id' => 'source_id',
            'baaa' => 'my_source/baf',
          ],
          'key' => '@id',
          'include_source' => TRUE,
          'source_key' => 'my_source',
        ],
        'source_values' => [
          'baf' => 'source_baz',
        ],
      ],
    ];
  }

  /**
   * Tests the sub_process process plugin.
   *
   * @dataProvider providerTestNotFoundSubProcess
   */
  public function testNotFoundSubProcess($process_configuration, $source_values = []): void {
    $migration = $this->getMigration();
    // Set up the properties for the sub_process.
    $plugin = new SubProcess($process_configuration, 'sub_process', []);
    // Manually create the plugins. Migration::getProcessPlugins does this
    // normally but the plugin system is not available.
    foreach ($process_configuration['process'] as $destination => $source) {
      $sub_process_plugins[$destination][] = new Get(['source' => $source], 'get', []);
    }
    $key_plugin['key'][] = new Get(['source' => '@id'], 'get', []);
    $migration->expects($this->exactly(2))
      ->method('getProcessPlugins')
      ->willReturnOnConsecutiveCalls(
        $sub_process_plugins,
        $key_plugin,
      );
    $event_dispatcher = $this->createMock(EventDispatcherInterface::class);
    $migrate_executable = new MigrateExecutable($migration, $this->createMock(MigrateMessageInterface::class), $event_dispatcher);

    // The current value of the pipeline.
    $current_value = [
      [
        'source_foo' => 'test',
        'source_id' => NULL,
      ] + $source_values,
    ];
    // This is not used but the interface requires it, so create an empty row.
    $row = new Row($source_values);

    // After transformation, check to make sure that source_foo and source_id's
    // values ended up in the proper destinations, and that the value of the
    // key (@id) is the same as the destination ID (42).
    $new_value = $plugin->transform($current_value, $migrate_executable, $row, 'test');
    $this->assertSame([], $new_value);
  }

  /**
   * Data provider for testNotFoundSubProcess().
   */
  public static function providerTestNotFoundSubProcess() {
    return [
      'no key' => [
        'process_configuration' => [
          'process' => [
            'foo' => 'source_foo',
          ],
          'key' => '@id',
        ],
      ],
      'lookup returns NULL' => [
        'process_configuration' => [
          'process' => [
            'foo' => 'source_foo',
            'id' => 'source_id',
          ],
          'key' => '@id',
        ],
      ],
    ];
  }

  /**
   * Tests behavior when source children are not arrays.
   *
   * @dataProvider providerTestSourceNotArray
   */
  public function testSourceNotArray($source_values, $type): void {
    $process = new SubProcess(['process' => ['foo' => 'source_foo']], 'sub_process', []);
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage("Input array should hold elements of type array, instead element was of type '$type'");
    $process->transform($source_values, $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Data provider for testSourceNotArray().
   */
  public static function providerTestSourceNotArray() {
    return [
      'strings cannot be subprocess items' => [
        ['strings', 'cannot', 'be', 'children'],
        'string',
      ],
      'xml elements cannot be subprocess items' => [
        [new \SimpleXMLElement("<element>Content</element>")],
        'object',
      ],
      'integers cannot be subprocess items' => [
        [1, 2, 3, 4],
        'integer',
      ],
      'booleans cannot be subprocess items' => [
        [TRUE, FALSE],
        'boolean',
      ],
      'null cannot be subprocess items' => [
        [NULL],
        'NULL',
      ],
      'iterator cannot be subprocess items' => [
        [new \ArrayIterator(['some', 'array'])],
        'object',
      ],
      'all subprocess items must be arrays' => [
        [['array'], 'not array'],
        'string',
      ],
    ];
  }

}
