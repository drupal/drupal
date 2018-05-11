<?php

namespace Drupal\Tests\migrate\Unit;

use Drupal\Component\Utility\Html;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;

/**
 * @coversDefaultClass \Drupal\migrate\MigrateExecutable
 * @group migrate
 */
class MigrateExecutableTest extends MigrateTestCase {

  /**
   * The mocked migration entity.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $migration;

  /**
   * The mocked migrate message.
   *
   * @var \Drupal\migrate\MigrateMessageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $message;

  /**
   * The tested migrate executable.
   *
   * @var \Drupal\Tests\migrate\Unit\TestMigrateExecutable
   */
  protected $executable;

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
  protected function setUp() {
    parent::setUp();
    $this->migration = $this->getMigration();
    $this->message = $this->getMock('Drupal\migrate\MigrateMessageInterface');
    $event_dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $this->executable = new TestMigrateExecutable($this->migration, $this->message, $event_dispatcher);
    $this->executable->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Tests an import with an incomplete rewinding.
   */
  public function testImportWithFailingRewind() {
    $exception_message = $this->getRandomGenerator()->string();
    $source = $this->getMock('Drupal\migrate\Plugin\MigrateSourceInterface');
    $source->expects($this->once())
      ->method('rewind')
      ->will($this->throwException(new \Exception($exception_message)));

    $this->migration->expects($this->any())
      ->method('getSourcePlugin')
      ->will($this->returnValue($source));

    // Ensure that a message with the proper message was added.
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

    $row->expects($this->once())
      ->method('getSourceIdValues')
      ->will($this->returnValue(['id' => 'test']));

    $this->idMap->expects($this->once())
      ->method('lookupDestinationIds')
      ->with(['id' => 'test'])
      ->will($this->returnValue([['test']]));

    $source->expects($this->once())
      ->method('current')
      ->will($this->returnValue($row));

    $this->executable->setSource($source);

    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->will($this->returnValue([]));

    $destination = $this->getMock('Drupal\migrate\Plugin\MigrateDestinationInterface');
    $destination->expects($this->once())
      ->method('import')
      ->with($row, ['test'])
      ->will($this->returnValue(['id' => 'test']));

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

    $row->expects($this->once())
      ->method('getSourceIdValues')
      ->will($this->returnValue(['id' => 'test']));

    $this->idMap->expects($this->once())
      ->method('lookupDestinationIds')
      ->with(['id' => 'test'])
      ->will($this->returnValue([['test']]));

    $source->expects($this->once())
      ->method('current')
      ->will($this->returnValue($row));

    $this->executable->setSource($source);

    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->will($this->returnValue([]));

    $destination = $this->getMock('Drupal\migrate\Plugin\MigrateDestinationInterface');
    $destination->expects($this->once())
      ->method('import')
      ->with($row, ['test'])
      ->will($this->returnValue(TRUE));

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

    $row->expects($this->once())
      ->method('getSourceIdValues')
      ->will($this->returnValue(['id' => 'test']));

    $source->expects($this->once())
      ->method('current')
      ->will($this->returnValue($row));

    $this->executable->setSource($source);

    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->will($this->returnValue([]));

    $destination = $this->getMock('Drupal\migrate\Plugin\MigrateDestinationInterface');
    $destination->expects($this->once())
      ->method('import')
      ->with($row, ['test'])
      ->will($this->returnValue([]));

    $this->migration
      ->method('getDestinationPlugin')
      ->willReturn($destination);

    $this->idMap->expects($this->once())
      ->method('saveIdMapping')
      ->with($row, [], MigrateIdMapInterface::STATUS_FAILED, NULL);

    $this->idMap->expects($this->once())
      ->method('messageCount')
      ->will($this->returnValue(0));

    $this->idMap->expects($this->once())
      ->method('saveMessage');

    $this->idMap->expects($this->once())
      ->method('lookupDestinationIds')
      ->with(['id' => 'test'])
      ->will($this->returnValue([['test']]));

    $this->message->expects($this->once())
      ->method('display')
      ->with('New object was not saved, no error provided');

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

    $row->expects($this->once())
      ->method('getSourceIdValues')
      ->will($this->returnValue(['id' => 'test']));

    $source->expects($this->once())
      ->method('current')
      ->will($this->returnValue($row));

    $this->executable->setSource($source);

    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->will($this->returnValue([]));

    $destination = $this->getMock('Drupal\migrate\Plugin\MigrateDestinationInterface');
    $destination->expects($this->once())
      ->method('import')
      ->with($row, ['test'])
      ->will($this->throwException(new MigrateException($exception_message)));

    $this->migration
      ->method('getDestinationPlugin')
      ->willReturn($destination);

    $this->idMap->expects($this->once())
      ->method('saveIdMapping')
      ->with($row, [], MigrateIdMapInterface::STATUS_FAILED, NULL);

    $this->idMap->expects($this->once())
      ->method('saveMessage');

    $this->idMap->expects($this->once())
      ->method('lookupDestinationIds')
      ->with(['id' => 'test'])
      ->will($this->returnValue([['test']]));

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

    $destination = $this->getMock('Drupal\migrate\Plugin\MigrateDestinationInterface');
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

    $row->expects($this->once())
      ->method('getSourceIdValues')
      ->will($this->returnValue(['id' => 'test']));

    $source->expects($this->once())
      ->method('current')
      ->will($this->returnValue($row));

    $this->executable->setSource($source);

    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->will($this->returnValue([]));

    $destination = $this->getMock('Drupal\migrate\Plugin\MigrateDestinationInterface');
    $destination->expects($this->once())
      ->method('import')
      ->with($row, ['test'])
      ->will($this->throwException(new \Exception($exception_message)));

    $this->migration
      ->method('getDestinationPlugin')
      ->willReturn($destination);

    $this->idMap->expects($this->once())
      ->method('saveIdMapping')
      ->with($row, [], MigrateIdMapInterface::STATUS_FAILED, NULL);

    $this->idMap->expects($this->once())
      ->method('saveMessage');

    $this->idMap->expects($this->once())
      ->method('lookupDestinationIds')
      ->with(['id' => 'test'])
      ->will($this->returnValue([['test']]));

    $this->message->expects($this->once())
      ->method('display')
      ->with($exception_message);

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
      $plugins[$key][0] = $this->getMock('Drupal\migrate\Plugin\MigrateProcessInterface');
      $plugins[$key][0]->expects($this->once())
        ->method('getPluginDefinition')
        ->will($this->returnValue([]));
      $plugins[$key][0]->expects($this->once())
        ->method('transform')
        ->will($this->returnValue($value));
    }
    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->with(NULL)
      ->will($this->returnValue($plugins));
    $row = new Row();
    $this->executable->processRow($row);
    foreach ($expected as $key => $value) {
      $this->assertSame($row->getDestinationProperty($key), $value);
    }
    $this->assertSame(count($row->getDestination()), count($expected));
  }

  /**
   * Tests the processRow method with an empty pipeline.
   */
  public function testProcessRowEmptyPipeline() {
    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->with(NULL)
      ->will($this->returnValue(['test' => []]));
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

    $this->setExpectedException(MigrateException::class, 'Pipeline failed at plugin_id plugin for destination destination_id: transform_return_string received instead of an array,');
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
   * @return \Drupal\migrate\Plugin\MigrateSourceInterface|\PHPUnit_Framework_MockObject_MockObject
   *   The mocked migration source.
   */
  protected function getMockSource() {
    $iterator = $this->getMock('\Iterator');

    $class = 'Drupal\migrate\Plugin\migrate\source\SourcePluginBase';
    $source = $this->getMockBuilder($class)
      ->disableOriginalConstructor()
      ->setMethods(get_class_methods($class))
      ->getMockForAbstractClass();
    $source->expects($this->once())
      ->method('rewind')
      ->will($this->returnValue(TRUE));
    $source->expects($this->any())
      ->method('initializeIterator')
      ->will($this->returnValue([]));
    $source->expects($this->any())
      ->method('valid')
      ->will($this->onConsecutiveCalls(TRUE, FALSE));

    return $source;
  }

}
