<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\fixture_manipulator\ActiveFixtureManipulator;
use Drupal\fixture_manipulator\FixtureManipulator;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Exception\StageEventException;
use Drupal\package_manager\ValidationResult;
use Drupal\package_manager\Validator\LockFileValidator;
use Drupal\package_manager\Validator\PhpTufValidator;

/**
 * @coversDefaultClass \Drupal\package_manager\Validator\PhpTufValidator
 * @group package_manager
 * @group #slow
 * @internal
 */
class PhpTufValidatorTest extends PackageManagerKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // PHP-TUF must be enabled for this test to run.
    $this->setSetting('package_manager_bypass_tuf', FALSE);

    (new ActiveFixtureManipulator())
      ->addConfig([
        'repositories.drupal' => [
          'type' => 'composer',
          'url' => 'https://packages.drupal.org/8',
          'tuf' => TRUE,
        ],
        'allow-plugins.' . PhpTufValidator::PLUGIN_NAME => TRUE,
      ])
      ->addPackage([
        'name' => PhpTufValidator::PLUGIN_NAME,
        'type' => 'composer-plugin',
        'require' => [
          'composer-plugin-api' => '*',
        ],
        'extra' => [
          'class' => 'PhpTufComposerPlugin',
        ],
      ])
      ->commitChanges()
      ->updateLock();
  }

  /**
   * Tests that there are no errors if the plugin is set up correctly.
   */
  public function testPluginInstalledAndConfiguredProperly(): void {
    $this->assertStatusCheckResults([]);
    $this->assertResults([]);
  }

  /**
   * Tests there is an error if the plugin is not installed in the project root.
   */
  public function testPluginNotInstalledInProjectRoot(): void {
    (new ActiveFixtureManipulator())
      ->removePackage(PhpTufValidator::PLUGIN_NAME)
      ->commitChanges()
      ->updateLock();

    $messages = [
      t('The <code>php-tuf/composer-integration</code> plugin is not installed.'),
      // Composer automatically removes the plugin from the `allow-plugins`
      // list when the plugin package is removed.
      t('The <code>php-tuf/composer-integration</code> plugin is not listed as an allowed plugin.'),
    ];
    $result = ValidationResult::createError($messages, t('The active directory is not protected by PHP-TUF, which is required to use Package Manager securely.'));
    $this->assertStatusCheckResults([$result]);
    $this->assertResults([$result], PreCreateEvent::class);
  }

  /**
   * Tests removing the plugin from the stage on pre-require.
   */
  public function testPluginRemovedFromStagePreRequire(): void {
    $this->getStageFixtureManipulator()
      ->removePackage(PhpTufValidator::PLUGIN_NAME);

    $messages = [
      t('The <code>php-tuf/composer-integration</code> plugin is not installed.'),
      // Composer automatically removes the plugin from the `allow-plugins`
      // list when the plugin package is removed.
      t('The <code>php-tuf/composer-integration</code> plugin is not listed as an allowed plugin.'),
    ];
    $result = ValidationResult::createError($messages, t('The stage directory is not protected by PHP-TUF, which is required to use Package Manager securely.'));
    $this->assertResults([$result], PreRequireEvent::class);
  }

  /**
   * Tests removing the plugin from the stage before applying it.
   */
  public function testPluginRemovedFromStagePreApply(): void {
    $stage = $this->createStage();
    $stage->create();
    $stage->require(['ext-json:*']);

    (new FixtureManipulator())
      ->removePackage(PhpTufValidator::PLUGIN_NAME)
      ->commitChanges($stage->getStageDirectory());

    $messages = [
      t('The <code>php-tuf/composer-integration</code> plugin is not installed.'),
      // Composer automatically removes the plugin from the `allow-plugins`
      // list when the plugin package is removed.
      t('The <code>php-tuf/composer-integration</code> plugin is not listed as an allowed plugin.'),
    ];
    $result = ValidationResult::createError($messages, t('The stage directory is not protected by PHP-TUF, which is required to use Package Manager securely.'));
    try {
      $stage->apply();
      $this->fail('Expected an exception but none was thrown.');
    }
    catch (StageEventException $e) {
      $this->assertInstanceOf(PreApplyEvent::class, $e->event);
      $this->assertValidationResultsEqual([$result], $e->event->getResults());
    }
  }

  /**
   * Data provider for testing invalid plugin configuration.
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerInvalidConfiguration(): array {
    return [
      'plugin specifically disallowed' => [
        [
          'allow-plugins.' . PhpTufValidator::PLUGIN_NAME => FALSE,
        ],
        [
          t('The <code>php-tuf/composer-integration</code> plugin is not listed as an allowed plugin.'),
        ],
      ],
      'all plugins disallowed' => [
        [
          'allow-plugins' => FALSE,
        ],
        [
          t('The <code>php-tuf/composer-integration</code> plugin is not listed as an allowed plugin.'),
        ],
      ],
      'packages.drupal.org not using TUF' => [
        [
          'repositories.drupal' => [
            'type' => 'composer',
            'url' => 'https://packages.drupal.org/8',
          ],
        ],
        [
          t('TUF is not enabled for the <code>https://packages.drupal.org/8</code> repository.'),
        ],
      ],
    ];
  }

  /**
   * Data provider for testing invalid plugin configuration in the stage.
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerInvalidConfigurationInStage(): \Generator {
    foreach (static::providerInvalidConfiguration() as $name => $arguments) {
      $arguments[] = PreRequireEvent::class;
      yield "$name on pre-require" => $arguments;

      array_splice($arguments, -1, NULL, PreApplyEvent::class);
      yield "$name on pre-apply" => $arguments;
    }
  }

  /**
   * Tests errors caused by invalid plugin configuration in the project root.
   *
   * @param array $config
   *   The Composer configuration to set.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[] $expected_messages
   *   The expected error messages.
   *
   * @dataProvider providerInvalidConfiguration
   */
  public function testInvalidConfigurationInProjectRoot(array $config, array $expected_messages): void {
    (new ActiveFixtureManipulator())->addConfig($config)->commitChanges()->updateLock();

    $result = ValidationResult::createError($expected_messages, t('The active directory is not protected by PHP-TUF, which is required to use Package Manager securely.'));
    $this->assertStatusCheckResults([$result]);
    $this->assertResults([$result], PreCreateEvent::class);
  }

  /**
   * Tests errors caused by invalid plugin configuration in the stage directory.
   *
   * @param array $config
   *   The Composer configuration to set.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[] $expected_messages
   *   The expected error messages.
   * @param string $event_class
   *   The event before which the plugin's configuration should be changed.
   *
   * @dataProvider providerInvalidConfigurationInStage
   */
  public function testInvalidConfigurationInStage(array $config, array $expected_messages, string $event_class): void {
    $listener = function (PreRequireEvent|PreApplyEvent $event) use ($config): void {
      (new FixtureManipulator())
        ->addConfig($config)
        ->commitChanges($event->stage->getStageDirectory())
        ->updateLock();
    };
    $this->addEventTestListener($listener, $event_class);

    // LockFileValidator will complain because we have not added, removed, or
    // updated any packages in the stage. In this very specific situation, it's
    // okay to disable that validator to remove the interference.
    if ($event_class === PreApplyEvent::class) {
      $lock_file_validator = $this->container->get(LockFileValidator::class);
      $this->container->get('event_dispatcher')
        ->removeSubscriber($lock_file_validator);
    }

    $result = ValidationResult::createError($expected_messages, t('The stage directory is not protected by PHP-TUF, which is required to use Package Manager securely.'));
    $this->assertResults([$result], $event_class);
  }

}
