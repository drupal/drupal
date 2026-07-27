<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\fixture_manipulator\ActiveFixtureManipulator;
use Drupal\fixture_manipulator\FixtureManipulator;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Event\PreRequireEvent;
use Drupal\package_manager\Exception\SandboxEventException;
use Drupal\package_manager\ValidationResult;
use Drupal\package_manager\Validator\PhpTufValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\package_manager\Validator\PhpTufValidator.
 *
 * @internal
 */
#[CoversClass(PhpTufValidator::class)]
#[Group('package_manager')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class PhpTufValidatorTest extends PackageManagerKernelTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // PHP-TUF must be enabled for this test to run.
    $this->setSetting('package_manager_bypass_tuf', FALSE);

    // audit.block-insecure is only available in Composer 2.9.2 onwards.
    try {
      (new ActiveFixtureManipulator())
        ->addConfig(['audit.block-insecure' => FALSE])
        ->commitChanges();
    }
    catch (\RuntimeException $e) {
      $this->assertStringContainsString('Setting audit.block-insecure does not exist', $e->getMessage());
    }

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
      $this->t('The <code>php-tuf/composer-integration</code> plugin is not installed.'),
      // Composer automatically removes the plugin from the `allow-plugins`
      // list when the plugin package is removed.
      $this->t('The <code>php-tuf/composer-integration</code> plugin is not listed as an allowed plugin.'),
    ];
    $result = ValidationResult::createError($messages, $this->t('The active directory is not protected by PHP-TUF, which is required to use Package Manager securely.'));
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
      $this->t('The <code>php-tuf/composer-integration</code> plugin is not installed.'),
      // Composer automatically removes the plugin from the `allow-plugins`
      // list when the plugin package is removed.
      $this->t('The <code>php-tuf/composer-integration</code> plugin is not listed as an allowed plugin.'),
    ];
    $result = ValidationResult::createError($messages, $this->t('The stage directory is not protected by PHP-TUF, which is required to use Package Manager securely.'));
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
      ->commitChanges($stage->getSandboxDirectory());

    $messages = [
      $this->t('The <code>php-tuf/composer-integration</code> plugin is not installed.'),
      // Composer automatically removes the plugin from the `allow-plugins`
      // list when the plugin package is removed.
      $this->t('The <code>php-tuf/composer-integration</code> plugin is not listed as an allowed plugin.'),
    ];
    $result = ValidationResult::createError($messages, $this->t('The stage directory is not protected by PHP-TUF, which is required to use Package Manager securely.'));
    try {
      $stage->apply();
      $this->fail('Expected an exception but none was thrown.');
    }
    catch (SandboxEventException $e) {
      $this->assertInstanceOf(PreApplyEvent::class, $e->event);
      $this->assertValidationResultsEqual([$result], $e->event->getResults());
    }
  }

}
