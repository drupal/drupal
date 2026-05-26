<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests installing in a different language with a non-dev version string.
 */
#[Group('locale')]
#[RunTestsInSeparateProcesses]
class LocaleNonInteractiveInstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
  }

  /**
   * Gets the version string to use in the translation file.
   *
   * @return string
   *   The version string to test, for example, '8.0.0' or '8.6.x'.
   */
  protected function getVersionStringToTest(): string {
    include_once $this->root . '/core/includes/install.core.inc';
    $version = _install_get_version_info(\Drupal::VERSION);
    return $version['major'] . '.0.0';
  }

  /**
   * {@inheritdoc}
   */
  protected function installParameters(): array {
    $parameters = parent::installParameters();
    // Install Drupal in German.
    $parameters['parameters']['langcode'] = 'de';
    // Create a po file so we don't attempt to download one from
    // localize.drupal.org and to have a test translation that will not change.
    \Drupal::service('file_system')->mkdir($this->publicFilesDirectory . '/translations', NULL, TRUE);
    $contents = <<<PO
msgid ""
msgstr ""

msgid "Log in"
msgstr "Anmelden"

PO;
    $version = $this->getVersionStringToTest();
    file_put_contents($this->publicFilesDirectory . "/translations/drupal-{$version}.de.po", $contents);
    return $parameters;
  }

  /**
   * Tests that the expected translated text appears on the login screen.
   */
  public function testInstallerTranslations(): void {
    $this->drupalGet('user/login');
    // cSpell:disable-next-line
    $this->assertSession()->responseContains('Anmelden');
  }

}
