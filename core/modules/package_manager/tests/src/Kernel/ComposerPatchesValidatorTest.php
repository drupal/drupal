<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\Url;
use Drupal\fixture_manipulator\ActiveFixtureManipulator;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Exception\StageEventException;
use Drupal\package_manager\ValidationResult;

/**
 * @covers \Drupal\package_manager\Validator\ComposerPatchesValidator
 * @group package_manager
 * @internal
 */
class ComposerPatchesValidatorTest extends PackageManagerKernelTestBase {

  const ABSENT = 0;
  const CONFIG_ALLOWED_PLUGIN = 1;
  const EXTRA_EXIT_ON_PATCH_FAILURE = 2;
  const REQUIRE_PACKAGE_FROM_ROOT = 4;
  const REQUIRE_PACKAGE_INDIRECTLY = 8;

  /**
   * Data provider for testErrorDuringPreCreate().
   *
   * @return mixed[][]
   *   The test cases.
   */
  public static function providerErrorDuringPreCreate(): array {
    $summary = t('Problems detected related to the Composer plugin <code>cweagans/composer-patches</code>.');
    return [
      'INVALID: exit-on-patch-failure missing' => [
        static::CONFIG_ALLOWED_PLUGIN | static::REQUIRE_PACKAGE_FROM_ROOT,
        [
          ValidationResult::createError([
            t('The <code>composer-exit-on-patch-failure</code> key is not set to <code>true</code> in the <code>extra</code> section of <code>composer.json</code>.'),
          ], $summary),
        ],
      ],
      'INVALID: indirect dependency' => [
        static::CONFIG_ALLOWED_PLUGIN | static::EXTRA_EXIT_ON_PATCH_FAILURE | static::REQUIRE_PACKAGE_INDIRECTLY,
        [
          ValidationResult::createError([
            t('It must be a root dependency.'),
          ], $summary),
        ],
        [
          'package-manager-faq-composer-patches-not-a-root-dependency',
          NULL,
        ],
      ],
      'VALID: present' => [
        static::CONFIG_ALLOWED_PLUGIN | static::EXTRA_EXIT_ON_PATCH_FAILURE | static::REQUIRE_PACKAGE_FROM_ROOT,
        [],
      ],
      'VALID: absent' => [
        static::ABSENT,
        [],
      ],
    ];
  }

  /**
   * Tests that the patcher configuration is validated during pre-create.
   *
   * @param int $options
   *   What aspects of the patcher are installed how.
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   The expected validation results.
   *
   *  @dataProvider providerErrorDuringPreCreate
   */
  public function testErrorDuringPreCreate(int $options, array $expected_results): void {
    $active_manipulator = new ActiveFixtureManipulator();
    if ($options & static::CONFIG_ALLOWED_PLUGIN) {
      $active_manipulator->addConfig(['allow-plugins.cweagans/composer-patches' => TRUE]);
    }
    if ($options & static::EXTRA_EXIT_ON_PATCH_FAILURE) {
      $active_manipulator->addConfig(['extra.composer-exit-on-patch-failure' => TRUE]);
    }
    if ($options & static::REQUIRE_PACKAGE_FROM_ROOT) {
      $active_manipulator->requirePackage('cweagans/composer-patches', '@dev');
    }
    elseif ($options & static::REQUIRE_PACKAGE_INDIRECTLY) {
      $active_manipulator->addPackage([
        'type' => 'package',
        'name' => 'dummy/depends-on-composer-patches',
        'description' => 'A dummy package depending on cweagans/composer-patches',
        'version' => '1.0.0',
        'require' => ['cweagans/composer-patches' => '*'],
      ]);
    }
    if ($options !== static::ABSENT) {
      $active_manipulator->commitChanges();
    }
    $this->assertStatusCheckResults($expected_results);
    $this->assertResults($expected_results, PreCreateEvent::class);
  }

  /**
   * Data provider for testErrorDuringPreApply() and testHelpLink().
   *
   * @return mixed[][]
   *   The test cases.
   */
  public static function providerErrorDuringPreApply(): array {
    $summary = t('Problems detected related to the Composer plugin <code>cweagans/composer-patches</code>.');

    return [
      'composer-patches present in stage, but not present in active' => [
        static::ABSENT,
        static::CONFIG_ALLOWED_PLUGIN | static::EXTRA_EXIT_ON_PATCH_FAILURE | static::REQUIRE_PACKAGE_FROM_ROOT,
        [
          ValidationResult::createError([
            t('It cannot be installed by Package Manager.'),
          ], $summary),
        ],
        [
          'package-manager-faq-composer-patches-installed-or-removed',
        ],
      ],
      'composer-patches partially present (exit missing)  in stage, but not present in active' => [
        static::ABSENT,
        static::CONFIG_ALLOWED_PLUGIN | static::REQUIRE_PACKAGE_FROM_ROOT,
        [
          ValidationResult::createError([
            t('It cannot be installed by Package Manager.'),
            t('The <code>composer-exit-on-patch-failure</code> key is not set to <code>true</code> in the <code>extra</code> section of <code>composer.json</code>.'),
          ], $summary),
        ],
        [
          'package-manager-faq-composer-patches-installed-or-removed',
          NULL,
        ],
      ],
      'composer-patches present due to non-root dependency in stage, but not present in active' => [
        static::CONFIG_ALLOWED_PLUGIN | static::EXTRA_EXIT_ON_PATCH_FAILURE,
        static::CONFIG_ALLOWED_PLUGIN | static::EXTRA_EXIT_ON_PATCH_FAILURE | static::REQUIRE_PACKAGE_INDIRECTLY,
        [
          ValidationResult::createError([
            t('It cannot be installed by Package Manager.'),
            t('It must be a root dependency.'),
          ], $summary),
        ],
        [
          'package-manager-faq-composer-patches-installed-or-removed',
          'package-manager-faq-composer-patches-not-a-root-dependency',
          NULL,
        ],
      ],
      'composer-patches removed in stage, but present in active' => [
        static::CONFIG_ALLOWED_PLUGIN | static::EXTRA_EXIT_ON_PATCH_FAILURE | static::REQUIRE_PACKAGE_FROM_ROOT,
        static::ABSENT,
        [
          ValidationResult::createError([
            t('It cannot be removed by Package Manager.'),
          ], $summary),
        ],
        [
          'package-manager-faq-composer-patches-installed-or-removed',
        ],
      ],
      'composer-patches present in stage and active' => [
        static::CONFIG_ALLOWED_PLUGIN | static::EXTRA_EXIT_ON_PATCH_FAILURE | static::REQUIRE_PACKAGE_FROM_ROOT,
        static::CONFIG_ALLOWED_PLUGIN | static::EXTRA_EXIT_ON_PATCH_FAILURE | static::REQUIRE_PACKAGE_FROM_ROOT,
        [],
        [],
      ],
      'composer-patches not present in stage and active' => [
        static::ABSENT,
        static::ABSENT,
        [],
        [],
      ],
    ];
  }

  /**
   * Tests the patcher's presence and configuration are validated on pre-apply.
   *
   * @param int $in_active
   *   Whether patcher is installed in active.
   * @param int $in_stage
   *   Whether patcher is installed in stage.
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   The expected validation results.
   *
   * @dataProvider providerErrorDuringPreApply
   */
  public function testErrorDuringPreApply(int $in_active, int $in_stage, array $expected_results): void {
    // Simulate in active.
    $active_manipulator = new ActiveFixtureManipulator();
    if ($in_active & static::CONFIG_ALLOWED_PLUGIN) {
      $active_manipulator->addConfig(['allow-plugins.cweagans/composer-patches' => TRUE]);
    }
    if ($in_active & static::EXTRA_EXIT_ON_PATCH_FAILURE) {
      $active_manipulator->addConfig(['extra.composer-exit-on-patch-failure' => TRUE]);
    }
    if ($in_active & static::REQUIRE_PACKAGE_FROM_ROOT) {
      $active_manipulator->requirePackage('cweagans/composer-patches', '@dev');
    }
    if ($in_active !== static::ABSENT) {
      $active_manipulator->commitChanges();
      $active_manipulator->updateLock();
    }

    // Simulate in stage.
    $stage_manipulator = $this->getStageFixtureManipulator();
    if ($in_stage & static::CONFIG_ALLOWED_PLUGIN) {
      $stage_manipulator->addConfig([
        'allow-plugins.cweagans/composer-patches' => TRUE,
      ]);
    }
    if ($in_stage & static::EXTRA_EXIT_ON_PATCH_FAILURE) {
      $stage_manipulator->addConfig([
        'extra.composer-exit-on-patch-failure' => TRUE,
      ]);
    }
    if ($in_stage & static::REQUIRE_PACKAGE_FROM_ROOT && !($in_active & static::REQUIRE_PACKAGE_FROM_ROOT)) {
      $stage_manipulator->requirePackage('cweagans/composer-patches', '1.7.333');
    }
    if (!($in_stage & static::REQUIRE_PACKAGE_FROM_ROOT) && $in_active & static::REQUIRE_PACKAGE_FROM_ROOT) {
      $stage_manipulator
        ->removePackage('cweagans/composer-patches');
    }
    if ($in_stage & static::REQUIRE_PACKAGE_INDIRECTLY) {
      $stage_manipulator->addPackage([
        'type' => 'package',
        'name' => 'dummy/depends-on-composer-patches',
        'description' => 'A dummy package depending on cweagans/composer-patches',
        'version' => '1.0.0',
        'require' => ['cweagans/composer-patches' => '*'],
      ]);
    }

    $stage = $this->createStage();
    $stage->create();
    $stage_dir = $stage->getStageDirectory();
    $stage->require(['drupal/core:9.8.1']);

    try {
      $stage->apply();
      // If we didn't get an exception, ensure we didn't expect any errors.
      $this->assertSame([], $expected_results);
    }
    catch (StageEventException $e) {
      $this->assertNotEmpty($expected_results);
      $this->assertValidationResultsEqual($expected_results, $e->event->getResults(), NULL, $stage_dir);
    }
  }

  /**
   * Tests that validation errors can carry links to help.
   *
   * @param int $in_active
   *   Whether patcher is installed in active.
   * @param int $in_stage
   *   Whether patcher is installed in stage.
   * @param \Drupal\package_manager\ValidationResult[] $expected_results
   *   The expected validation results.
   * @param string[] $help_page_sections
   *   An associative array of fragments (anchors) in the online help. The keys
   *   should be the numeric indices of the validation result messages which
   *   should link to those fragments.
   *
   * @dataProvider providerErrorDuringPreApply
   */
  public function testErrorDuringPreApplyWithHelp(int $in_active, int $in_stage, array $expected_results, array $help_page_sections): void {
    $this->enableModules(['help']);

    foreach ($expected_results as $result_index => $result) {
      $messages = $result->messages;

      foreach ($messages as $message_index => $message) {
        if ($help_page_sections[$message_index]) {
          // Get the link to the online documentation for the error message.
          $url = Url::fromRoute('help.page', ['name' => 'package_manager'])
            ->setOption('fragment', $help_page_sections[$message_index])
            ->toString();
          // Reformat the provided results so that they all have the link to the
          // online documentation appended to them.
          $messages[$message_index] = t('@message See <a href=":url">the help page</a> for information on how to resolve the problem.', ['@message' => $message, ':url' => $url]);
        }
      }
      $expected_results[$result_index] = ValidationResult::createError($messages, $result->summary);
    }
    $this->testErrorDuringPreApply($in_active, $in_stage, $expected_results);
  }

}
