<?php

namespace Drupal\Tests\Core\Extension;

use Drupal\Core\Extension\InfoParser;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * Tests InfoParser class and exception.
 *
 * Files for this test are stored in core/modules/system/tests/fixtures and end
 * with .info.txt instead of info.yml in order not not be considered as real
 * extensions.
 *
 * @coversDefaultClass \Drupal\Core\Extension\InfoParser
 *
 * @group Extension
 */
class InfoParserUnitTest extends UnitTestCase {

  /**
   * The InfoParser object.
   *
   * @var \Drupal\Core\Extension\InfoParser
   */
  protected $infoParser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->infoParser = new InfoParser();
  }

  /**
   * Tests the functionality of the infoParser object.
   *
   * @covers ::parse
   */
  public function testInfoParserNonExisting() {
    vfsStream::setup('modules');
    $info = $this->infoParser->parse(vfsStream::url('modules') . '/does_not_exist.info.txt');
    $this->assertTrue(empty($info), 'Non existing info.yml returns empty array.');
  }

  /**
   * Test if correct exception is thrown for a broken info file.
   *
   * @covers ::parse
   */
  public function testInfoParserBroken() {
    $broken_info = <<<BROKEN_INFO
# info.yml for testing broken YAML parsing exception handling.
name: File
type: module
description: 'Defines a file field type.'
package: Core
version: VERSION
core: 8.x
dependencies::;;
  - field
BROKEN_INFO;

    vfsStream::setup('modules');
    vfsStream::create([
      'fixtures' => [
        'broken.info.txt' => $broken_info,
      ],
    ]);
    $filename = vfsStream::url('modules/fixtures/broken.info.txt');
    $this->setExpectedException('\Drupal\Core\Extension\InfoParserException', 'broken.info.txt');
    $this->infoParser->parse($filename);
  }

  /**
   * Tests that missing required keys are detected.
   *
   * @covers ::parse
   */
  public function testInfoParserMissingKeys() {
    $missing_keys = <<<MISSINGKEYS
# info.yml for testing missing name, description, and type keys.
package: Core
version: VERSION
dependencies:
  - field
MISSINGKEYS;

    vfsStream::setup('modules');
    vfsStream::create([
      'fixtures' => [
        'missing_keys.info.txt' => $missing_keys,
      ],
    ]);
    $filename = vfsStream::url('modules/fixtures/missing_keys.info.txt');
    $this->setExpectedException('\Drupal\Core\Extension\InfoParserException', 'Missing required keys (type, core, name) in vfs://modules/fixtures/missing_keys.info.txt');
    $this->infoParser->parse($filename);
  }

  /**
   * Tests that missing required key is detected.
   *
   * @covers ::parse
   */
  public function testInfoParserMissingKey() {
    $missing_key = <<<MISSINGKEY
# info.yml for testing missing type key.
name: File
description: 'Defines a file field type.'
package: Core
version: VERSION
core: 8.x
dependencies:
  - field
MISSINGKEY;

    vfsStream::setup('modules');
    vfsStream::create([
      'fixtures' => [
        'missing_key.info.txt' => $missing_key,
      ],
    ]);
    $filename = vfsStream::url('modules/fixtures/missing_key.info.txt');
    $this->setExpectedException('\Drupal\Core\Extension\InfoParserException', 'Missing required keys (type) in vfs://modules/fixtures/missing_key.info.txt');
    $this->infoParser->parse($filename);
  }

  /**
   * Tests common info file.
   *
   * @covers ::parse
   */
  public function testInfoParserCommonInfo() {
    $common = <<<COMMONTEST
core: 8.x
name: common_test
type: module
description: 'testing info file parsing'
simple_string: 'A simple string'
version: "VERSION"
double_colon: dummyClassName::method
COMMONTEST;

    vfsStream::setup('modules');
    vfsStream::create([
      'fixtures' => [
        'common_test.info.txt' => $common,
      ],
    ]);
    $info_values = $this->infoParser->parse(vfsStream::url('modules/fixtures/common_test.info.txt'));
    $this->assertEquals($info_values['simple_string'], 'A simple string', 'Simple string value was parsed correctly.');
    $this->assertEquals($info_values['version'], \Drupal::VERSION, 'Constant value was parsed correctly.');
    $this->assertEquals($info_values['double_colon'], 'dummyClassName::method', 'Value containing double-colon was parsed correctly.');
  }

}
