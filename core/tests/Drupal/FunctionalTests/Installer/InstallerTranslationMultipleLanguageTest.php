<?php

namespace Drupal\FunctionalTests\Installer;

/**
 * Tests translation files for multiple languages get imported during install.
 *
 * @group Installer
 */
class InstallerTranslationMultipleLanguageTest extends InstallerTestBase {

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
  protected function setUpLanguage() {
    // Place custom local translations in the translations directory.
    mkdir(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.de.po', $this->getPo('de'));
    file_put_contents(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.es.po', $this->getPo('es'));

    parent::setUpLanguage();
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

msgid "Language"
msgstr "Language $langcode"

#: Testing site name configuration during the installer.
msgid "Drupal"
msgstr "Drupal"
ENDPO;
  }

  /**
   * {@inheritdoc}
   */
  protected function installParameters() {
    $params = parent::installParameters();
    $params['forms']['install_configure_form']['site_name'] = 'SITE_NAME_' . $this->langcode;
    return $params;
  }

  /**
   * Tests that translations ended up at the expected places.
   */
  public function testTranslationsLoaded() {
    // Ensure the title is correct.
    $this->assertEquals('SITE_NAME_' . $this->langcode, \Drupal::config('system.site')->get('name'));

    // Verify German and Spanish were configured.
    $this->drupalGet('admin/config/regional/language');
    $this->assertSession()->pageTextContains('German');
    $this->assertSession()->pageTextContains('Spanish');
    // If the installer was English or we used a profile that keeps English, we
    // expect that configured also. Otherwise English should not be configured
    // on the site.
    if ($this->langcode == 'en' || $this->profile == 'testing_multilingual_with_english') {
      $this->assertSession()->pageTextContains('English');
    }
    else {
      $this->assertSession()->pageTextNotContains('English');
    }

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

    if ($this->langcode == 'de') {
      // Active configuration should be in German and no German override should
      // exist.
      $this->assertEquals('Anonymous de', $config->get('anonymous'));
      $this->assertEquals('de', $config->get('langcode'));
      $this->assertTrue($override_de->isNew());

      if ($this->profile == 'testing_multilingual_with_english') {
        // English is already added in this profile. Should make the override
        // available.
        $this->assertEquals('Anonymous', $override_en->get('anonymous'));
      }
      else {
        // English is not yet available.
        $this->assertTrue($override_en->isNew());

        // Adding English should make the English override available.
        $edit = ['predefined_langcode' => 'en'];
        $this->drupalGet('admin/config/regional/language/add');
        $this->submitForm($edit, 'Add language');
        $override_en = $language_manager->getLanguageConfigOverride('en', 'user.settings');
        $this->assertEquals('Anonymous', $override_en->get('anonymous'));
      }

      // Activate a module, to make sure that config is not overridden by module
      // installation.
      $edit = [
        'modules[views][enable]' => TRUE,
        'modules[filter][enable]' => TRUE,
      ];
      $this->drupalGet('admin/modules');
      $this->submitForm($edit, 'Install');

      // Verify the strings from the translation are still as expected.
      $this->verifyImportedStringsTranslated();
    }
    else {
      // Active configuration should be English.
      $this->assertEquals('Anonymous', $config->get('anonymous'));
      $this->assertEquals('en', $config->get('langcode'));
      // There should not be an English override.
      $this->assertTrue($override_en->isNew());
      // German should be an override.
      $this->assertEquals('Anonymous de', $override_de->get('anonymous'));
    }

    // Spanish is always an override (never used as installation language).
    $this->assertEquals('Anonymous es', $override_es->get('anonymous'));

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
