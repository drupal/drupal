<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\fixture_manipulator\ActiveFixtureManipulator;
use Drupal\package_manager\ValidationResult;
use Drupal\package_manager\Validator\ComposerValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Composer Validator.
 *
 * @internal
 */
#[Group('package_manager')]
#[CoversClass(ComposerValidator::class)]
#[RunTestsInSeparateProcesses]
class ComposerValidatorHelpTest extends PackageManagerKernelTestBase {

  use StringTranslationTrait;

  /**
   * Data provider for ::testLinkToOnlineHelp().
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerLinkToOnlineHelp(): array {
    return [
      'TLS disabled' => [
        ['disable-tls' => TRUE],
        [
          t('TLS must be enabled for HTTPS Composer downloads. See <a href="/admin/help/package_manager#package-manager-requirements">the help page</a> for more information on how to configure Composer to download packages securely.'),
          t('You should also check the value of <code>secure-http</code> and make sure that it is set to <code>true</code> or not set at all.'),
        ],
      ],
      'secure-http is off' => [
        ['secure-http' => FALSE],
        [
          t('HTTPS must be enabled for Composer downloads. See <a href="/admin/help/package_manager#package-manager-requirements">the help page</a> for more information on how to configure Composer to download packages securely.'),
        ],
      ],
    ];
  }

  /**
   * Tests that invalid configuration links to online help, if available.
   *
   * @param array $config
   *   The Composer configuration to set.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[] $expected_messages
   *   The expected validation error messages.
   */
  #[DataProvider('providerLinkToOnlineHelp')]
  public function testLinkToOnlineHelp(array $config, array $expected_messages): void {
    $this->enableModules(['help']);
    (new ActiveFixtureManipulator())->addConfig($config)->commitChanges();

    $result = ValidationResult::createError($expected_messages, $this->t("Composer settings don't satisfy Package Manager's requirements."));
    $this->assertStatusCheckResults([$result]);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Ensure that any warnings arising from Composer settings (which we expect
    // in this test) will not fail the test during tear-down.
    $this->failureLogger->reset();
    parent::tearDown();
  }

}
