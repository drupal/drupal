<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Language\LanguageManager;

// cspell:ignore xoxo

/**
 * Verifies that the installer language list uses local and remote languages.
 *
 * @group Installer
 */
class InstallerLanguagePageTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Installer step: Select language.
   */
  protected function setUpLanguage(): void {
    // Place a custom local translation in the translations directory.
    mkdir($this->root . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    touch($this->root . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.xoxo.po');

    // Check that all predefined languages show up with their native names.
    $this->visitInstaller();
    foreach (LanguageManager::getStandardLanguageList() as $langcode => $names) {
      $this->assertSession()->optionExists('edit-langcode', $langcode);
      $this->assertSession()->responseContains('>' . $names[1] . '<');
    }

    // Check that our custom one shows up with the file name indicated language.
    $this->assertSession()->optionExists('edit-langcode', 'xoxo');
    $this->assertSession()->responseContains('>xoxo<');

    parent::setUpLanguage();
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled(): void {
    $this->assertSession()->addressEquals('user/1');
    $this->assertSession()->statusCodeEquals(200);
  }

}
