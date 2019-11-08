<?php

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
    $elements = $this->xpath('//input[@type="submit"]/@value');
    $this->assertEqual(current($elements)->getText(), 'Save and continue Arabic');
    $this->translations['Save and continue'] = 'Save and continue Arabic';

    // Verify that language direction is right-to-left.
    $direction = current($this->xpath('/@dir'))->getText();
    $this->assertEqual($direction, 'rtl');
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);
  }

}
