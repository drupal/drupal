<?php

namespace Drupal\Tests\Core\Extension;

use Drupal\Core\Extension\ExtensionLifecycle;
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
    $this->assertEmpty($info, 'Non existing info.yml returns empty array.');
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
core_version_requirement: '*'
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
   * Tests that a missing 'core_version_requirement' key is detected.
   *
   * @covers ::parse
   */
  public function testMissingCoreVersionRequirement() {
    $missing_core_version_requirement = <<<MISSING_CORE_VERSION_REQUIREMENT
# info.yml for testing core_version_requirement.
version: VERSION
type: module
name: Skynet
dependencies:
  - self_awareness
MISSING_CORE_VERSION_REQUIREMENT;

    vfsStream::setup('modules');
    vfsStream::create([
      'fixtures' => [
        'missing_core_version_requirement.info.txt' => $missing_core_version_requirement,
        'missing_core_version_requirement-duplicate.info.txt' => $missing_core_version_requirement,
      ],
    ]);
    $exception_message = "The 'core_version_requirement' key must be present in vfs://modules/fixtures/missing_core_version_requirement";
    // Set the expected exception for the 2nd call to parse().
    $this->expectException('\Drupal\Core\Extension\InfoParserException');
    $this->expectExceptionMessage("$exception_message-duplicate.info.txt");

    try {
      $this->infoParser->parse(vfsStream::url('modules/fixtures/missing_core_version_requirement.info.txt'));
    }
    catch (InfoParserException $exception) {
      $this->assertSame("$exception_message.info.txt", $exception->getMessage());

      $this->infoParser->parse(vfsStream::url('modules/fixtures/missing_core_version_requirement-duplicate.info.txt'));
    }
  }

  /**
   * Tests that Testing package modules use a default core_version_requirement.
   *
   * @covers ::parse
   */
  public function testTestingPackageMissingCoreVersionRequirement() {
    $missing_core_version_requirement = <<<MISSING_CORE_VERSION_REQUIREMENT
# info.yml for testing core_version_requirement.
package: Testing
version: VERSION
type: module
name: Skynet
MISSING_CORE_VERSION_REQUIREMENT;

    vfsStream::setup('modules');
    vfsStream::create([
      'fixtures' => [
        'missing_core_version_requirement.info.txt' => $missing_core_version_requirement,
      ],
    ]);
    $info_values = $this->infoParser->parse(vfsStream::url('modules/fixtures/missing_core_version_requirement.info.txt'));
    $this->assertSame($info_values['core_version_requirement'], \Drupal::VERSION);
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
    [$major, $minor] = explode('.', \Drupal::VERSION);

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
   * Tests an info file with valid lifecycle values.
   *
   * @covers ::parse
   *
   * @dataProvider providerValidLifecycle
   */
  public function testValidLifecycle($lifecycle, $expected) {
    $info = <<<INFO
package: Core
core_version_requirement: '*'
version: VERSION
type: module
name: Module for That
INFO;
    if (!empty($lifecycle)) {
      $info .= "\nlifecycle: $lifecycle\n";
    }
    if (in_array($lifecycle, [ExtensionLifecycle::DEPRECATED, ExtensionLifecycle::OBSOLETE], TRUE)) {
      $info .= "\nlifecycle_link: http://example.com\n";
    }
    vfsStream::setup('modules');
    $filename = "lifecycle-$lifecycle.info.yml";
    vfsStream::create([
      'fixtures' => [
        $filename => $info,
      ],
    ]);
    $info_values = $this->infoParser->parse(vfsStream::url("modules/fixtures/$filename"));
    $this->assertSame($expected, $info_values[ExtensionLifecycle::LIFECYCLE_IDENTIFIER]);
  }

  /**
   * Data provider for testValidLifecycle().
   */
  public function providerValidLifecycle() {
    return [
      'empty' => [
        '',
        ExtensionLifecycle::STABLE,
      ],
      'experimental' => [
        ExtensionLifecycle::EXPERIMENTAL,
        ExtensionLifecycle::EXPERIMENTAL,
      ],
      'stable' => [
        ExtensionLifecycle::STABLE,
        ExtensionLifecycle::STABLE,
      ],
      'deprecated' => [
        ExtensionLifecycle::DEPRECATED,
        ExtensionLifecycle::DEPRECATED,
      ],
      'obsolete' => [
        ExtensionLifecycle::OBSOLETE,
        ExtensionLifecycle::OBSOLETE,
      ],
    ];
  }

  /**
   * Tests an info file with invalid lifecycle values.
   *
   * @covers ::parse
   *
   * @dataProvider providerInvalidLifecycle
   */
  public function testInvalidLifecycle($lifecycle, $exception_message) {
    $info = <<<INFO
package: Core
core_version_requirement: '*'
version: VERSION
type: module
name: Module for That
INFO;
    $info .= "\nlifecycle: $lifecycle\n";
    vfsStream::setup('modules');
    $filename = "lifecycle-$lifecycle.info.txt";
    vfsStream::create([
      'fixtures' => [
        $filename => $info,
      ],
    ]);
    $this->expectException('\Drupal\Core\Extension\InfoParserException');
    $this->expectExceptionMessage($exception_message);
    $info_values = $this->infoParser->parse(vfsStream::url("modules/fixtures/$filename"));
    $this->assertEmpty($info_values);
  }

  /**
   * Data provider for testInvalidLifecycle().
   */
  public function providerInvalidLifecycle() {
    return [
      'bogus' => [
        'bogus',
        "'lifecycle: bogus' is not valid",
      ],
      'two words' => [
        'deprecated obsolete',
        "'lifecycle: deprecated obsolete' is not valid",
      ],
      'wrong case' => [
        'Experimental',
        "'lifecycle: Experimental' is not valid",
      ],
    ];
  }

  /**
   * Tests an info file's lifecycle_link values.
   *
   * @covers ::parse
   *
   * @dataProvider providerLifecycleLink
   */
  public function testLifecycleLink($lifecycle, $lifecycle_link = NULL, $exception_message = NULL) {
    $info = <<<INFO
package: Core
core_version_requirement: '*'
version: VERSION
type: module
name: Module for That
lifecycle: $lifecycle
INFO;
    if (($lifecycle_link)) {
      $info .= "\nlifecycle_link: $lifecycle_link\n";
    }
    vfsStream::setup('modules');
    // Use a random file name to bypass the static caching in
    // \Drupal\Core\Extension\InfoParser.
    $random = mb_strtolower($this->randomMachineName());
    $filename = "lifecycle-$random.info.yml";
    vfsStream::create([
      'fixtures' => [
        $filename => $info,
      ],
    ]);
    $path = vfsStream::url("modules/fixtures/$filename");
    if ($exception_message) {
      $this->expectException(InfoParserException::class);
      $this->expectExceptionMessage(sprintf($exception_message, $path));
    }
    $info_values = $this->infoParser->parse($path);
    $this->assertSame($lifecycle, $info_values[ExtensionLifecycle::LIFECYCLE_IDENTIFIER]);
  }

  /**
   * Data provider for testLifecycleLink().
   */
  public function providerLifecycleLink() {
    return [
      'valid deprecated' => [
        ExtensionLifecycle::DEPRECATED,
        'http://example.com',
      ],
      'valid obsolete' => [
        ExtensionLifecycle::OBSOLETE,
        'http://example.com',
      ],
      'valid stable' => [
        ExtensionLifecycle::STABLE,
      ],
      'valid experimental' => [
        ExtensionLifecycle::EXPERIMENTAL,
      ],
      'missing deprecated' => [
        ExtensionLifecycle::DEPRECATED,
        NULL,
        "Extension Module for That (%s) has 'lifecycle: deprecated' but is missing a 'lifecycle_link' entry.",
      ],
      'missing obsolete' => [
        ExtensionLifecycle::OBSOLETE,
        NULL,
        "Extension Module for That (%s) has 'lifecycle: obsolete' but is missing a 'lifecycle_link' entry.",
      ],
      'invalid deprecated' => [
        ExtensionLifecycle::DEPRECATED,
        'look ma, not a url',
        "Extension Module for That (%s) has a 'lifecycle_link' entry that is not a valid URL.",
      ],
      'invalid obsolete' => [
        ExtensionLifecycle::OBSOLETE,
        'I think you may find that this is also not a url',
        "Extension Module for That (%s) has a 'lifecycle_link' entry that is not a valid URL.",
      ],
    ];
  }

}
