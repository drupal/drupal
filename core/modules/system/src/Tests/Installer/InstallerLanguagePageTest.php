<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\InstallerLanguagePageTest.
 */

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\InstallerTestBase;

/**
 * Verifies that the installer language list combines local and remote languages.
 *
 * @group Installer
 */
class InstallerLanguagePageTest extends InstallerTestBase {

  /**
   * Installer step: Select language.
   */
  protected function setUpLanguage() {
    // Place a custom local translation in the translations directory.
    mkdir(\Drupal::root() . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    touch(\Drupal::root() . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.xoxo.po');

    // Check that all predefined languages show up with their native names.
    $this->drupalGet($GLOBALS['base_url'] . '/core/install.php');
    foreach (\Drupal::languageManager()->getStandardLanguageList() as $langcode => $names) {
      $this->assertOption('edit-langcode', $langcode);
      $this->assertRaw('>' . $names[1] . '<');
    }

    // Check that our custom one shows up with the file name indicated language.
    $this->assertOption('edit-langcode', 'xoxo');
    $this->assertRaw('>xoxo<');

    parent::setUpLanguage();
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);
  }

}
