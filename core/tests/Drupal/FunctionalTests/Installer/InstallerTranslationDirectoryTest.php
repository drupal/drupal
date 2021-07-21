<?php

namespace Drupal\FunctionalTests\Installer;

/**
 * Installs Drupal in German with a non-default translation directory.
 *
 * @group Installer
 */
class InstallerTranslationDirectoryTest extends InstallerTestBase {

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
  protected function prepareEnvironment() {
    parent::prepareEnvironment();

    // Configure an invalid translations directory to test the resulting error.
    $this->settings['config']['locale.settings']['translation']['path'] = (object) [
      'value' => '/this/directory/does/not/exist',
      'required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage() {
    parent::setUpLanguage();

    $this->assertSession()->pageTextContains('Requirements problem');
    $this->assertSession()->pageTextContains('Errors found');
    $this->assertSession()->pageTextContains('Translations directory');
    $this->assertSession()->pageTextContains('The translations directory does not exist.');
    $this->assertSession()->pageTextContains('The installer requires that you create a translations directory as part of the installation process. Create the directory /this/directory/does/not/exist . More details about installing Drupal are available in INSTALL.txt.');

    // Prepare the custom translations directory outside of the files directory.
    $translation_directory = $this->root . '/' . $this->siteDirectory . '/custom_translations';
    mkdir($translation_directory, 0777, TRUE);
    // Place a custom local translation in the translations directory.
    file_put_contents($translation_directory . '/drupal-8.0.0.de.po', $this->getPo('de'));
    $this->settings['config']['locale.settings']['translation']['path'] = (object) [
      'value' => $translation_directory,
      'required' => TRUE,
    ];
    $this->writeSettings($this->settings);

    $this->clickLink('try again');

    // After selecting a different language than English, all following screens
    // should be translated already.
    $elements = $this->xpath('//input[@type="submit"]/@value');
    $this->assertSame(current($elements)->getText(), 'Save and continue de');
    $this->translations['Save and continue'] = 'Save and continue de';

    // Check the language direction.
    $direction = current($this->xpath('/@dir'))->getText();
    $this->assertSame($direction, 'ltr');
  }

  /**
   * Verifies the expected behaviors of the installation result.
   */
  public function testInstaller() {
    $assert_session = $this->assertSession();
    $assert_session->addressEquals('user/1');
    $assert_session->statusCodeEquals(200);

    // Verify German was configured but not English.
    $this->drupalGet('admin/config/regional/language');
    $assert_session->pageTextContains('German');
    $assert_session->pageTextNotContains('English');

    // Verify the strings from the translation files were imported.
    $this->drupalGet('admin/config/regional/translate');
    $test_samples = ['Save and continue', 'Anonymous'];
    foreach ($test_samples as $sample) {
      $edit = [];
      $edit['langcode'] = 'de';
      $edit['translation'] = 'translated';
      $edit['string'] = $sample;
      $this->submitForm($edit, t('Filter'));
      $assert_session->pageTextContains($sample . ' de');
    }
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

msgid "Anonymous"
msgstr "Anonymous $langcode"
ENDPO;
  }

}
