<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Tests\BrowserTestBase;

// cspell:ignore montag

/**
 * Tests translation files for multiple languages get imported during install.
 *
 * @group Installer
 */
class InstallerTranslationMultipleLanguageNonInteractiveTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Switch to the multilingual testing profile.
   *
   * @var string
   */
  protected $profile = 'testing_multilingual';

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    // Place custom local translations in the translations directory.
    mkdir(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.de.po', $this->getPo('de'));
    file_put_contents(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.es.po', $this->getPo('es'));
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
    return <<<PO
msgid ""
msgstr ""

msgid "Save and continue"
msgstr "Save and continue $langcode"

msgid "Anonymous"
msgstr "Anonymous $langcode"

msgid "Language"
msgstr "Language $langcode"

#: Testing site name configuration during the installer.
msgid "Drupal"
msgstr "Drupal"
PO;
  }

  /**
   * {@inheritdoc}
   */
  protected function installParameters() {
    $params = parent::installParameters();
    $params['forms']['install_configure_form']['site_name'] = 'SITE_NAME_en';
    return $params;
  }

  /**
   * Tests that translations ended up at the expected places.
   */
  public function testTranslationsLoaded() {
    $this->drupalLogin($this->createUser([], NULL, TRUE));
    // Ensure the title is correct.
    $this->assertEquals('SITE_NAME_en', \Drupal::config('system.site')->get('name'));

    // Verify German and Spanish were configured.
    $this->drupalGet('admin/config/regional/language');
    $this->assertSession()->pageTextContains('German');
    $this->assertSession()->pageTextContains('Spanish');
    // If the installer was English or we used a profile that keeps English, we
    // expect that configured also. Otherwise English should not be configured
    // on the site.
    $this->assertSession()->pageTextContains('English');

    // Verify the strings from the translation files were imported.
    $this->verifyImportedStringsTranslated();

    /** @var \Drupal\language\ConfigurableLanguageManager $language_manager */
    $language_manager = \Drupal::languageManager();

    // If the site was installed in a foreign language (only tested with German
    // in subclasses), then the active configuration should be updated and no
    // override should exist in German. Otherwise the German translation should
    // end up in overrides the same way as Spanish (which is not used as a site
    // installation language). English should be available based on profile
    // information and should be possible to add if not yet added, making
    // English overrides available.

    $config = \Drupal::config('user.settings');
    $override_de = $language_manager->getLanguageConfigOverride('de', 'user.settings');
    $override_en = $language_manager->getLanguageConfigOverride('en', 'user.settings');
    $override_es = $language_manager->getLanguageConfigOverride('es', 'user.settings');

    // Active configuration should be English.
    $this->assertEquals('Anonymous', $config->get('anonymous'));
    $this->assertEquals('en', $config->get('langcode'));
    // There should not be an English override.
    $this->assertTrue($override_en->isNew());
    // German should be an override.
    $this->assertEquals('Anonymous de', $override_de->get('anonymous'));

    // Spanish is always an override (never used as installation language).
    $this->assertEquals('Anonymous es', $override_es->get('anonymous'));

    // Test translation from locale_test module.
    $this->assertEquals('Montag', t('Monday', [], ['langcode' => 'de']));
  }

  /**
   * Helper function to verify that the expected strings are translated.
   */
  protected function verifyImportedStringsTranslated() {
    $test_samples = ['Save and continue', 'Anonymous', 'Language'];
    $langcodes = ['de', 'es'];

    foreach ($test_samples as $sample) {
      foreach ($langcodes as $langcode) {
        $edit = [];
        $edit['langcode'] = $langcode;
        $edit['translation'] = 'translated';
        $edit['string'] = $sample;
        $this->drupalGet('admin/config/regional/translate');
        $this->submitForm($edit, 'Filter');
        $this->assertSession()->pageTextContains($sample . ' ' . $langcode);
      }
    }
  }

}
