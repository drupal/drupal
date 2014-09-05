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

    $this->executable = new TestMigrateExecutable($this->migration, $this->message);
    $this->executable->setStringTranslation($this->getStringTranslationStub());
    $this->executable->setTimeThreshold(0.1);
    $this->executable->limit = array('unit' => 'second', 'value' => 1);
  }

  /**
   * Tests an import with an incomplete rewinding.
   */
  public function testImportWithFailingRewind() {
    $iterator = $this->getMock('\Iterator');
    $exception_message = $this->getRandomGenerator()->string();
    $iterator->expects($this->once())
      ->method('rewind')
      ->will($this->throwException(new \Exception($exception_message)));
    $source = $this->getMock('Drupal\migrate\Plugin\MigrateSourceInterface');
    $source->expects($this->any())
      ->method('getIterator')
      ->will($this->returnValue($iterator));

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

    $this->assertSame(1, $this->executable->getSuccessesSinceFeedback());
    $this->assertSame(1, $this->executable->getTotalSuccesses());
    $this->assertSame(1, $this->executable->getTotalProcessed());
    $this->assertSame(1, $this->executable->getProcessedSinceFeedback());
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

    $this->assertSame(1, $this->executable->getSuccessesSinceFeedback());
    $this->assertSame(1, $this->executable->getTotalSuccesses());
    $this->assertSame(1, $this->executable->getTotalProcessed());
    $this->assertSame(1, $this->executable->getProcessedSinceFeedback());
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
      ->method('delete')
      ->with(array('id' => 'test'), TRUE);

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
   * Tests the import method with a MigrateException being thrown.
   */
  public function testImportWithValidRowWithMigrateException() {
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

    $this->message->expects($this->once())
      ->method('display')
      ->with($exception_message);

    $this->idMap->expects($this->once())
      ->method('lookupDestinationId')
      ->with(array('id' => 'test'))
      ->will($this->returnValue(array('test')));

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
   * Tests time limit option method.
   */
  public function testTimeOptionExceeded() {
    // Assert time limit of one second (test configuration default) is exceeded.
    $this->executable->setTimeElapsed(1);
    $this->assertTrue($this->executable->timeOptionExceeded());
    // Assert time limit not exceeded.
    $this->executable->limit = array('unit' => 'seconds', 'value' => (REQUEST_TIME - 3600));
    $this->assertFalse($this->executable->timeOptionExceeded());
    // Assert no time limit.
    $this->executable->limit = array();
    $this->assertFalse($this->executable->timeOptionExceeded());
  }

  /**
   * Tests get time limit method.
   */
  public function testGetTimeLimit() {
    // Assert time limit has a unit of one second (test configuration default).
    $limit = $this->executable->limit;
    $this->assertArrayHasKey('unit', $limit);
    $this->assertSame('second', $limit['unit']);
    $this->assertSame($limit['value'], $this->executable->getTimeLimit());
    // Assert time limit has a unit of multiple seconds.
    $this->executable->limit = array('unit' => 'seconds', 'value' => 30);
    $limit = $this->executable->limit;
    $this->assertArrayHasKey('unit', $limit);
    $this->assertSame('seconds', $limit['unit']);
    $this->assertSame($limit['value'], $this->executable->getTimeLimit());
    // Assert no time limit.
    $this->executable->limit = array();
    $limit = $this->executable->limit;
    $this->assertArrayNotHasKey('unit', $limit);
    $this->assertArrayNotHasKey('value', $limit);
    $this->assertNull($this->executable->getTimeLimit());
  }

  /**
   * Tests saving of queued messages.
   */
  public function testSaveQueuedMessages() {
    // Assert no queued messages before save.
    $this->assertAttributeEquals(array(), 'queuedMessages', $this->executable);
    // Set required source_id_values for MigrateIdMapInterface::saveMessage().
    $expected_messages[] = array('message' => 'message 1', 'level' => MigrationInterface::MESSAGE_ERROR);
    $expected_messages[] = array('message' => 'message 2', 'level' => MigrationInterface::MESSAGE_WARNING);
    $expected_messages[] = array('message' => 'message 3', 'level' => MigrationInterface::MESSAGE_INFORMATIONAL);
    foreach ($expected_messages as $queued_message) {
      $this->executable->queueMessage($queued_message['message'], $queued_message['level']);
    }
    $this->executable->setSourceIdValues(array());
    $this->assertAttributeEquals($expected_messages, 'queuedMessages', $this->executable);
    // No asserts of saved messages since coverage exists
    // in MigrateSqlIdMapTest::saveMessage().
    $this->executable->saveQueuedMessages();
    // Assert no queued messages after save.
    $this->assertAttributeEquals(array(), 'queuedMessages', $this->executable);
  }

  /**
   * Tests the queuing of messages.
   */
  public function testQueueMessage() {
    // Assert no queued messages.
    $expected_messages = array();
    $this->assertAttributeEquals(array(), 'queuedMessages', $this->executable);
    // Assert a single (default level) queued message.
    $expected_messages[] = array(
      'message' => 'message 1',
      'level' => MigrationInterface::MESSAGE_ERROR,
    );
    $this->executable->queueMessage('message 1');
    $this->assertAttributeEquals($expected_messages, 'queuedMessages', $this->executable);
    // Assert multiple queued messages.
    $expected_messages[] = array(
      'message' => 'message 2',
      'level' => MigrationInterface::MESSAGE_WARNING,
    );
    $this->executable->queueMessage('message 2', MigrationInterface::MESSAGE_WARNING);
    $this->assertAttributeEquals($expected_messages, 'queuedMessages', $this->executable);
    $expected_messages[] = array(
      'message' => 'message 3',
      'level' => MigrationInterface::MESSAGE_INFORMATIONAL,
    );
    $this->executable->queueMessage('message 3', MigrationInterface::MESSAGE_INFORMATIONAL);
    $this->assertAttributeEquals($expected_messages, 'queuedMessages', $this->executable);
  }

  /**
   * Tests maximum execution time (max_execution_time) of an import.
   */
  public function testMaxExecTimeExceeded() {
    // Assert no max_execution_time value.
    $this->executable->setMaxExecTime(0);
    $this->assertFalse($this->executable->maxExecTimeExceeded());
    // Assert default max_execution_time value does not exceed.
    $this->executable->setMaxExecTime(30);
    $this->assertFalse($this->executable->maxExecTimeExceeded());
    // Assert max_execution_time value is exceeded.
    $this->executable->setMaxExecTime(1);
    $this->executable->setTimeElapsed(2);
    $this->assertTrue($this->executable->maxExecTimeExceeded());
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
   * @return \Drupal\migrate\Source|\PHPUnit_Framework_MockObject_MockObject
   */
  protected function getMockSource() {
    $iterator = $this->getMock('\Iterator');

    $source = $this->getMockBuilder('Drupal\migrate\Source')
      ->disableOriginalConstructor()
      ->getMock();
    $source->expects($this->any())
      ->method('getIterator')
      ->will($this->returnValue($iterator));
    $source->expects($this->once())
      ->method('rewind')
      ->will($this->returnValue(TRUE));
    $source->expects($this->any())
      ->method('valid')
      ->will($this->onConsecutiveCalls(TRUE, FALSE));

    return $source;
  }

}
