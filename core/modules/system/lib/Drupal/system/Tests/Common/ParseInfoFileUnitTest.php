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
      'name' => 'Parsing .info files',
      'description' => 'Tests the drupal_parse_info_file() API function.',
      'group' => 'Common',
    );
  }

  /**
   * Parse an example .info file an verify the results.
   */
  function testParseInfoFile() {
    $info_values = drupal_parse_info_file(drupal_get_path('module', 'system') . '/tests/common_test_info.txt');
    $this->assertEqual($info_values['simple_string'], 'A simple string', t('Simple string value was parsed correctly.'), t('System'));
    $this->assertEqual($info_values['simple_constant'], WATCHDOG_INFO, t('Constant value was parsed correctly.'), t('System'));
    $this->assertEqual($info_values['double_colon'], 'dummyClassName::', t('Value containing double-colon was parsed correctly.'), t('System'));
  }
}
