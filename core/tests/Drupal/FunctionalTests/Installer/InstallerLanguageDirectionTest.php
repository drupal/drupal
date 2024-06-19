<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

/**
 * Verifies that the early installer uses the correct language direction.
 *
 * @group Installer
 */
class InstallerLanguageDirectionTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Overrides the language code the installer should use.
   *
   * @var string
   */
  protected $langcode = 'ar';

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage() {
    // Place a custom local translation in the translations directory.
    mkdir($this->root . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents($this->root . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.ar.po', "msgid \"\"\nmsgstr \"\"\nmsgid \"Save and continue\"\nmsgstr \"Save and continue Arabic\"");

    parent::setUpLanguage();
    // After selecting a different language than English, all following screens
    // should be translated already.
    $this->assertSession()->buttonExists('Save and continue Arabic');
    $this->translations['Save and continue'] = 'Save and continue Arabic';

    // Verify that language direction is right-to-left.
    $this->assertSession()->elementTextEquals('xpath', '/@dir', 'rtl');
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled(): void {
    $this->assertSession()->addressEquals('user/1');
    $this->assertSession()->statusCodeEquals(200);
  }

}
