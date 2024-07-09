<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Component\Serialization\Yaml;

// cSpell:ignore Anónimo Aplicar

/**
 * Verifies that installing from existing configuration works.
 *
 * @group Installer
 */
class InstallerExistingConfigSyncDirectoryMultilingualTest extends InstallerConfigDirectoryTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_config_install_multilingual';

  /**
   * {@inheritdoc}
   */
  protected $existingSyncDirectory = TRUE;

  /**
   * Installer step: Select installation profile.
   */
  protected function setUpProfile() {
    // Ensure the site name 'Multilingual' appears as expected in the 'Use
    // existing configuration' radio description.
    $this->assertSession()->pageTextContains('Install Multilingual using existing configuration.');
    return parent::setUpProfile();
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigLocation() {
    return __DIR__ . '/../../../fixtures/config_install/multilingual';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    // Place custom local translations in the translations directory and fix up
    // configuration.
    mkdir($this->publicFilesDirectory . '/translations', 0777, TRUE);
    file_put_contents($this->publicFilesDirectory . '/translations/drupal-8.0.0.es.po', $this->getPo('es'));
    $locale_settings = Yaml::decode(file_get_contents($this->siteDirectory . '/config/sync/locale.settings.yml'));
    $locale_settings['translation']['use_source'] = 'local';
    $locale_settings['translation']['path'] = $this->publicFilesDirectory . '/translations';
    file_put_contents($this->siteDirectory . '/config/sync/locale.settings.yml', Yaml::encode($locale_settings));
  }

  /**
   * Confirms that the installation installed the configuration correctly.
   */
  public function testConfigSync(): void {
    $comparer = $this->configImporter()->getStorageComparer();
    $expected_changelist_default_collection = [
      'create' => [],
      // The system.mail is changed configuration because the test system
      // changes it to ensure that mails are not sent.
      'update' => ['system.mail'],
      'delete' => [],
      'rename' => [],
    ];
    $this->assertEquals($expected_changelist_default_collection, $comparer->getChangelist());
    $expected_changelist_spanish_collection = [
      'create' => [],
      // The view was untranslated but the translation exists so the installer
      // performs the translation.
      'update' => ['views.view.who_s_new'],
      'delete' => [],
      'rename' => [],
    ];
    $this->assertEquals($expected_changelist_spanish_collection, $comparer->getChangelist(NULL, 'language.es'));

    // Ensure that menu blocks have been created correctly.
    $this->assertSession()->responseNotContains('This block is broken or missing.');
    $this->assertSession()->linkExists('Add content');

    // Ensure that the Spanish translation of anonymous is the one from
    // configuration and not the PO file.
    // cspell:disable-next-line
    $this->assertSame('Anónimo', \Drupal::languageManager()->getLanguageConfigOverride('es', 'user.settings')->get('anonymous'));

    /** @var \Drupal\locale\StringStorageInterface $locale_storage */
    $locale_storage = \Drupal::service('locale.storage');
    // If configuration contains a translation that is not in the po file then
    // _install_config_locale_overrides_process_batch() will create a customized
    // translation.
    $translation = $locale_storage->findTranslation(['source' => 'Anonymous', 'language' => 'es']);
    $this->assertSame('Anónimo', $translation->getString());
    $this->assertTrue((bool) $translation->customized, "A customized translation has been created for Anonymous");

    // If configuration contains a translation that is in the po file then
    // _install_config_locale_overrides_process_batch() will not create a
    // customized translation.
    $translation = $locale_storage->findTranslation(['source' => 'Apply', 'language' => 'es']);
    $this->assertSame('Aplicar', $translation->getString());
    $this->assertFalse((bool) $translation->customized, "A non-customized translation has been created for Apply");

    /** @var \Drupal\language\Config\LanguageConfigOverride $view_config */
    // Ensure that views are translated as expected.
    $view_config = \Drupal::languageManager()->getLanguageConfigOverride('es', 'views.view.who_s_new');
    $this->assertSame('Aplicar', $view_config->get('display.default.display_options.exposed_form.options.submit_button'));
    $view_config = \Drupal::languageManager()->getLanguageConfigOverride('es', 'views.view.archive');
    $this->assertSame('Aplicar', $view_config->get('display.default.display_options.exposed_form.options.submit_button'));

    // Manually update the translation status so can re-run the import.
    $status = locale_translation_get_status();
    $status['drupal']['es']->type = 'local';
    $status['drupal']['es']->files['local']->timestamp = time();
    \Drupal::keyValue('locale.translation_status')->set('drupal', $status['drupal']);
    // Run the translation import.
    $this->drupalGet('admin/reports/translations');
    $this->submitForm([], 'Update translations');

    // Ensure that only the config we expected to have changed has.
    $comparer = $this->configImporter()->getStorageComparer();
    $expected_changelist_spanish_collection = [
      'create' => [],
      // The view was untranslated but the translation exists so the installer
      // performs the translation.
      'update' => ['views.view.who_s_new'],
      'delete' => [],
      'rename' => [],
    ];
    $this->assertEquals($expected_changelist_spanish_collection, $comparer->getChangelist(NULL, 'language.es'));

    // Change a translation and ensure configuration is updated.
    $po = <<<PO
msgid ""
msgstr ""

msgid "Anonymous"
msgstr "Anonymous es"

msgid "Apply"
msgstr "Aplicar New"

PO;
    file_put_contents($this->publicFilesDirectory . '/translations/drupal-8.0.0.es.po', $po);

    // Manually update the translation status so can re-run the import.
    $status = locale_translation_get_status();
    $status['drupal']['es']->type = 'local';
    $status['drupal']['es']->files['local']->timestamp = time();
    \Drupal::keyValue('locale.translation_status')->set('drupal', $status['drupal']);
    // Run the translation import.
    $this->drupalGet('admin/reports/translations');
    $this->submitForm([], 'Update translations');

    $translation = \Drupal::service('locale.storage')->findTranslation(['source' => 'Apply', 'language' => 'es']);
    $this->assertSame('Aplicar New', $translation->getString());
    $this->assertFalse((bool) $translation->customized, "A non-customized translation has been created for Apply");

    // Ensure that only the config we expected to have changed has.
    $comparer = $this->configImporter()->getStorageComparer();
    $expected_changelist_spanish_collection = [
      'create' => [],
      // All views with 'Aplicar' will have been changed to use the new
      // translation.
      'update' => [
        'views.view.archive',
        'views.view.content_recent',
        'views.view.frontpage',
        'views.view.glossary',
        'views.view.who_s_new',
        'views.view.who_s_online',
      ],
      'delete' => [],
      'rename' => [],
    ];
    $this->assertEquals($expected_changelist_spanish_collection, $comparer->getChangelist(NULL, 'language.es'));
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

msgid "Anonymous"
msgstr "Anonymous $langcode"

msgid "Apply"
msgstr "Aplicar"

PO;
  }

}
