<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Extension\InfoParserUnitTest.
 */

namespace Drupal\system\Tests\Extension;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Core\Extension\InfoParser;
use Drupal\Core\Extension\InfoParserException;

/**
 * Tests InfoParser class.
 *
 * Files for this test are stored in core/modules/system/tests/fixtures and end
 * with .info.txt instead of info.yml in order not not be considered as real
 * extensions.
 */
class InfoParserUnitTest extends DrupalUnitTestBase {

  /**
   * The InfoParser object.
   *
   * @var \Drupal\Core\Extension\InfoParser
   */
  protected $infoParser;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'InfoParser',
      'description' => 'Tests InfoParser class and exception.',
      'group' => 'Extension',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->infoParser = new InfoParser();
  }

  /**
   * Tests the functionality of the infoParser object.
   */
  public function testInfoParser() {
    $info = $this->infoParser->parse('core/modules/system/tests/fixtures/does_not_exist.info.txt');
    $this->assertTrue(empty($info), 'Non existing info.yml returns empty array.');

    // Test that invalid YAML throws an exception and that message contains the
    // filename that caused it.
    $filename = 'core/modules/system/tests/fixtures/broken.info.txt';
    try {
      $this->infoParser->parse($filename);
      $this->fail('Expected InfoParserException not thrown when reading broken.info.txt');
    }
    catch (InfoParserException $e) {
      $this->assertTrue(strpos($e->getMessage(), $filename) !== FALSE, 'Exception message contains info.yml filename.');
    }

    // Tests that missing required keys are detected.
    $filename = 'core/modules/system/tests/fixtures/missing_keys.info.txt';
    try {
      $this->infoParser->parse($filename);
      $this->fail('Expected InfoParserException not thrown when reading missing_keys.info.txt');
    }
    catch (InfoParserException $e) {
      $expected_message = "Missing required keys (type, core, name) in $filename.";
      $this->assertEqual($e->getMessage(), $expected_message);
    }

    // Tests that a single missing required key is detected.
    $filename = 'core/modules/system/tests/fixtures/missing_key.info.txt';
    try {
      $this->infoParser->parse($filename);
      $this->fail('Expected InfoParserException not thrown when reading missing_key.info.txt');
    }
    catch (InfoParserException $e) {
      $expected_message = "Missing required key (type) in $filename.";
      $this->assertEqual($e->getMessage(), $expected_message);
    }

    $info_values = $this->infoParser->parse('core/modules/system/tests/fixtures/common_test.info.txt');
    $this->assertEqual($info_values['simple_string'], 'A simple string', 'Simple string value was parsed correctly.', 'System');
    $this->assertEqual($info_values['version'], \Drupal::VERSION, 'Constant value was parsed correctly.', 'System');
    $this->assertEqual($info_values['double_colon'], 'dummyClassName::', 'Value containing double-colon was parsed correctly.', 'System');
  }

}
