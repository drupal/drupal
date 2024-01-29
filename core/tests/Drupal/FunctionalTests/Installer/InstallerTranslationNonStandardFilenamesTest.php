<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests non-standard named translation files get imported during install.
 *
 * @group Installer
 */
class InstallerTranslationNonStandardFilenamesTest extends InstallerTranslationMultipleLanguageNonInteractiveTest {

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    BrowserTestBase::prepareEnvironment();
    // Place custom local translations in the translations directory.
    mkdir(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations/drupal.de.po', $this->getPo('de'));
    file_put_contents(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations/drupal.es.po', $this->getPo('es'));
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareSettings() {
    parent::prepareSettings();
    $settings['config']['locale.settings']['translation']['default_filename'] = (object) [
      'value' => '%project.%language.po',
      'required' => TRUE,
    ];
    $settings['config']['locale.settings']['translation']['default_server_pattern'] = (object) [
      'value' => 'translations://%project.%language.po',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

}
