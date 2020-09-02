<?php

namespace Drupal\FunctionalTests\Installer;

/**
 * Installs Drupal in German and checks resulting site.
 *
 * @group Installer
 *
 * @see \Drupal\FunctionalTests\Installer\InstallerTranslationTest
 */
class InstallerTranslationQueryTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Overrides the language code in which to install Drupal.
   *
   * @var string
   */
  protected $langcode = 'de';

  /**
   * {@inheritdoc}
   */
  protected function visitInstaller() {
    // Place a custom local translation in the translations directory.
    mkdir($this->root . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents($this->root . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.de.po', $this->getPo('de'));

    // The unrouted URL assembler does not exist at this point, so we build the
    // URL ourselves.
    $this->drupalGet($GLOBALS['base_url'] . '/core/install.php' . '?langcode=' . $this->langcode);

    // The language should have been automatically detected, all following
    // screens should be translated already.
    $elements = $this->xpath('//input[@type="submit"]/@value');
    $this->assertEqual(current($elements)->getText(), 'Save and continue de');
    $this->translations['Save and continue'] = 'Save and continue de';

    // Check the language direction.
    $direction = current($this->xpath('/@dir'))->getText();
    $this->assertEqual($direction, 'ltr');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage() {
    // The language was was preset by passing a query parameter in the URL, so
    // no explicit language selection is necessary.
  }

  /**
   * Verifies the expected behaviors of the installation result.
   */
  public function testInstaller() {
    $this->assertSession()->addressEquals('user/1');
    $this->assertSession()->statusCodeEquals(200);

    // Verify German was configured but not English.
    $this->drupalGet('admin/config/regional/language');
    $this->assertText('German');
    $this->assertNoText('English');
  }

  /**
   * Returns the string for the test .po file.
   *
   * @param string $langcode
   *   The language code.
   *
   * @return string
   *   Contents for the test .po file.
   */
  protected function getPo($langcode) {
    return <<<ENDPO
msgid ""
msgstr ""

msgid "Save and continue"
msgstr "Save and continue $langcode"
ENDPO;
  }

}
