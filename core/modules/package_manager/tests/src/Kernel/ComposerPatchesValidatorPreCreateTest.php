<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\fixture_manipulator\ActiveFixtureManipulator;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\ValidationResult;
use Drupal\package_manager\Validator\ComposerPatchesValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Composer Patches Validator.
 *
 * @internal
 */
#[Group('package_manager')]
#[Group('#slow')]
#[CoversClass(ComposerPatchesValidator::class)]
#[RunTestsInSeparateProcesses]
class ComposerPatchesValidatorPreCreateTest extends PackageManagerKernelTestBase {

  use StringTranslationTrait;

  const ABSENT = 0;
  const CONFIG_ALLOWED_PLUGIN = 1;
  const EXTRA_EXIT_ON_PATCH_FAILURE = 2;
  const REQUIRE_PACKAGE_FROM_ROOT = 4;
  const REQUIRE_PACKAGE_INDIRECTLY = 8;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The composer-patches plugin is not allowed by default.
    $this->config('package_manager.settings')
      ->set('additional_trusted_composer_plugins', [
        'cweagans/composer-patches',
      ])
      ->save();
  }

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
   */
  #[DataProvider('providerErrorDuringPreCreate')]
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

}
