<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

// cspell:ignore nmsgid nmsgstr enregistrer

/**
 * Verifies that installing from existing configuration works.
 *
 * @group Installer
 */
class InstallerExistingConfigTest extends InstallerConfigDirectoryTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage(): void {
    // Place a custom local translation in the translations directory.
    mkdir($this->root . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents($this->root . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.fr.po', "msgid \"\"\nmsgstr \"\"\nmsgid \"Save and continue\"\nmsgstr \"Enregistrer et continuer\"");
    parent::setUpLanguage();
  }

  /**
   * {@inheritdoc}
   */
  public function setUpSettings(): void {
    // The configuration is from a site installed in French.
    // So after selecting the profile the installer detects that the site must
    // be installed in French, thus we change the button translation.
    $this->translations['Save and continue'] = 'Enregistrer et continuer';
    parent::setUpSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigLocation(): string {
    return __DIR__ . '/../../../fixtures/config_install/testing_config_install';
  }

}
