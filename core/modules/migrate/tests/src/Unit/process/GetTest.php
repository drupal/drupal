<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\Plugin\migrate\process\Get;

/**
 * Tests the get process plugin.
 *
 * @group migrate
 */
class GetTest extends MigrateProcessTestCase {

  /**
   * Tests the Get plugin when source is a string.
   */
  public function testTransformSourceString() {
    $this->row->expects($this->once())
      ->method('get')
      ->with('test')
      ->willReturn('source_value');
    $this->plugin = new Get(['source' => 'test'], '', []);
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('source_value', $value);
  }

  /**
   * Tests the Get plugin when source is an array.
   */
  public function testTransformSourceArray() {
    $map = [
      'test1' => 'source_value1',
      'test2' => 'source_value2',
    ];
    $this->plugin = new Get(['source' => ['test1', 'test2']], '', []);
    $this->row->expects($this->exactly(2))
      ->method('get')
      ->willReturnCallback(function ($argument) use ($map) {
        return $map[$argument];
      });
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame(['source_value1', 'source_value2'], $value);
  }

  /**
   * Tests the Get plugin when source is a string pointing to destination.
   */
  public function testTransformSourceStringAt() {
    $this->row->expects($this->once())
      ->method('get')
      ->with('@@test')
      ->willReturn('source_value');
    $this->plugin = new Get(['source' => '@@test'], '', []);
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('source_value', $value);
  }

  /**
   * Tests the Get plugin when source is an array pointing to destination.
   */
  public function testTransformSourceArrayAt() {
    $map = [
      'test1' => 'source_value1',
      '@@test2' => 'source_value2',
      '@@test3' => 'source_value3',
      'test4' => 'source_value4',
    ];
    $this->plugin = new Get(['source' => ['test1', '@@test2', '@@test3', 'test4']], '', []);
    $this->row->expects($this->exactly(4))
      ->method('get')
      ->willReturnCallback(function ($argument) use ($map) {
        return $map[$argument];
      });
    $value = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame(['source_value1', 'source_value2', 'source_value3', 'source_value4'], $value);
  }

  /**
   * Tests the Get plugin when source has integer values.
   *
   * @dataProvider integerValuesDataProvider
   */
  public function testIntegerValues($source, $expected_value) {
    $this->row->expects($this->atMost(2))
      ->method('get')
      ->willReturnOnConsecutiveCalls('val1', 'val2');

    $this->plugin = new Get(['source' => $source], '', []);
    $return = $this->plugin->transform(NULL, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($expected_value, $return);
  }

  /**
   * Provides data for the successful lookup test.
   *
   * @return array
   */
  public function integerValuesDataProvider() {
    return [
      [
        'source' => [0 => 0, 1 => 'test'],
        'expected_value' => [0 => 'val1', 1 => 'val2'],
      ],
      [
        'source' => [FALSE],
        'expected_value' => [NULL],
      ],
      [
        'source' => [NULL],
        'expected_value' => [NULL],
      ],
    ];
  }

  /**
   * Tests the Get plugin for syntax errors, e.g. "Invalid tag_line detected" by
   * creating a prophecy of the class.
   */
  public function testPluginSyntax() {
    $this->assertNotNull($this->prophesize(Get::class));
  }

}
