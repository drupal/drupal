<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\Plugin\migrate\process\Log;
use Drupal\migrate\Row;

/**
 * Tests the Log process plugin.
 *
 * @group migrate
 */
class LogTest extends MigrateProcessTestCase {

  /**
   * Tests the Log plugin.
   *
   * @dataProvider providerTestLog
   */
  public function testLog($value, $expected_message): void {
    // Test the expected log message.
    $this->migrateExecutable->expects($this->once())
      ->method('saveMessage')
      ->with($expected_message);
    $plugin = new Log([], 'log', []);

    // Test the input value is not altered.
    $new_value = $plugin->transform($value, $this->migrateExecutable, new Row(), 'foo');
    $this->assertSame($value, $new_value);
  }

  /**
   * Provides data for testLog.
   *
   * @return string[][]
   *   An array of test data arrays.
   */
  public static function providerTestLog() {
    $object = (object) [
      'a' => 'test',
      'b' => 'test2',
      'c' => 'test3',
    ];
    $xml_str = <<<XML
<?xml version='1.0'?>
<mathematician>
 <name>Ada Lovelace</name>
</mathematician>
XML;
    return [
      'int zero' => [
        'value' => 0,
        'expected_message' => "'foo' value is '0'",
      ],
      'string empty' => [
        'value' => '',
        'expected_message' => "'foo' value is ''",
      ],
      'string' => [
        'value' => 'Testing the log message',
        'expected_message' => "'foo' value is 'Testing the log message'",
      ],
      'array' => [
        'value' => ['key' => 'value'],
        'expected_message' => "'foo' value is 'Array\n(\n    [key] => value\n)\n'",
      ],
      'float' => [
        'value' => 1.123,
        'expected_message' => "'foo' value is '1.123000'",
      ],
      'NULL' => [
        'value' => NULL,
        'expected_message' => "'foo' value is 'NULL'",
      ],
      'boolean' => [
        'value' => TRUE,
        'expected_message' => "'foo' value is 'true'",
      ],
      'object_with_to_String' => [
        'value' => new ObjWithString(),
        'expected_message' => "'foo' value is Drupal\Tests\migrate\Unit\process\ObjWithString:\n'a test string'",
      ],
      'object_no_to_string' => [
        'value' => $object,
        'expected_message' => "Unable to log the value for 'foo'",
      ],
      'simple_xml' => [
        'value' => new \SimpleXMLElement($xml_str),
        'expected_message' => "'foo' value is SimpleXMLElement:\n'\n \n'",
      ],
    ];
  }

}

/**
 * Test class with a __toString() method.
 */
class ObjWithString {

  /**
   * Returns a string.
   *
   * @return string
   *   A string.
   */
  public function __toString() {
    return 'a test string';
  }

}
