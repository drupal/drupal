<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\fixture_manipulator\ActiveFixtureManipulator;
use Drupal\package_manager\Event\PreApplyEvent;
use Drupal\package_manager\Event\PreCreateEvent;
use Drupal\package_manager\Exception\SandboxEventException;
use Drupal\package_manager\ValidationResult;

/**
 * @covers \Drupal\package_manager\Validator\ComposerPluginsValidator
 * @group package_manager
 * @internal
 */
class ComposerPluginsValidatorInsecureTest extends PackageManagerKernelTestBase {

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
    catch (SandboxEventException $e) {
      // Processing is required because the error message we get from Composer
      // contains multiple white spaces at the start or end of line.
      $this->assertStringContainsString($expected_message, preg_replace('/\s\s+/', '', $e->getMessage()));
      $this->assertInstanceOf(PreApplyEvent::class, $e->event);
    }
  }

}
