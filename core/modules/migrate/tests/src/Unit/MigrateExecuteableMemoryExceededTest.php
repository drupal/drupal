<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\MigrateExecuteableMemoryExceeded.
 */

namespace Drupal\Tests\migrate\Unit;

/**
 * Tests the \Drupal\migrate\MigrateExecutable::memoryExceeded() method.
 *
 * @group migrate
 */
class MigrateExecuteableMemoryExceededTest extends MigrateTestCase {

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

  /**
   * The migration configuration, initialized to set the ID to test.
   *
   * @var array
   */
  protected $migrationConfiguration = array(
    'id' => 'test',
  );

  /**
   * php.init memory_limit value.
   */
  protected $memoryLimit = 10000000;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migration = $this->getMigration();
    $this->message = $this->getMock('Drupal\migrate\MigrateMessageInterface');

    $this->executable = new TestMigrateExecutable($this->migration, $this->message);
    $this->executable->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Runs the actual test.
   *
   * @param string $message
   *   The second message to assert.
   * @param bool $memory_exceeded
   *   Whether to test the memory exceeded case.
   * @param int $memory_usage_first
   *   (optional) The first memory usage value.
   * @param int $memory_usage_second
   *   (optional) The fake amount of memory usage reported after memory reclaim.
   * @param int $memory_limit
   *   (optional) The memory limit.
   */
  protected function runMemoryExceededTest($message, $memory_exceeded, $memory_usage_first = NULL, $memory_usage_second = NULL, $memory_limit = NULL) {
    $this->executable->setMemoryLimit($memory_limit ?: $this->memoryLimit);
    $this->executable->setMemoryUsage($memory_usage_first ?: $this->memoryLimit, $memory_usage_second ?: $this->memoryLimit);
    $this->executable->setMemoryThreshold(0.85);
    if ($message) {
      $this->executable->message->expects($this->at(0))
        ->method('display')
        ->with($this->stringContains('reclaiming memory'));
      $this->executable->message->expects($this->at(1))
        ->method('display')
        ->with($this->stringContains($message));
    }
    else {
      $this->executable->message->expects($this->never())
        ->method($this->anything());
    }
    $result = $this->executable->memoryExceeded();
    $this->assertEquals($memory_exceeded, $result);
  }

  /**
   * Tests memoryExceeded method when a new batch is needed.
   */
  public function testMemoryExceededNewBatch() {
    // First case try reset and then start new batch.
    $this->runMemoryExceededTest('starting new batch', TRUE);
  }

  /**
   * Tests memoryExceeded method when enough is cleared.
   */
  public function testMemoryExceededClearedEnough() {
    $this->runMemoryExceededTest('reclaimed enough', FALSE, $this->memoryLimit, $this->memoryLimit * 0.75);
  }

  /**
   * Tests memoryExceeded when memory usage is not exceeded.
   */
  public function testMemoryNotExceeded() {
    $this->runMemoryExceededTest('', FALSE, floor($this->memoryLimit * 0.85) - 1);
  }

}
