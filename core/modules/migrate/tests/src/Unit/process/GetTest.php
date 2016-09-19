<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\process\GetTest.
 */

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\Plugin\migrate\process\TestGet;

/**
 * Tests the get process plugin.
 *
 * @group migrate
 */
class GetTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->plugin = new TestGet();
    parent::setUp();
  }

  /**
   * Tests the Get plugin when source is a string.
   */
  public function testTransformSourceString() {
    $this->row->expects($this->once())
      ->method('getSourceProperty')
      ->with('test')
      ->will($this->returnValue('source_value'));
    $this->plugin->setSource('test');
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, 'source_value');
  }

  /**
   * Tests the Get plugin when source is an array.
   */
  public function testTransformSourceArray() {
    $map = array(
      'test1' => 'source_value1',
      'test2' => 'source_value2',
    );
    $this->plugin->setSource(array('test1', 'test2'));
    $this->row->expects($this->exactly(2))
      ->method('getSourceProperty')
      ->will($this->returnCallback(function ($argument)  use ($map) { return $map[$argument]; } ));
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, array('source_value1', 'source_value2'));
  }

  /**
   * Tests the Get plugin when source is a string pointing to destination.
   */
  public function testTransformSourceStringAt() {
    $this->row->expects($this->once())
      ->method('getSourceProperty')
      ->with('@test')
      ->will($this->returnValue('source_value'));
    $this->plugin->setSource('@@test');
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, 'source_value');
  }

  /**
   * Tests the Get plugin when source is an array pointing to destination.
   */
  public function testTransformSourceArrayAt() {
    $map = array(
      'test1' => 'source_value1',
      '@test2' => 'source_value2',
      '@test3' => 'source_value3',
      'test4' => 'source_value4',
    );
    $this->plugin->setSource(array('test1', '@@test2', '@@test3', 'test4'));
    $this->row->expects($this->exactly(4))
      ->method('getSourceProperty')
      ->will($this->returnCallback(function ($argument)  use ($map) { return $map[$argument]; } ));
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, array('source_value1', 'source_value2', 'source_value3', 'source_value4'));
  }

  /**
   * Tests the Get plugin when source has integer values.
   */
  public function testIntegerValues() {
    $this->row->expects($this->exactly(2))
      ->method('getSourceProperty')
      ->willReturnOnConsecutiveCalls('val1', 'val2');

    $this->plugin->setSource([0 => 0, 1 => 'test']);
    $return = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame([0 => 'val1', 1 => 'val2'], $return);

    $this->plugin->setSource([FALSE]);
    $return = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame([NULL], $return);

    $this->plugin->setSource([NULL]);
    $return = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame([NULL], $return);
  }

}

namespace Drupal\migrate\Plugin\migrate\process;

class TestGet extends Get {
  public function __construct() {
  }
  public function setSource($source) {
    $this->configuration['source'] = $source;
  }

}
