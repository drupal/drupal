<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit;

use Drupal\Component\Utility\Html;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\migrate\MigrateExecutable
 * @group migrate
 */
class MigrateExecutableTest extends MigrateTestCase {

  /**
   * Stores ID map records of the ID map plugin from ::getTestRollbackIdMap.
   *
   * @var string[][]
   */
  protected static $idMapRecords;

  /**
   * The mocked migration entity.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $migration;

  /**
   * The mocked migrate message.
   *
   * @var \Drupal\migrate\MigrateMessageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $message;

  /**
   * The tested migrate executable.
   *
   * @var \Drupal\Tests\migrate\Unit\TestMigrateExecutable
   */
  protected $executable;

  /**
   * A mocked event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventDispatcher;

  /**
   * The migration's configuration values.
   *
   * @var array
   */
  protected $migrationConfiguration = [
    'id' => 'test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    static::$idMapRecords = [];
    $this->migration = $this->getMigration();
    $this->message = $this->createMock('Drupal\migrate\MigrateMessageInterface');
    $this->eventDispatcher = $this->createMock('Symfony\Contracts\EventDispatcher\EventDispatcherInterface');
    $this->executable = new TestMigrateExecutable($this->migration, $this->message, $this->eventDispatcher);
    $this->executable->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Tests an import with an incomplete rewinding.
   */
  public function testImportWithFailingRewind() {
    $exception_message = $this->getRandomGenerator()->string();
    $source = $this->createMock('Drupal\migrate\Plugin\MigrateSourceInterface');
    $source->expects($this->once())
      ->method('rewind')
      ->will($this->throwException(new \Exception($exception_message)));
    // The exception message contains the line number where it is thrown. Save
    // it for the testing the exception message.
    $line = (__LINE__) - 3;

    $this->migration->expects($this->any())
      ->method('getSourcePlugin')
      ->willReturn($source);

    // Ensure that a message with the proper message was added.
    $exception_message .= " in " . __FILE__ . " line $line";
    $this->message->expects($this->once())
      ->method('display')
      ->with("Migration failed with source plugin exception: " . Html::escape($exception_message));

    $result = $this->executable->import();
    $this->assertEquals(MigrationInterface::RESULT_FAILED, $result);
  }

  /**
   * Tests the import method with a valid row.
   */
  public function testImportWithValidRow() {
    $source = $this->getMockSource();

    $row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();

    $this->executable->setSource($source);

    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->willReturn([]);

    $destination = $this->createMock('Drupal\migrate\Plugin\MigrateDestinationInterface');

    $this->migration
      ->method('getDestinationPlugin')
      ->willReturn($destination);

    $this->assertSame(MigrationInterface::RESULT_COMPLETED, $this->executable->import());
  }

  /**
   * Tests the import method with a valid row.
   */
  public function testImportWithValidRowWithoutDestinationId() {
    $source = $this->getMockSource();

    $row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();

    $this->executable->setSource($source);

    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->willReturn([]);

    $destination = $this->createMock('Drupal\migrate\Plugin\MigrateDestinationInterface');

    $this->migration
      ->method('getDestinationPlugin')
      ->willReturn($destination);

    $this->idMap->expects($this->never())
      ->method('saveIdMapping');

    $this->assertSame(MigrationInterface::RESULT_COMPLETED, $this->executable->import());
  }

  /**
   * Tests the import method with a valid row.
   */
  public function testImportWithValidRowNoDestinationValues() {
    $source = $this->getMockSource();

    $row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();

    $this->executable->setSource($source);

    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->willReturn([]);

    $destination = $this->createMock('Drupal\migrate\Plugin\MigrateDestinationInterface');

    $this->migration
      ->method('getDestinationPlugin')
      ->willReturn($destination);

    $this->assertSame(MigrationInterface::RESULT_COMPLETED, $this->executable->import());
  }

  /**
   * Tests the import method with a thrown MigrateException.
   *
   * The MigrationException in this case is being thrown from the destination.
   */
  public function testImportWithValidRowWithDestinationMigrateException() {
    $exception_message = $this->getRandomGenerator()->string();
    $source = $this->getMockSource();

    $row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();

    $this->executable->setSource($source);

    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->willReturn([]);

    $destination = $this->createMock('Drupal\migrate\Plugin\MigrateDestinationInterface');

    $this->migration
      ->method('getDestinationPlugin')
      ->willReturn($destination);

    $this->assertSame(MigrationInterface::RESULT_COMPLETED, $this->executable->import());
  }

  /**
   * Tests the import method with a thrown MigrateException.
   *
   * The MigrationException in this case is being thrown from a process plugin.
   */
  public function testImportWithValidRowWithProcesMigrateException() {
    $exception_message = $this->getRandomGenerator()->string();
    $source = $this->getMockSource();

    $row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();

    $row->expects($this->once())
      ->method('getSourceIdValues')
      ->willReturn(['id' => 'test']);

    $source->expects($this->once())
      ->method('current')
      ->willReturn($row);

    $this->executable->setSource($source);

    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->willThrowException(new MigrateException($exception_message));

    $destination = $this->createMock('Drupal\migrate\Plugin\MigrateDestinationInterface');
    $destination->expects($this->never())
      ->method('import');

    $this->migration
      ->method('getDestinationPlugin')
      ->willReturn($destination);

    $this->idMap->expects($this->once())
      ->method('saveIdMapping')
      ->with($row, [], MigrateIdMapInterface::STATUS_FAILED, NULL);

    $this->idMap->expects($this->once())
      ->method('saveMessage');

    $this->idMap->expects($this->never())
      ->method('lookupDestinationIds');

    $this->assertSame(MigrationInterface::RESULT_COMPLETED, $this->executable->import());
  }

  /**
   * Tests the import method with a regular Exception being thrown.
   */
  public function testImportWithValidRowWithException() {
    $exception_message = $this->getRandomGenerator()->string();
    $source = $this->getMockSource();

    $row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();

    $this->executable->setSource($source);

    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->willReturn([]);

    $destination = $this->createMock('Drupal\migrate\Plugin\MigrateDestinationInterface');

    $this->migration
      ->method('getDestinationPlugin')
      ->willReturn($destination);

    $this->assertSame(MigrationInterface::RESULT_COMPLETED, $this->executable->import());
  }

  /**
   * Tests the processRow method.
   */
  public function testProcessRow() {
    $expected = [
      'test' => 'test destination',
      'test1' => 'test1 destination',
    ];
    foreach ($expected as $key => $value) {
      $plugins[$key][0] = $this->createMock('Drupal\migrate\Plugin\MigrateProcessInterface');
      $plugins[$key][0]->expects($this->once())
        ->method('getPluginDefinition')
        ->willReturn([]);
      $plugins[$key][0]->expects($this->once())
        ->method('transform')
        ->willReturn($value);
    }
    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->with(NULL)
      ->willReturn($plugins);
    $row = new Row();
    $this->executable->processRow($row);
    foreach ($expected as $key => $value) {
      $this->assertSame($row->getDestinationProperty($key), $value);
    }
    $this->assertSameSize($expected, $row->getDestination());
  }

  /**
   * Tests the processRow method with an empty pipeline.
   */
  public function testProcessRowEmptyPipeline() {
    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->with(NULL)
      ->willReturn(['test' => []]);
    $row = new Row();
    $this->executable->processRow($row);
    $this->assertSame($row->getDestination(), []);
  }

  /**
   * Tests the processRow pipeline exception.
   */
  public function testProcessRowPipelineException() {
    $row = new Row();
    $plugin = $this->prophesize(MigrateProcessInterface::class);
    $plugin->getPluginDefinition()->willReturn(['handle_multiples' => FALSE]);
    $plugin->transform(NULL, $this->executable, $row, 'destination_id')
      ->willReturn('transform_return_string');
    $plugin->multiple()->willReturn(TRUE);
    $plugin->getPluginId()->willReturn('plugin_id');
    $plugin = $plugin->reveal();
    $plugins['destination_id'] = [$plugin, $plugin];
    $this->migration->method('getProcessPlugins')->willReturn($plugins);

    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('Pipeline failed at plugin_id plugin for destination destination_id: transform_return_string received instead of an array,');
    $this->executable->processRow($row);
  }

  /**
   * Tests the processRow method.
   */
  public function testProcessRowEmptyDestination() {
    $expected = [
      'test' => 'test destination',
      'test1' => 'test1 destination',
      'test2' => NULL,
    ];
    $row = new Row();
    $plugins = [];
    foreach ($expected as $key => $value) {
      $plugin = $this->prophesize(MigrateProcessInterface::class);
      $plugin->getPluginDefinition()->willReturn([]);
      $plugin->transform(NULL, $this->executable, $row, $key)->willReturn($value);
      $plugin->multiple()->willReturn(TRUE);
      $plugins[$key][0] = $plugin->reveal();
    }
    $this->migration->method('getProcessPlugins')->willReturn($plugins);
    $this->executable->processRow($row);
    foreach ($expected as $key => $value) {
      $this->assertSame($value, $row->getDestinationProperty($key));
    }
    $this->assertCount(2, $row->getDestination());
    $this->assertSame(['test2'], $row->getEmptyDestinationProperties());
  }

  /**
   * Returns a mock migration source instance.
   *
   * @return \Drupal\migrate\Plugin\MigrateSourceInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked migration source.
   */
  protected function getMockSource() {
    $this->createMock('\Iterator');

    $class = 'Drupal\migrate\Plugin\migrate\source\SourcePluginBase';
    $source = $this->getMockBuilder($class)
      ->disableOriginalConstructor()
      ->onlyMethods(get_class_methods($class))
      ->getMockForAbstractClass();
    $source->expects($this->once())
      ->method('rewind')
      ->willReturn(TRUE);
    $source->expects($this->any())
      ->method('initializeIterator')
      ->willReturn([]);
    $source->expects($this->any())
      ->method('valid')
      ->will($this->onConsecutiveCalls(TRUE, FALSE));

    return $source;
  }

  /**
   * Tests rollback.
   *
   * @param array[] $id_map_records
   *   The ID map records to test with.
   * @param bool $rollback_called
   *   Sets an expectation that the destination's rollback() will or will not be
   *   called.
   * @param string[] $source_id_keys
   *   The keys of the source IDs. The provided source ID keys must be defined
   *   in the $id_map_records parameter. Optional, defaults to ['source'].
   * @param string[] $destination_id_keys
   *   The keys of the destination IDs. The provided keys must be defined in the
   *   $id_map_records parameter. Optional, defaults to ['destination'].
   * @param int $expected_result
   *   The expected result of the rollback action. Optional, defaults to
   *   MigrationInterface::RESULT_COMPLETED.
   *
   * @dataProvider providerTestRollback
   *
   * @covers ::rollback
   */
  public function testRollback(array $id_map_records, bool $rollback_called = TRUE, array $source_id_keys = ['source'], array $destination_id_keys = ['destination'], int $expected_result = MigrationInterface::RESULT_COMPLETED) {
    $id_map = $this
      ->getTestRollbackIdMap($id_map_records, $source_id_keys, $destination_id_keys)
      ->reveal();

    $migration = $this->getMigration($id_map);
    $destination = $this->prophesize(MigrateDestinationInterface::class);
    if ($rollback_called) {
      $destination->rollback($id_map->currentDestination())->shouldBeCalled();
    }
    else {
      $destination->rollback()->shouldNotBeCalled();
    }
    $migration
      ->method('getDestinationPlugin')
      ->willReturn($destination->reveal());

    $executable = new TestMigrateExecutable($migration, $this->message, $this->eventDispatcher);

    $this->assertEquals($expected_result, $executable->rollback());
  }

  /**
   * Data provider for ::testRollback.
   *
   * @return array
   *   The test cases.
   */
  public function providerTestRollback() {
    return [
      'Rollback delete' => [
        'ID map records' => [
          [
            'source' => '1',
            'destination' => '1',
            'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
          ],
        ],
      ],
      'Rollback preserve' => [
        'ID map records' => [
          [
            'source' => '1',
            'destination' => '1',
            'rollback_action' => MigrateIdMapInterface::ROLLBACK_PRESERVE,
          ],
        ],
        'Rollback called' => FALSE,
      ],
      'Rolling back a failed row' => [
        'ID map records' => [
          [
            'source' => '1',
            'destination' => NULL,
            'source_row_status' => '2',
            'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
          ],
        ],
        'Rollback called' => FALSE,
      ],
      'Rolling back with ID map having records with duplicated destination ID' => [
        'ID map records' => [
          [
            'source_1' => '1',
            'source_2' => '1',
            'destination' => '1',
            'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
          ],
          [
            'source_1' => '2',
            'source_2' => '2',
            'destination' => '2',
            'rollback_action' => MigrateIdMapInterface::ROLLBACK_PRESERVE,
          ],
          [
            'source_1' => '3',
            'source_2' => '3',
            'destination' => '1',
            'rollback_action' => MigrateIdMapInterface::ROLLBACK_DELETE,
          ],
        ],
        'Rollback called' => TRUE,
        'Source ID keys' => ['source_1', 'source_2'],
      ],
      'Rollback NULL' => [
        'ID map records' => [
          [
            'source' => '1',
            'destination' => '1',
            'rollback_action' => NULL,
          ],
        ],
      ],
      'Rollback missing' => [
        'ID map records' => [
          [
            'source' => '1',
            'destination' => '1',
          ],
        ],
      ],
    ];
  }

  /**
   * Returns an ID map object prophecy used in ::testRollback.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy
   *   An ID map object prophecy.
   */
  public function getTestRollbackIdMap(array $items, array $source_id_keys, array $destination_id_keys) {
    static::$idMapRecords = array_map(function (array $item) {
      return $item + [
        'source_row_status' => '0',
        'rollback_action' => '0',
        'last_imported' => '0',
        'hash' => '',
      ];
    }, $items);
    $array_iterator = new \ArrayIterator(static::$idMapRecords);

    $id_map = $this->prophesize(MigrateIdMapInterface::class);
    $id_map->setMessage(Argument::cetera())->willReturn(NULL);
    $id_map->rewind()->will(function () use ($array_iterator) {
      $array_iterator->rewind();
    });
    $id_map->valid()->will(function () use ($array_iterator) {
      return $array_iterator->valid();
    });
    $id_map->next()->will(function () use ($array_iterator) {
      $array_iterator->next();
    });
    $id_map->currentDestination()->will(function () use ($array_iterator, $destination_id_keys) {
      $current = $array_iterator->current();
      $destination_values = array_filter($current, function ($key) use ($destination_id_keys) {
        return in_array($key, $destination_id_keys, TRUE);
      }, ARRAY_FILTER_USE_KEY);
      return empty(array_filter($destination_values, 'is_null'))
        ? array_combine($destination_id_keys, array_values($destination_values))
        : NULL;
    });
    $id_map->currentSource()->will(function () use ($array_iterator, $source_id_keys) {
      $current = $array_iterator->current();
      $source_values = array_filter($current, function ($key) use ($source_id_keys) {
        return in_array($key, $source_id_keys, TRUE);
      }, ARRAY_FILTER_USE_KEY);
      return empty(array_filter($source_values, 'is_null'))
        ? array_combine($source_id_keys, array_values($source_values))
        : NULL;
    });
    $id_map->getRowByDestination(Argument::type('array'))->will(function () {
      $destination_ids = func_get_args()[0][0];
      $return = array_reduce(self::$idMapRecords, function (array $carry, array $record) use ($destination_ids) {
        if (array_merge($record, $destination_ids) === $record) {
          $carry = $record;
        }
        return $carry;
      }, []);

      return $return;
    });
    $id_map->deleteDestination(Argument::type('array'))->will(function () {
      $destination_ids = func_get_args()[0][0];
      $matching_records = array_filter(self::$idMapRecords, function (array $record) use ($destination_ids) {
        return array_merge($record, $destination_ids) === $record;
      });
      foreach (array_keys($matching_records) as $record_key) {
        unset(self::$idMapRecords[$record_key]);
      }
    });
    $id_map->delete(Argument::type('array'))->will(function () {
      $source_ids = func_get_args()[0][0];
      $matching_records = array_filter(self::$idMapRecords, function (array $record) use ($source_ids) {
        return array_merge($record, $source_ids) === $record;
      });
      foreach (array_keys($matching_records) as $record_key) {
        unset(self::$idMapRecords[$record_key]);
      }
    });

    return $id_map;
  }

}
