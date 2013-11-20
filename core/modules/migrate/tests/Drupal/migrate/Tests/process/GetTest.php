<?php
/**
 * @file
 * Contains
 */

namespace Drupal\migrate\Tests\process;

use Drupal\migrate\Plugin\migrate\process\TestGet;

/**
 * @group migrate
 * @group Drupal
 */
class GetTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Get process plugin',
      'description' => 'Tests the get process plugin.',
      'group' => 'Migrate',
    );
  }

  function setUp() {
    $this->plugin = new TestGet();
    parent::setUp();
  }

  function testTransformSourceString() {
    $this->row->expects($this->once())
      ->method('getSourceProperty')
      ->with('test')
      ->will($this->returnValue('source_value'));
    $this->plugin->setSource('test');
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, 'source_value');
  }

  function testTransformSourceArray() {
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

  function testTransformSourceStringAt() {
    $this->row->expects($this->once())
      ->method('getSourceProperty')
      ->with('@test')
      ->will($this->returnValue('source_value'));
    $this->plugin->setSource('@@test');
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($value, 'source_value');
  }

  function testTransformSourceArrayAt() {
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
}

namespace Drupal\migrate\Plugin\migrate\process;

class TestGet extends Get {
  function __construct() {
  }
  function setSource($source) {
    $this->configuration['source'] = $source;
  }
}
