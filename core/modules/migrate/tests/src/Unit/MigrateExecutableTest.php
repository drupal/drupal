<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\MigrateExecutableTest.
 */

namespace Drupal\Tests\migrate\Unit;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;

/**
 * @coversDefaultClass \Drupal\Tests\migrate\Unit\MigrateExecutableTest
 * @group migrate
 */
class MigrateExecutableTest extends MigrateTestCase {

  /**
   * The mocked migration entity.
   *
   * @var \Drupal\migrate\Entity\MigrationInterface|\PHPUnit_Framework_MockObject_MockObject
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

  protected $migrationConfiguration = array(
    'id' => 'test',
  );

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
      ->with("Migration failed with source plugin exception: $exception_message");

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
      ->will($this->returnValue(array('id' => 'test')));

    $this->idMap->expects($this->once())
      ->method('lookupDestinationId')
      ->with(array('id' => 'test'))
      ->will($this->returnValue(array('test')));

    $source->expects($this->once())
      ->method('current')
      ->will($this->returnValue($row));

    $this->executable->setSource($source);

    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->will($this->returnValue(array()));

    $destination = $this->getMock('Drupal\migrate\Plugin\MigrateDestinationInterface');
    $destination->expects($this->once())
      ->method('import')
      ->with($row, array('test'))
      ->will($this->returnValue(array('id' => 'test')));

    $this->migration->expects($this->once())
      ->method('getDestinationPlugin')
      ->will($this->returnValue($destination));

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
      ->will($this->returnValue(array('id' => 'test')));

    $this->idMap->expects($this->once())
      ->method('lookupDestinationId')
      ->with(array('id' => 'test'))
      ->will($this->returnValue(array('test')));

    $source->expects($this->once())
      ->method('current')
      ->will($this->returnValue($row));

    $this->executable->setSource($source);

    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->will($this->returnValue(array()));

    $destination = $this->getMock('Drupal\migrate\Plugin\MigrateDestinationInterface');
    $destination->expects($this->once())
      ->method('import')
      ->with($row, array('test'))
      ->will($this->returnValue(TRUE));

    $this->migration->expects($this->once())
      ->method('getDestinationPlugin')
      ->will($this->returnValue($destination));

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
      ->will($this->returnValue(array('id' => 'test')));

    $source->expects($this->once())
      ->method('current')
      ->will($this->returnValue($row));

    $this->executable->setSource($source);

    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->will($this->returnValue(array()));

    $destination = $this->getMock('Drupal\migrate\Plugin\MigrateDestinationInterface');
    $destination->expects($this->once())
      ->method('import')
      ->with($row, array('test'))
      ->will($this->returnValue(array()));

    $this->migration->expects($this->once())
      ->method('getDestinationPlugin')
      ->will($this->returnValue($destination));

    $this->idMap->expects($this->once())
      ->method('saveIdMapping')
      ->with($row, array(), MigrateIdMapInterface::STATUS_FAILED, NULL);

    $this->idMap->expects($this->once())
      ->method('messageCount')
      ->will($this->returnValue(0));

    $this->idMap->expects($this->once())
      ->method('saveMessage');

    $this->idMap->expects($this->once())
      ->method('lookupDestinationId')
      ->with(array('id' => 'test'))
      ->will($this->returnValue(array('test')));

    $this->message->expects($this->once())
      ->method('display')
      ->with('New object was not saved, no error provided');

    $this->assertSame(MigrationInterface::RESULT_COMPLETED, $this->executable->import());
  }

  /**
   * Tests the import method with a MigrateException being thrown from the
   * destination.
   */
  public function testImportWithValidRowWithDestinationMigrateException() {
    $exception_message = $this->getRandomGenerator()->string();
    $source = $this->getMockSource();

    $row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();

    $row->expects($this->once())
      ->method('getSourceIdValues')
      ->will($this->returnValue(array('id' => 'test')));

    $source->expects($this->once())
      ->method('current')
      ->will($this->returnValue($row));

    $this->executable->setSource($source);

    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->will($this->returnValue(array()));

    $destination = $this->getMock('Drupal\migrate\Plugin\MigrateDestinationInterface');
    $destination->expects($this->once())
      ->method('import')
      ->with($row, array('test'))
      ->will($this->throwException(new MigrateException($exception_message)));

    $this->migration->expects($this->once())
      ->method('getDestinationPlugin')
      ->will($this->returnValue($destination));

    $this->idMap->expects($this->once())
      ->method('saveIdMapping')
      ->with($row, array(), MigrateIdMapInterface::STATUS_FAILED, NULL);

    $this->idMap->expects($this->once())
      ->method('saveMessage');

    $this->idMap->expects($this->once())
      ->method('lookupDestinationId')
      ->with(array('id' => 'test'))
      ->will($this->returnValue(array('test')));

    $this->assertSame(MigrationInterface::RESULT_COMPLETED, $this->executable->import());
  }

  /**
   * Tests the import method with a MigrateException being thrown from a process
   * plugin.
   */
  public function testImportWithValidRowWithProcesMigrateException() {
    $exception_message = $this->getRandomGenerator()->string();
    $source = $this->getMockSource();

    $row = $this->getMockBuilder('Drupal\migrate\Row')
      ->disableOriginalConstructor()
      ->getMock();

    $row->expects($this->once())
      ->method('getSourceIdValues')
      ->willReturn(array('id' => 'test'));

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

    $this->migration->expects($this->once())
      ->method('getDestinationPlugin')
      ->willReturn($destination);

    $this->idMap->expects($this->once())
      ->method('saveIdMapping')
      ->with($row, array(), MigrateIdMapInterface::STATUS_FAILED, NULL);

    $this->idMap->expects($this->once())
      ->method('saveMessage');

    $this->idMap->expects($this->never())
      ->method('lookupDestinationId');

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
      ->will($this->returnValue(array('id' => 'test')));

    $source->expects($this->once())
      ->method('current')
      ->will($this->returnValue($row));

    $this->executable->setSource($source);

    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->will($this->returnValue(array()));

    $destination = $this->getMock('Drupal\migrate\Plugin\MigrateDestinationInterface');
    $destination->expects($this->once())
      ->method('import')
      ->with($row, array('test'))
      ->will($this->throwException(new \Exception($exception_message)));

    $this->migration->expects($this->once())
      ->method('getDestinationPlugin')
      ->will($this->returnValue($destination));

    $this->idMap->expects($this->once())
      ->method('saveIdMapping')
      ->with($row, array(), MigrateIdMapInterface::STATUS_FAILED, NULL);

    $this->idMap->expects($this->once())
      ->method('saveMessage');

    $this->idMap->expects($this->once())
      ->method('lookupDestinationId')
      ->with(array('id' => 'test'))
      ->will($this->returnValue(array('test')));

    $this->message->expects($this->once())
      ->method('display')
      ->with($exception_message);

    $this->assertSame(MigrationInterface::RESULT_COMPLETED, $this->executable->import());
  }

  /**
   * Tests the processRow method.
   */
  public function testProcessRow() {
    $expected = array(
      'test' => 'test destination',
      'test1' => 'test1 destination'
    );
    foreach ($expected as $key => $value) {
      $plugins[$key][0] = $this->getMock('Drupal\migrate\Plugin\MigrateProcessInterface');
      $plugins[$key][0]->expects($this->once())
        ->method('getPluginDefinition')
        ->will($this->returnValue(array()));
      $plugins[$key][0]->expects($this->once())
        ->method('transform')
        ->will($this->returnValue($value));
    }
    $this->migration->expects($this->once())
      ->method('getProcessPlugins')
      ->with(NULL)
      ->will($this->returnValue($plugins));
    $row = new Row(array(), array());
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
      ->will($this->returnValue(array('test' => array())));
    $row = new Row(array(), array());
    $this->executable->processRow($row);
    $this->assertSame($row->getDestination(), array());
  }

  /**
   * Returns a mock migration source instance.
   *
   * @return \Drupal\migrate\Plugin\MigrateSourceInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function getMockSource() {
    $iterator = $this->getMock('\Iterator');

    $class = 'Drupal\migrate\Plugin\migrate\source\SourcePluginBase';
    $source = $this->getMockBuilder($class)
      ->disableOriginalConstructor()
      ->setMethods(get_class_methods($class))
      ->getMockForAbstractClass();
    $source->expects($this->any())
      ->method('getIterator')
      ->will($this->returnValue($iterator));
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
