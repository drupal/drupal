<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\ParseInfoFileUnitTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests the drupal_parse_info_file() API function.
 */
class ParseInfoFileUnitTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Parsing .info.yml files',
      'description' => 'Tests the drupal_parse_info_file() API function.',
      'group' => 'Common',
    );
  }

  /**
   * Parses an example .info.yml file and verifies the results.
   */
  function testParseInfoFile() {
    $info_values = drupal_parse_info_file(drupal_get_path('module', 'system') . '/tests/common_test_info.txt');
    $this->assertEqual($info_values['simple_string'], 'A simple string', 'Simple string value was parsed correctly.', 'System');
    $this->assertEqual($info_values['version'], \Drupal::VERSION, 'Constant value was parsed correctly.', 'System');
    $this->assertEqual($info_values['double_colon'], 'dummyClassName::', 'Value containing double-colon was parsed correctly.', 'System');
  }
}
