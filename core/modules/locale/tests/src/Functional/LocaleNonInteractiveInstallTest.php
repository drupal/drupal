<?php

namespace Drupal\Tests\locale\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests installing in a different language with a non-dev version string.
 *
 * @group locale
 */
class LocaleNonInteractiveInstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Gets the version string to use in the translation file.
   *
   * @return string
   *   The version string to test, for example, '8.0.0' or '8.6.x'.
   */
  protected function getVersionStringToTest() {
    include_once $this->root . '/core/includes/install.core.inc';
    $version = _install_get_version_info(\Drupal::VERSION);
    return $version['major'] . '.0.0';
  }

  /**
   * {@inheritdoc}
   */
  protected function installParameters() {
    $parameters = parent::installParameters();
    // Install Drupal in German.
    $parameters['parameters']['langcode'] = 'de';
    // Create a po file so we don't attempt to download one from
    // localize.drupal.org and to have a test translation that will not change.
    \Drupal::service('file_system')->mkdir($this->publicFilesDirectory . '/translations', NULL, TRUE);
    $contents = <<<ENDPO
msgid ""
msgstr ""

msgid "Enter the password that accompanies your username."
msgstr "Geben sie das Passwort für ihren Benutzernamen ein."

ENDPO;
    $version = $this->getVersionStringToTest();
    file_put_contents($this->publicFilesDirectory . "/translations/drupal-{$version}.de.po", $contents);
    return $parameters;
  }

  /**
   * Tests that the expected translated text appears on the login screen.
   */
  public function testInstallerTranslations() {
    $this->drupalGet('user/login');
    $this->assertSession()->responseContains('Geben sie das Passwort für ihren Benutzernamen ein.');
  }

}
