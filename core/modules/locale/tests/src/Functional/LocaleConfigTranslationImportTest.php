<?php

namespace Drupal\Tests\locale\Functional;

use Drupal\locale\Locale;
use Drupal\Tests\BrowserTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests translation update's effects on configuration translations.
 *
 * @group locale
 */
class LocaleConfigTranslationImportTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language', 'locale_test_translate'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
  }

  /**
   * Tests update changes configuration translations if enabled after language.
   */
  public function testConfigTranslationImport() {
    $admin_user = $this->drupalCreateUser([
      'administer modules',
      'administer site configuration',
      'administer languages',
      'access administration pages',
      'administer permissions',
    ]);
    $this->drupalLogin($admin_user);

    // Add a language. The Afrikaans translation file of locale_test_translate
    // (test.af.po) has been prepared with a configuration translation.
    ConfigurableLanguage::createFromLangcode('af')->save();

    // Enable locale module.
    $this->container->get('module_installer')->install(['locale']);
    $this->resetAll();

    // Enable import of translations. By default this is disabled for automated
    // tests.
    $this->config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->set('translation.use_source', LOCALE_TRANSLATION_USE_SOURCE_LOCAL)
      ->save();

    // Add translation permissions now that the locale module has been enabled.
    $edit = [
      'authenticated[translate interface]' => 'translate interface',
    ];
    $this->drupalGet('admin/people/permissions');
    $this->submitForm($edit, 'Save permissions');

    // Check and update the translation status. This will import the Afrikaans
    // translations of locale_test_translate module.
    $this->drupalGet('admin/reports/translations/check');

    // Override the Drupal core translation status to be up to date.
    // Drupal core should not be a subject in this test.
    $status = locale_translation_get_status();
    $status['drupal']['af']->type = 'current';
    \Drupal::state()->set('locale.translation_status', $status);

    $this->drupalGet('admin/reports/translations');
    $this->submitForm([], 'Update translations');

    // Check if configuration translations have been imported.
    $override = \Drupal::languageManager()->getLanguageConfigOverride('af', 'system.maintenance');
    // cSpell:disable-next-line
    $this->assertEquals('Ons is tans besig met onderhoud op @site. Wees asseblief geduldig, ons sal binnekort weer terug wees.', $override->get('message'));

    // Ensure that \Drupal\locale\LocaleConfigSubscriber::onConfigSave() works
    // as expected during a configuration install that installs locale.
    /** @var \Drupal\Core\Config\FileStorage $sync */
    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($this->container->get('config.storage'), $sync);

    // Add our own translation to the config that will be imported.
    $af_sync = $sync->createCollection('language.af');
    $data = $af_sync->read('system.maintenance');
    $data['message'] = 'Test af message';
    $af_sync->write('system.maintenance', $data);

    // Uninstall locale module.
    $this->container->get('module_installer')->uninstall(['locale_test_translate']);
    $this->container->get('module_installer')->uninstall(['locale']);
    $this->resetAll();

    $this->configImporter()->import();

    $this->drupalGet('admin/reports/translations/check');
    $status = locale_translation_get_status();
    $status['drupal']['af']->type = 'current';
    \Drupal::state()->set('locale.translation_status', $status);
    $this->drupalGet('admin/reports/translations');
    $this->submitForm([], 'Update translations');

    // Check if configuration translations have been imported.
    $override = \Drupal::languageManager()->getLanguageConfigOverride('af', 'system.maintenance');
    $this->assertEquals('Test af message', $override->get('message'));
  }

  /**
   * Tests update changes configuration translations if enabled after language.
   */
  public function testConfigTranslationModuleInstall() {

    // Enable locale, block and config_translation modules.
    $this->container->get('module_installer')->install(['block', 'config_translation']);
    $this->resetAll();

    // The testing profile overrides locale.settings to disable translation
    // import. Test that this override is in place.
    $this->assertFalse($this->config('locale.settings')->get('translation.import_enabled'), 'Translations imports are disabled by default in the Testing profile.');

    $admin_user = $this->drupalCreateUser([
      'administer modules',
      'administer site configuration',
      'administer languages',
      'access administration pages',
      'administer permissions',
      'translate configuration',
    ]);
    $this->drupalLogin($admin_user);

    // Enable import of translations. By default this is disabled for automated
    // tests.
    $this->config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->set('translation.use_source', LOCALE_TRANSLATION_USE_SOURCE_LOCAL)
      ->save();

    // Add predefined language.
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm(['predefined_langcode' => 'af'], 'Add language');

    // Add the system branding block to the page.
    $this->drupalPlaceBlock('system_branding_block', ['region' => 'header', 'id' => 'site-branding']);
    $this->drupalGet('admin/config/system/site-information');
    $this->submitForm(['site_slogan' => 'Test site slogan'], 'Save configuration');
    $this->drupalGet('admin/config/system/site-information/translate/af/edit');
    $this->submitForm([
      'translation[config_names][system.site][slogan]' => 'Test site slogan in Afrikaans',
    ], 'Save translation');

    // Get the front page and ensure that the translated configuration appears.
    $this->drupalGet('af');
    $this->assertSession()->pageTextContains('Test site slogan in Afrikaans');

    $override = \Drupal::languageManager()->getLanguageConfigOverride('af', 'locale_test_translate.settings');
    $this->assertEquals('Locale can translate Afrikaans', $override->get('translatable_default_with_translation'));

    // Update test configuration.
    $override
      ->set('translatable_no_default', 'This translation is preserved')
      ->set('translatable_default_with_translation', 'This translation is preserved')
      ->set('translatable_default_with_no_translation', 'This translation is preserved')
      ->save();

    // Install any module.
    $this->drupalGet('admin/modules');
    $this->submitForm(['modules[dblog][enable]' => 'dblog'], 'Install');
    $this->assertSession()->pageTextContains('Module Database Logging has been enabled.');

    // Get the front page and ensure that the translated configuration still
    // appears.
    $this->drupalGet('af');
    $this->assertSession()->pageTextContains('Test site slogan in Afrikaans');

    $this->rebuildContainer();
    $override = \Drupal::languageManager()->getLanguageConfigOverride('af', 'locale_test_translate.settings');
    $expected = [
      'translatable_no_default' => 'This translation is preserved',
      'translatable_default_with_translation' => 'This translation is preserved',
      'translatable_default_with_no_translation' => 'This translation is preserved',
    ];
    $this->assertEquals($expected, $override->get());
  }

  /**
   * Tests removing a string from Locale deletes configuration translations.
   */
  public function testLocaleRemovalAndConfigOverrideDelete() {
    // Enable the locale module.
    $this->container->get('module_installer')->install(['locale']);
    $this->resetAll();

    $admin_user = $this->drupalCreateUser([
      'administer modules',
      'administer site configuration',
      'administer languages',
      'access administration pages',
      'administer permissions',
      'translate interface',
    ]);
    $this->drupalLogin($admin_user);

    // Enable import of translations. By default this is disabled for automated
    // tests.
    $this->config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->set('translation.use_source', LOCALE_TRANSLATION_USE_SOURCE_LOCAL)
      ->save();

    // Add predefined language.
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm(['predefined_langcode' => 'af'], 'Add language');

    $override = \Drupal::languageManager()->getLanguageConfigOverride('af', 'locale_test_translate.settings');
    $this->assertEquals(['translatable_default_with_translation' => 'Locale can translate Afrikaans'], $override->get());

    // Remove the string from translation to simulate a Locale removal. Note
    // that is no current way of doing this in the UI.
    $locale_storage = \Drupal::service('locale.storage');
    $string = $locale_storage->findString(['source' => 'Locale can translate']);
    \Drupal::service('locale.storage')->delete($string);
    // Force a rebuild of config translations.
    $count = Locale::config()->updateConfigTranslations(['locale_test_translate.settings'], ['af']);
    $this->assertEquals(1, $count, 'Correct count of updated translations');

    $override = \Drupal::languageManager()->getLanguageConfigOverride('af', 'locale_test_translate.settings');
    $this->assertEquals([], $override->get());
    $this->assertTrue($override->isNew(), 'The configuration override was deleted when the Locale string was deleted.');
  }

  /**
   * Tests removing a string from Locale changes configuration translations.
   */
  public function testLocaleRemovalAndConfigOverridePreserve() {
    // Enable the locale module.
    $this->container->get('module_installer')->install(['locale']);
    $this->resetAll();

    $admin_user = $this->drupalCreateUser([
      'administer modules',
      'administer site configuration',
      'administer languages',
      'access administration pages',
      'administer permissions',
      'translate interface',
    ]);
    $this->drupalLogin($admin_user);

    // Enable import of translations. By default this is disabled for automated
    // tests.
    $this->config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->set('translation.use_source', LOCALE_TRANSLATION_USE_SOURCE_LOCAL)
      ->save();

    // Add predefined language.
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm(['predefined_langcode' => 'af'], 'Add language');

    $override = \Drupal::languageManager()->getLanguageConfigOverride('af', 'locale_test_translate.settings');
    // Update test configuration.
    $override
      ->set('translatable_no_default', 'This translation is preserved')
      ->set('translatable_default_with_no_translation', 'This translation is preserved')
      ->save();
    $expected = [
      'translatable_default_with_translation' => 'Locale can translate Afrikaans',
      'translatable_no_default' => 'This translation is preserved',
      'translatable_default_with_no_translation' => 'This translation is preserved',
    ];
    $this->assertEquals($expected, $override->get());

    // Set the translated string to empty.
    $search = [
      'string' => 'Locale can translate',
      'langcode' => 'af',
      'translation' => 'all',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $textarea = $this->assertSession()->elementExists('xpath', '//textarea');
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => '',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($edit, 'Save translations');

    $override = \Drupal::languageManager()->getLanguageConfigOverride('af', 'locale_test_translate.settings');
    $expected = [
      'translatable_no_default' => 'This translation is preserved',
      'translatable_default_with_no_translation' => 'This translation is preserved',
    ];
    $this->assertEquals($expected, $override->get());
  }

  /**
   * Tests setting a non-English language as default and importing configuration.
   */
  public function testConfigTranslationWithNonEnglishLanguageDefault() {
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = $this->container->get('module_installer');
    ConfigurableLanguage::createFromLangcode('af')->save();

    $module_installer->install(['locale']);
    $this->resetAll();
    /** @var \Drupal\locale\StringStorageInterface $local_storage */
    $local_storage = $this->container->get('locale.storage');

    $source_string = 'Locale can translate';
    $translation_string = 'Locale can translate Afrikaans';

    // Create a translation for the "Locale can translate" string, this string
    // can be found in the "locale_test_translate" module's install config.
    $source = $local_storage->createString([
      'source' => $source_string,
    ])->save();
    $local_storage->createTranslation([
      'lid' => $source->getId(),
      'language' => 'af',
      'translation' => $translation_string,
    ])->save();

    // Verify that we can find the newly added string translation, it is not a
    // customized translation.
    $translation = $local_storage->findTranslation([
      'source' => $source_string,
      'language' => 'af',
    ]);
    $this->assertEquals($translation_string, $translation->getString());
    $this->assertFalse((bool) $translation->customized);

    // Uninstall the "locale_test_translate" module, verify that we can still
    // find the string translation.
    $module_installer->uninstall(['locale_test_translate']);
    $this->resetAll();
    $translation = $local_storage->findTranslation([
      'source' => $source_string,
      'language' => 'af',
    ]);
    $this->assertEquals($translation_string, $translation->getString());

    // Set the default language to "Afrikaans" and re-enable the
    // "locale_test_translate" module.
    $this->config('system.site')->set('default_langcode', 'af')->save();
    $module_installer->install(['locale_test_translate']);
    $this->resetAll();

    // Verify that enabling the "locale_test_translate" module didn't cause
    // the string translation to be overwritten.
    $translation = $local_storage->findTranslation([
      'source' => $source_string,
      'language' => 'af',
    ]);
    $this->assertEquals($translation_string, $translation->getString());
    $this->assertFalse((bool) $translation->customized);
  }

}
