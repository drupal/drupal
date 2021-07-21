<?php

namespace Drupal\Tests\Core\Extension;

use Drupal\Core\Extension\InfoParser;
use Drupal\Core\Extension\InfoParserException;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * Tests InfoParser class and exception.
 *
 * Files for this test are stored in core/modules/system/tests/fixtures and end
 * with .info.txt instead of info.yml in order not to be considered as real
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
  protected function setUp(): void {
    parent::setUp();
    // Use a fake DRUPAL_ROOT.
    $this->infoParser = new InfoParser('vfs:/');
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
   * Tests if correct exception is thrown for a broken info file.
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
    $this->expectException('\Drupal\Core\Extension\InfoParserException');
    $this->expectExceptionMessage('broken.info.txt');
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
    $this->expectException('\Drupal\Core\Extension\InfoParserException');
    $this->expectExceptionMessage('Missing required keys (type, name) in vfs://modules/fixtures/missing_keys.info.txt');
    $this->infoParser->parse($filename);
  }

  /**
   * Tests that missing 'core' and 'core_version_requirement' keys are detected.
   *
   * @covers ::parse
   */
  public function testMissingCoreCoreVersionRequirement() {
    $missing_core_and_core_version_requirement = <<<MISSING_CORE_AND_CORE_VERSION_REQUIREMENT
# info.yml for testing core and core_version_requirement.
version: VERSION
type: module
name: Skynet
dependencies:
  - self_awareness
MISSING_CORE_AND_CORE_VERSION_REQUIREMENT;

    vfsStream::setup('modules');
    vfsStream::create([
      'fixtures' => [
        'missing_core_and_core_version_requirement.info.txt' => $missing_core_and_core_version_requirement,
        'missing_core_and_core_version_requirement-duplicate.info.txt' => $missing_core_and_core_version_requirement,
      ],
    ]);
    $exception_message = "The 'core_version_requirement' key must be present in vfs://modules/fixtures/missing_core_and_core_version_requirement";
    // Set the expected exception for the 2nd call to parse().
    $this->expectException('\Drupal\Core\Extension\InfoParserException');
    $this->expectExceptionMessage("$exception_message-duplicate.info.txt");

    try {
      $this->infoParser->parse(vfsStream::url('modules/fixtures/missing_core_and_core_version_requirement.info.txt'));
    }
    catch (InfoParserException $exception) {
      $this->assertSame("$exception_message.info.txt", $exception->getMessage());

      $this->infoParser->parse(vfsStream::url('modules/fixtures/missing_core_and_core_version_requirement-duplicate.info.txt'));
    }
  }

  /**
   * Tests that Testing package modules use a default core_version_requirement.
   *
   * @covers ::parse
   */
  public function testTestingPackageMissingCoreCoreVersionRequirement() {
    $missing_core_and_core_version_requirement = <<<MISSING_CORE_AND_CORE_VERSION_REQUIREMENT
# info.yml for testing core and core_version_requirement.
package: Testing
version: VERSION
type: module
name: Skynet
MISSING_CORE_AND_CORE_VERSION_REQUIREMENT;

    vfsStream::setup('modules');
    vfsStream::create([
      'fixtures' => [
        'missing_core_and_core_version_requirement.info.txt' => $missing_core_and_core_version_requirement,
      ],
    ]);
    $info_values = $this->infoParser->parse(vfsStream::url('modules/fixtures/missing_core_and_core_version_requirement.info.txt'));
    $this->assertSame($info_values['core_version_requirement'], \Drupal::VERSION);
  }

  /**
   * Tests that 'core_version_requirement: ^8.8' is valid with no 'core' key.
   *
   * @covers ::parse
   */
  public function testCoreVersionRequirement88() {
    $core_version_requirement = <<<BOTH_CORE_VERSION_REQUIREMENT
# info.yml for testing core and core_version_requirement keys.
package: Core
core_version_requirement: ^8.8
version: VERSION
type: module
name: Module for That
dependencies:
  - field
BOTH_CORE_VERSION_REQUIREMENT;

    vfsStream::setup('modules');
    foreach (['1', '2'] as $file_delta) {
      $filename = "core_version_requirement-$file_delta.info.txt";
      vfsStream::create([
        'fixtures' => [
          $filename => $core_version_requirement,
        ],
      ]);
      $info_values = $this->infoParser->parse(vfsStream::url("modules/fixtures/$filename"));
      $this->assertSame($info_values['core_version_requirement'], '^8.8', "Expected core_version_requirement for file: $filename");
    }
  }

  /**
   * Tests that 'core_version_requirement: ^8.8' is invalid with a 'core' key.
   *
   * @covers ::parse
   */
  public function testCoreCoreVersionRequirement88() {
    $core_and_core_version_requirement_88 = <<<BOTH_CORE_CORE_VERSION_REQUIREMENT_88
# info.yml for testing core and core_version_requirement keys.
package: Core
core: 8.x
core_version_requirement: ^8.8
version: VERSION
type: module
name: Form auto submitter
dependencies:
  - field
BOTH_CORE_CORE_VERSION_REQUIREMENT_88;

    vfsStream::setup('modules');
    vfsStream::create([
      'fixtures' => [
        'core_and_core_version_requirement_88.info.txt' => $core_and_core_version_requirement_88,
        'core_and_core_version_requirement_88-duplicate.info.txt' => $core_and_core_version_requirement_88,
      ],
    ]);
    $exception_message = "The 'core_version_requirement' constraint (^8.8) requires the 'core' key not be set in vfs://modules/fixtures/core_and_core_version_requirement_88";
    // Set the expected exception for the 2nd call to parse().
    $this->expectException('\Drupal\Core\Extension\InfoParserException');
    $this->expectExceptionMessage("$exception_message-duplicate.info.txt");
    try {
      $this->infoParser->parse(vfsStream::url('modules/fixtures/core_and_core_version_requirement_88.info.txt'));
    }
    catch (InfoParserException $exception) {
      $this->assertSame("$exception_message.info.txt", $exception->getMessage());

      $this->infoParser->parse(vfsStream::url('modules/fixtures/core_and_core_version_requirement_88-duplicate.info.txt'));
    }
  }

  /**
   * Tests a invalid 'core' key.
   *
   * @covers ::parse
   *
   * @dataProvider providerInvalidCore
   */
  public function testInvalidCore($core, $filename) {
    $invalid_core = <<<INVALID_CORE
# info.yml for testing invalid core key.
package: Core
core: $core
core_version_requirement: ^8 || ^9
version: VERSION
type: module
name: Llama or Alpaca
description: Tells whether an image is of a Llama or Alpaca
dependencies:
  - llama_detector
  - alpaca_detector
INVALID_CORE;

    vfsStream::setup('modules');
    vfsStream::create([
      'fixtures' => [
        "invalid_core-$filename.info.txt" => $invalid_core,
        "invalid_core-$filename-duplicate.info.txt" => $invalid_core,
      ],
    ]);
    $exception_message = "'core: {$core}' is not supported. Use 'core_version_requirement' to specify core compatibility. Only 'core: 8.x' is supported to provide backwards compatibility for Drupal 8 when needed in vfs://modules/fixtures/invalid_core-$filename";
    // Set the expected exception for the 2nd call to parse().
    $this->expectException('\Drupal\Core\Extension\InfoParserException');
    $this->expectExceptionMessage("$exception_message-duplicate.info.txt");

    try {
      $this->infoParser->parse(vfsStream::url("modules/fixtures/invalid_core-$filename.info.txt"));
    }
    catch (InfoParserException $exception) {
      $this->assertSame("$exception_message.info.txt", $exception->getMessage());

      $this->infoParser->parse(vfsStream::url("modules/fixtures/invalid_core-$filename-duplicate.info.txt"));
    }
  }

  public function providerInvalidCore() {
    return [
      '^8' => [
        '^8',
        'caret8',
      ],
      '^9' => [
        '^9',
        'caret9',
      ],
      '7.x' => [
        '7.x',
        '7.x',
      ],
      '9.x' => [
        '9.x',
        '9.x',
      ],
      '10.x' => [
        '10.x',
        '10.x',
      ],
    ];
  }

  /**
   * Tests a 'core: 8.x' with different values for 'core_version_requirement'.
   *
   * @covers ::parse
   *
   * @dataProvider providerCore8x
   */
  public function testCore8x($core_version_requirement, $filename) {
    $core_8x = <<<CORE_8X
package: Tests
core: 8.x
core_version_requirement: '$core_version_requirement'
version: VERSION
type: module
name: Yet another test module
description: Sorry, I am running out of witty descriptions
CORE_8X;

    vfsStream::setup('modules');
    vfsStream::create([
      'fixtures' => [
        "core_8x-$filename.info.txt" => $core_8x,
        "core_8x-$filename-duplicate.info.txt" => $core_8x,
      ],
    ]);
    $parsed = $this->infoParser->parse(vfsStream::url("modules/fixtures/core_8x-$filename.info.txt"));
    $this->assertSame($core_version_requirement, $parsed['core_version_requirement']);
    $this->infoParser->parse(vfsStream::url("modules/fixtures/core_8x-$filename-duplicate.info.txt"));
    $this->assertSame($core_version_requirement, $parsed['core_version_requirement']);
  }

  /**
   * Data provider for testCore8x().
   */
  public function providerCore8x() {
    return [
      '^8 || ^9' => [
        '^8 || ^9',
        'all-8-9',
      ],
      '*' => [
        '*',
        'asterisk',
      ],
      '>=8' => [
        ">=8",
        'gte8',
      ],
    ];
  }

  /**
   * Tests setting the 'core' key without the 'core_version_requirement' key.
   *
   * @covers ::parse
   *
   * @dataProvider providerCoreWithoutCoreVersionRequirement
   */
  public function testCoreWithoutCoreVersionRequirement($core) {
    $core_without_core_version_requirement = <<<CORE_WITHOUT_CORE_VERSION_REQUIREMENT
package: Dogs
core: $core
version: VERSION
type: module
name: Gracie Daily Picture
description: Shows a random picture of Gracie the Dog everyday.
CORE_WITHOUT_CORE_VERSION_REQUIREMENT;

    vfsStream::setup('modules');
    vfsStream::create([
      'fixtures' => [
        "core_without_core_version_requirement-$core.info.txt" => $core_without_core_version_requirement,
        "core_without_core_version_requirement-$core-duplicate.info.txt" => $core_without_core_version_requirement,
      ],
    ]);
    $exception_message = "'core: $core' is not supported. Use 'core_version_requirement' to specify core compatibility. Only 'core: 8.x' is supported to provide backwards compatibility for Drupal 8 when needed in vfs://modules/fixtures/core_without_core_version_requirement-$core";
    // Set the expected exception for the 2nd call to parse().
    $this->expectException('\Drupal\Core\Extension\InfoParserException');
    $this->expectExceptionMessage("$exception_message-duplicate.info.txt");

    try {
      $this->infoParser->parse(vfsStream::url("modules/fixtures/core_without_core_version_requirement-$core.info.txt"));
    }
    catch (InfoParserException $exception) {
      $this->assertSame("$exception_message.info.txt", $exception->getMessage());
      $this->infoParser->parse(vfsStream::url("modules/fixtures/core_without_core_version_requirement-$core-duplicate.info.txt"));
    }
  }

  /**
   * DataProvider for testCoreWithoutCoreVersionRequirement().
   */
  public function providerCoreWithoutCoreVersionRequirement() {
    return [
      '7.x' => ['7.x'],
      '9.x' => ['9.x'],
      '10.x' => ['10.x'],
    ];
  }

  /**
   * Tests a invalid 'core_version_requirement'.
   *
   * @covers ::parse
   *
   * @dataProvider providerCoreVersionRequirementInvalid
   */
  public function testCoreVersionRequirementInvalid($test_case, $constraint) {
    $invalid_core_version_requirement = <<<INVALID_CORE_VERSION_REQUIREMENT
# info.yml for core_version_requirement validation.
name: Gracie Evaluator
description: 'Determines if Gracie is a "Good Dog". The answer is always "Yes".'
package: Core
type: module
version: VERSION
core_version_requirement: '$constraint'
dependencies:
  - goodness_api
INVALID_CORE_VERSION_REQUIREMENT;

    vfsStream::setup('modules');
    vfsStream::create([
      'fixtures' => [
        "invalid_core_version_requirement-$test_case.info.txt" => $invalid_core_version_requirement,
        "invalid_core_version_requirement-$test_case-duplicate.info.txt" => $invalid_core_version_requirement,
      ],
    ]);
    $exception_message = "The 'core_version_requirement' can not be used to specify compatibility for a specific version before 8.7.7 in vfs://modules/fixtures/invalid_core_version_requirement-$test_case";
    // Set the expected exception for the 2nd call to parse().
    $this->expectException('\Drupal\Core\Extension\InfoParserException');
    $this->expectExceptionMessage("$exception_message-duplicate.info.txt");
    try {
      $this->infoParser->parse(vfsStream::url("modules/fixtures/invalid_core_version_requirement-$test_case.info.txt"));
    }
    catch (InfoParserException $exception) {
      $this->assertSame("$exception_message.info.txt", $exception->getMessage());

      $this->infoParser->parse(vfsStream::url("modules/fixtures/invalid_core_version_requirement-$test_case-duplicate.info.txt"));
    }
  }

  /**
   * Data provider for testCoreVersionRequirementInvalid().
   */
  public function providerCoreVersionRequirementInvalid() {
    return [
      '8.0.0-alpha2' => ['alpha2', '8.0.0-alpha2'],
      '8.6.0-rc1' => ['rc1', '8.6.0-rc1'],
      '^8.7' => ['8_7', '^8.7'],
      '>8.6.3' => ['gt8_6_3', '>8.6.3'],
    ];
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
        'missing_key-duplicate.info.txt' => $missing_key,
      ],
    ]);
    // Set the expected exception for the 2nd call to parse().
    $this->expectException(InfoParserException::class);
    $this->expectExceptionMessage('Missing required keys (type) in vfs://modules/fixtures/missing_key-duplicate.info.txt');
    try {
      $this->infoParser->parse(vfsStream::url('modules/fixtures/missing_key.info.txt'));
    }
    catch (InfoParserException $exception) {
      $this->assertSame('Missing required keys (type) in vfs://modules/fixtures/missing_key.info.txt', $exception->getMessage());

      $this->infoParser->parse(vfsStream::url('modules/fixtures/missing_key-duplicate.info.txt'));
    }

  }

  /**
   * Tests common info file.
   *
   * @covers ::parse
   */
  public function testInfoParserCommonInfo() {
    $common = <<<COMMONTEST
core_version_requirement: '*'
name: common_test
type: module
description: 'testing info file parsing'
simple_string: 'A simple string'
version: "VERSION"
double_colon: dummyClassName::method
COMMONTEST;

    vfsStream::setup('modules');

    foreach (['1', '2'] as $file_delta) {
      $filename = "common_test-$file_delta.info.txt";
      vfsStream::create([
        'fixtures' => [
          $filename => $common,
        ],
      ]);
      $info_values = $this->infoParser->parse(vfsStream::url("modules/fixtures/$filename"));
      $this->assertEquals('A simple string', $info_values['simple_string'], 'Simple string value was parsed correctly.');
      $this->assertEquals(\Drupal::VERSION, $info_values['version'], 'Constant value was parsed correctly.');
      $this->assertEquals('dummyClassName::method', $info_values['double_colon'], 'Value containing double-colon was parsed correctly.');
      $this->assertFalse($info_values['core_incompatible']);
    }
  }

  /**
   * Tests common info file.
   *
   * @covers ::parse
   */
  public function testInfoParserCoreInfo() {
    $common = <<<CORETEST
name: core_test
type: module
version: "VERSION"
description: 'testing info file parsing'
CORETEST;

    vfsStream::setup('core');

    $filename = "core_test.info.txt";
    vfsStream::create([
      'fixtures' => [
        $filename => $common,
      ],
    ]);
    $info_values = $this->infoParser->parse(vfsStream::url("core/fixtures/$filename"));
    $this->assertEquals(\Drupal::VERSION, $info_values['version'], 'Constant value was parsed correctly.');
    $this->assertFalse($info_values['core_incompatible']);
    $this->assertEquals(\Drupal::VERSION, $info_values['core_version_requirement']);
  }

  /**
   * @covers ::parse
   *
   * @dataProvider providerCoreIncompatibility
   */
  public function testCoreIncompatibility($test_case, $constraint, $expected) {
    $core_incompatibility = <<<CORE_INCOMPATIBILITY
core_version_requirement: $constraint
name: common_test
type: module
description: 'testing info file parsing'
simple_string: 'A simple string'
version: "VERSION"
double_colon: dummyClassName::method
CORE_INCOMPATIBILITY;

    vfsStream::setup('modules');
    foreach (['1', '2'] as $file_delta) {
      $filename = "core_incompatible-$test_case-$file_delta.info.txt";
      vfsStream::create([
        'fixtures' => [
          $filename => $core_incompatibility,
        ],
      ]);
      $info_values = $this->infoParser->parse(vfsStream::url("modules/fixtures/$filename"));
      $this->assertSame($expected, $info_values['core_incompatible'], "core_incompatible correct in file: $filename");
    }
  }

  /**
   * Data provider for testCoreIncompatibility().
   */
  public function providerCoreIncompatibility() {
    list($major, $minor) = explode('.', \Drupal::VERSION);

    $next_minor = $minor + 1;
    $next_major = $major + 1;
    return [
      'next_minor' => [
        'next_minor',
        "^$major.$next_minor",
        TRUE,
      ],
      'current_major_next_major' => [
        'current_major_next_major',
        "^$major || ^$next_major",
        FALSE,
      ],
      'previous_major_next_major' => [
        'previous_major_next_major',
        "^1 || ^$next_major",
        TRUE,
      ],
      'current_minor' => [
        'current_minor',
        "~$major.$minor",
        FALSE,
      ],
    ];
  }

  /**
   * Tests a profile info file.
   */
  public function testProfile() {
    $profile = <<<PROFILE_TEST
core_version_requirement: '*'
name: The Perfect Profile
type: profile
description: 'This profile makes Drupal perfect. You should have no complaints.'
PROFILE_TEST;

    vfsStream::setup('profiles');
    vfsStream::create([
      'fixtures' => [
        'invalid_profile.info.txt' => $profile,
      ],
    ]);
    $info = $this->infoParser->parse(vfsStream::url('profiles/fixtures/invalid_profile.info.txt'));
    $this->assertFalse($info['core_incompatible']);
  }

  /**
   * Tests the exception for an unparsable 'core_version_requirement' value.
   *
   * @covers ::parse
   */
  public function testUnparsableCoreVersionRequirement() {
    $unparsable_core_version_requirement = <<<UNPARSABLE_CORE_VERSION_REQUIREMENT
# info.yml for testing invalid core_version_requirement value.
name: Not this module
description: 'Not the module you are looking for.'
package: Core
type: module
version: VERSION
core_version_requirement: not-this-version
UNPARSABLE_CORE_VERSION_REQUIREMENT;

    vfsStream::setup('modules');
    vfsStream::create([
      'fixtures' => [
        'unparsable_core_version_requirement.info.txt' => $unparsable_core_version_requirement,
      ],
    ]);
    $this->expectException(InfoParserException::class);
    $this->expectExceptionMessage("The 'core_version_requirement' constraint (not-this-version) is not a valid value in vfs://modules/fixtures/unparsable_core_version_requirement.info.txt");
    $this->infoParser->parse(vfsStream::url('modules/fixtures/unparsable_core_version_requirement.info.txt'));
  }

  /**
   * Tests an info file with 'core: 8.x' but without 'core_version_requirement'.
   *
   * @covers ::parse
   */
  public function testCore8xNoCoreVersionRequirement() {
    $info = <<<INFO
package: Core
core: 8.x
version: VERSION
type: module
name: Module for That
dependencies:
  - field
INFO;

    vfsStream::setup('modules');
    foreach (['1', '2'] as $file_delta) {
      $filename = "core_version_requirement-$file_delta.info.txt";
      vfsStream::create([
        'fixtures' => [
          $filename => $info,
        ],
      ]);
      $info_values = $this->infoParser->parse(vfsStream::url("modules/fixtures/$filename"));
      $this->assertSame(TRUE, $info_values['core_incompatible'], "Expected 'core_incompatible's for file: $filename");
    }
  }

}
