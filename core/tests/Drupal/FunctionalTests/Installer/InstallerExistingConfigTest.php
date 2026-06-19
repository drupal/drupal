<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore nmsgid nmsgstr enregistrer
/**
 * Verifies that installing from existing configuration works.
 */
#[Group('Installer')]
#[RunTestsInSeparateProcesses]
class InstallerExistingConfigTest extends InstallerConfigDirectoryTestBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment(): void {
    parent::prepareEnvironment();

    // Place a custom local translation in the translations directory.
    mkdir($this->root . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents($this->root . '/' . $this->siteDirectory . '/files/translations/drupal-' . \Drupal::VERSION . '.fr.po', "msgid \"\"\nmsgstr \"\"\nmsgid \"Save and continue\"\nmsgstr \"Enregistrer et continuer\"");

    // The configuration is from a site installed in French. The installer
    // therefore detects that the site must be installed in French, thus we
    // change the button translation.
    $this->translations['Save and continue'] = 'Enregistrer et continuer';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage(): void {
    // This step gets skipped because the config we're installing from was
    // created from a site installed in French, and the installer automatically
    // detects that.
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigLocation(): string {
    return __DIR__ . '/../../../fixtures/config_install/testing_config_install';
  }

}
