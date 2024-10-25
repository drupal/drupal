<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\fixture_manipulator\ActiveFixtureManipulator;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Exception\StageEventException;
use Drupal\package_manager\ValidationResult;

/**
 * @covers \Drupal\package_manager\Validator\ComposerPluginsValidator
 * @group package_manager
 * @internal
 */
class ComposerPluginsValidatorTest extends PackageManagerKernelTestBase {

  /**
   * Tests `config.allow-plugins: true` fails validation during pre-create.
   */
  public function testInsecureConfigurationFailsValidationPreCreate(): void {
    $active_manipulator = new ActiveFixtureManipulator();
    $active_manipulator->addConfig(['allow-plugins' => TRUE]);
    $active_manipulator->commitChanges();

    $expected_results = [
      ValidationResult::createError(
        [
          new TranslatableMarkup('All composer plugins are allowed because <code>config.allow-plugins</code> is configured to <code>true</code>. This is an unacceptable security risk.'),
        ],
      ),
    ];
    $this->assertStatusCheckResults($expected_results);
    $this->assertResults($expected_results, PreCreateEvent::class);
  }

  /**
   * Tests `config.allow-plugins: true` fails validation during pre-apply.
   */
  public function testInsecureConfigurationFailsValidationPreApply(): void {
    $stage_manipulator = $this->getStageFixtureManipulator();
    $stage_manipulator->addConfig(['allow-plugins' => TRUE]);

    $expected_results = [
      ValidationResult::createError(
        [
          new TranslatableMarkup('All composer plugins are allowed because <code>config.allow-plugins</code> is configured to <code>true</code>. This is an unacceptable security risk.'),
        ],
      ),
    ];
    $this->assertResults($expected_results, PreApplyEvent::class);
  }

  /**
   * Tests composer plugins are validated during pre-create.
   *
   * @dataProvider providerSimpleValidCases
   * @dataProvider providerSimpleInvalidCases
   * @dataProvider providerComplexInvalidCases
   */
  public function testValidationDuringPreCreate(array $composer_config_to_add, array $packages_to_add, array $expected_results): void {
    $active_manipulator = new ActiveFixtureManipulator();
    if ($composer_config_to_add) {
      $active_manipulator->addConfig($composer_config_to_add);
    }
    foreach ($packages_to_add as $package) {
      $active_manipulator->addPackage($package);
    }
    $active_manipulator->commitChanges();

    $this->assertStatusCheckResults($expected_results);
    $this->assertResults($expected_results, PreCreateEvent::class);
  }

  /**
   * Tests composer plugins are validated during pre-apply.
   *
   * @dataProvider providerSimpleValidCases
   * @dataProvider providerSimpleInvalidCases
   * @dataProvider providerComplexInvalidCases
   */
  public function testValidationDuringPreApply(array $composer_config_to_add, array $packages_to_add, array $expected_results): void {
    $stage_manipulator = $this->getStageFixtureManipulator();
    if ($composer_config_to_add) {
      $stage_manipulator->addConfig($composer_config_to_add);
    }
    foreach ($packages_to_add as $package) {
      $stage_manipulator->addPackage($package);
    }

    // Ensure \Drupal\package_manager\Validator\SupportedReleaseValidator does
    // not complain.
    $release_fixture_folder = __DIR__ . '/../../fixtures/release-history';
    $this->setReleaseMetadata([
      'semver_test' => "$release_fixture_folder/semver_test.1.1.xml",
    ]);

    $this->assertResults($expected_results, PreApplyEvent::class);
  }

  /**
   * Tests adding a plugin that's not allowed by the allow-plugins config.
   *
   * The exception that this test looks for is not necessarily triggered by
   * ComposerPluginsValidator; Composer will exit with an error if there is an
   * installed plugin that is not allowed by the `allow-plugins` config. In
   * practice, this means that whichever validator is the first one to do a
   * Composer operation (via ComposerInspector) will get the exception -- it
   * may or may not be ComposerPluginsValidator.
   *
   * This test is here to ensure that Composer's behavior remains consistent,
   * even if we're not explicitly testing ComposerPluginsValidator here.
   */
  public function testAddDisallowedPlugin(): void {
    $this->getStageFixtureManipulator()
      ->addPackage([
        'name' => 'composer/plugin-c',
        'version' => '16.4',
        'type' => 'composer-plugin',
        'require' => ['composer-plugin-api' => '*'],
        'extra' => ['class' => 'AnyClass'],
      ]);

    $expected_message = "composer/plugin-c contains a Composer plugin which is blocked by your allow-plugins config.";
    $stage = $this->createStage();
    $stage->create();
    $stage->require(['drupal/core:9.8.1']);
    try {
      // We are trying to add package plugin-c but not allowing it in config,
      // so we expect the operation to fail on PreApplyEvent.
      $stage->apply();
    }
    catch (StageEventException $e) {
      // Processing is required because the error message we get from Composer
      // contains multiple white spaces at the start or end of line.
      $this->assertStringContainsString($expected_message, preg_replace('/\s\s+/', '', $e->getMessage()));
      $this->assertInstanceOf(PreApplyEvent::class, $e->event);
    }
  }

  /**
   * Tests additional composer plugins can be trusted during pre-create.
   *
   * @dataProvider providerSimpleInvalidCases
   * @dataProvider providerComplexInvalidCases
   */
  public function testValidationAfterTrustingDuringPreCreate(array $composer_config_to_add, array $packages_to_add, array $expected_results): void {
    $expected_results_without_composer_plugin_violations = array_filter(
      $expected_results,
      fn (ValidationResult $v) => !$v->summary || !str_contains(strtolower($v->summary->getUntranslatedString()), 'unsupported composer plugin'),
    );

    // Trust all added packages.
    $this->config('package_manager.settings')
      ->set('additional_trusted_composer_plugins', array_map(fn (array $package) => $package['name'], $packages_to_add))
      ->save();

    // Reuse the test logic that does not trust additional packages, but with
    // updated expected results.
    $this->testValidationDuringPreCreate($composer_config_to_add, $packages_to_add, $expected_results_without_composer_plugin_violations);
  }

  /**
   * Tests additional composer plugins can be trusted during pre-apply.
   *
   * @dataProvider providerSimpleInvalidCases
   * @dataProvider providerComplexInvalidCases
   */
  public function testValidationAfterTrustingDuringPreApply(array $composer_config_to_add, array $packages_to_add, array $expected_results): void {
    $expected_results_without_composer_plugin_violations = array_filter(
      $expected_results,
      fn (ValidationResult $v) => !$v->summary || !str_contains(strtolower($v->summary->getUntranslatedString()), 'unsupported composer plugin'),
    );

    // Trust all added packages.
    $this->config('package_manager.settings')
      ->set('additional_trusted_composer_plugins', array_map(fn (array $package) => $package['name'], $packages_to_add))
      ->save();

    // Reuse the test logic that does not trust additional packages, but with
    // updated expected results.
    $this->testValidationDuringPreApply($composer_config_to_add, $packages_to_add, $expected_results_without_composer_plugin_violations);
  }

  /**
   * Generates simple test cases.
   *
   * @return \Generator
   */
  public static function providerSimpleValidCases(): \Generator {
    yield 'no composer plugins' => [
      [],
      [
        [
          'name' => "drupal/semver_test",
          'version' => '8.1.0',
          'type' => 'drupal-module',
        ],
      ],
      [],
    ];

    yield 'another supported composer plugin' => [
      [
        'allow-plugins.drupal/core-vendor-hardening' => TRUE,
      ],
      [
        [
          'name' => 'drupal/core-vendor-hardening',
          'version' => '9.8.0',
          'type' => 'composer-plugin',
          'require' => ['composer-plugin-api' => '*'],
          'extra' => ['class' => 'AnyClass'],
        ],
      ],
      [],
    ];

    yield 'a supported composer plugin for which any version is supported: party like it is Drupal 99!' => [
      [
        'allow-plugins.drupal/core-composer-scaffold' => TRUE,
      ],
      [
        [
          'name' => 'drupal/core-composer-scaffold',
          'version' => '99.0.0',
          'type' => 'composer-plugin',
          'require' => ['composer-plugin-api' => '*'],
          'extra' => ['class' => 'AnyClass'],
        ],
      ],
      [],
    ];

    yield 'one UNsupported but disallowed plugin — pretty package name' => [
      [
        'allow-plugins.composer/plugin-a' => FALSE,
      ],
      [
        [
          'name' => 'composer/plugin-a',
          'version' => '6.1',
          'type' => 'composer-plugin',
          'require' => ['composer-plugin-api' => '*'],
          'extra' => ['class' => 'AnyClass'],
        ],
      ],
      [],
    ];

    yield 'one UNsupported but disallowed plugin — normalized package name' => [
      [
        'allow-plugins.composer/plugin-b' => FALSE,
      ],
      [
        [
          'name' => 'composer/plugin-b',
          'version' => '20.1',
          'type' => 'composer-plugin',
          'require' => ['composer-plugin-api' => '*'],
          'extra' => ['class' => 'AnyClass'],
        ],
      ],
      [],
    ];
  }

  /**
   * Generates simple invalid test cases.
   *
   * @return \Generator
   */
  public static function providerSimpleInvalidCases(): \Generator {
    yield 'one UNsupported composer plugin — pretty package name' => [
      [
        'allow-plugins.not-cweagans/not-composer-patches' => TRUE,
      ],
      [
        [
          'name' => 'not-cweagans/not-composer-patches',
          'require' => ['composer-plugin-api' => '*'],
          'extra' => ['class' => 'AnyClass'],
          'version' => '6.1',
          'type' => 'composer-plugin',
        ],
      ],
      [
        ValidationResult::createError(
          [
            new TranslatableMarkup('<code>not-cweagans/not-composer-patches</code>'),
          ],
          new TranslatableMarkup('An unsupported Composer plugin was detected.'),
        ),
      ],
    ];

    yield 'one UNsupported composer plugin — normalized package name' => [
      [
        'allow-plugins.also-not-cweagans/also-not-composer-patches' => TRUE,
      ],
      [
        [
          'name' => 'also-not-cweagans/also-not-composer-patches',
          'version' => '20.1',
          'type' => 'composer-plugin',
          'require' => ['composer-plugin-api' => '*'],
          'extra' => ['class' => 'AnyClass'],
        ],
      ],
      [
        ValidationResult::createError(
          [
            new TranslatableMarkup('<code>also-not-cweagans/also-not-composer-patches</code>'),
          ],
          new TranslatableMarkup('An unsupported Composer plugin was detected.'),
        ),
      ],
    ];

    yield 'one supported composer plugin but incompatible version — newer version' => [
      [
        'allow-plugins.phpstan/extension-installer' => TRUE,
      ],
      [
        [
          'name' => 'phpstan/extension-installer',
          'version' => '20.1',
          'type' => 'composer-plugin',
          'require' => ['composer-plugin-api' => '*'],
          'extra' => ['class' => 'AnyClass'],
        ],
      ],
      [
        ValidationResult::createError(
          [
            new TranslatableMarkup('<code>phpstan/extension-installer</code> is supported, but only version <code>^1.1</code>, found <code>20.1</code>.'),
          ],
          new TranslatableMarkup('An unsupported Composer plugin was detected.'),
        ),
      ],
    ];

    yield 'one supported composer plugin but incompatible version — older version' => [
      [
        'allow-plugins.dealerdirect/phpcodesniffer-composer-installer' => TRUE,
      ],
      [
        [
          'name' => 'dealerdirect/phpcodesniffer-composer-installer',
          'version' => '0.6.1',
          'type' => 'composer-plugin',
          'require' => ['composer-plugin-api' => '*'],
          'extra' => ['class' => 'AnyClass'],
        ],
      ],
      [
        ValidationResult::createError(
          [
            new TranslatableMarkup('<code>dealerdirect/phpcodesniffer-composer-installer</code> is supported, but only version <code>^0.7.1 || ^1.0.0</code>, found <code>0.6.1</code>.'),
          ],
          new TranslatableMarkup('An unsupported Composer plugin was detected.'),
        ),
      ],
    ];
  }

  /**
   * Generates complex invalid test cases based on the simple test cases.
   *
   * @return \Generator
   */
  public static function providerComplexInvalidCases(): \Generator {
    $valid_cases = iterator_to_array(static::providerSimpleValidCases());
    $invalid_cases = iterator_to_array(static::providerSimpleInvalidCases());
    $all_config = NestedArray::mergeDeepArray(
      // First key-value pair for each simple test case: the packages it adds.
      array_map(fn (array $c) => $c[0], $valid_cases + $invalid_cases)
    );
    $all_packages = NestedArray::mergeDeepArray(
      // Second key-value pair for each simple test case: the packages it adds.
      array_map(fn (array $c) => $c[1], $valid_cases + $invalid_cases)
    );

    yield 'complex combination' => [
      $all_config,
      $all_packages,
      [
        ValidationResult::createError(
          [
            new TranslatableMarkup('<code>not-cweagans/not-composer-patches</code>'),
            new TranslatableMarkup('<code>also-not-cweagans/also-not-composer-patches</code>'),
            new TranslatableMarkup('<code>phpstan/extension-installer</code> is supported, but only version <code>^1.1</code>, found <code>20.1</code>.'),
            new TranslatableMarkup('<code>dealerdirect/phpcodesniffer-composer-installer</code> is supported, but only version <code>^0.7.1 || ^1.0.0</code>, found <code>0.6.1</code>.'),
          ],
          new TranslatableMarkup('Unsupported Composer plugins were detected.'),
        ),
      ],
    ];
  }

}
