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
  public static $modules = ['language', 'locale_test_translate'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test update changes configuration translations if enabled after language.
   */
  public function testConfigTranslationImport() {
    $admin_user = $this->drupalCreateUser(['administer modules', 'administer site configuration', 'administer languages', 'access administration pages', 'administer permissions']);
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
    $this->drupalPostForm('admin/people/permissions', $edit, t('Save permissions'));

    // Check and update the translation status. This will import the Afrikaans
    // translations of locale_test_translate module.
    $this->drupalGet('admin/reports/translations/check');

    // Override the Drupal core translation status to be up to date.
    // Drupal core should not be a subject in this test.
    $status = locale_translation_get_status();
    $status['drupal']['af']->type = 'current';
    \Drupal::state()->set('locale.translation_status', $status);

    $this->drupalPostForm('admin/reports/translations', [], t('Update translations'));

    // Check if configuration translations have been imported.
    $override = \Drupal::languageManager()->getLanguageConfigOverride('af', 'system.maintenance');
    $this->assertEqual($override->get('message'), 'Ons is tans besig met onderhoud op @site. Wees asseblief geduldig, ons sal binnekort weer terug wees.');
  }

  /**
   * Test update changes configuration translations if enabled after language.
   */
  public function testConfigTranslationModuleInstall() {

    // Enable locale, block and config_translation modules.
    $this->container->get('module_installer')->install(['block', 'config_translation']);
    $this->resetAll();

    // The testing profile overrides locale.settings to disable translation
    // import. Test that this override is in place.
    $this->assertFalse($this->config('locale.settings')->get('translation.import_enabled'), 'Translations imports are disabled by default in the Testing profile.');

    $admin_user = $this->drupalCreateUser(['administer modules', 'administer site configuration', 'administer languages', 'access administration pages', 'administer permissions', 'translate configuration']);
    $this->drupalLogin($admin_user);

    // Enable import of translations. By default this is disabled for automated
    // tests.
    $this->config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->set('translation.use_source', LOCALE_TRANSLATION_USE_SOURCE_LOCAL)
      ->save();

    // Add predefined language.
    $this->drupalPostForm('admin/config/regional/language/add', ['predefined_langcode' => 'af'], t('Add language'));

    // Add the system branding block to the page.
    $this->drupalPlaceBlock('system_branding_block', ['region' => 'header', 'id' => 'site-branding']);
    $this->drupalPostForm('admin/config/system/site-information', ['site_slogan' => 'Test site slogan'], 'Save configuration');
    $this->drupalPostForm('admin/config/system/site-information/translate/af/edit', ['translation[config_names][system.site][slogan]' => 'Test site slogan in Afrikaans'], 'Save translation');

    // Get the front page and ensure that the translated configuration appears.
    $this->drupalGet('af');
    $this->assertText('Test site slogan in Afrikaans');

    $override = \Drupal::languageManager()->getLanguageConfigOverride('af', 'locale_test_translate.settings');
    $this->assertEqual('Locale can translate Afrikaans', $override->get('translatable_default_with_translation'));

    // Update test configuration.
    $override
      ->set('translatable_no_default', 'This translation is preserved')
      ->set('translatable_default_with_translation', 'This translation is preserved')
      ->set('translatable_default_with_no_translation', 'This translation is preserved')
      ->save();

    // Install any module.
    $this->drupalPostForm('admin/modules', ['modules[dblog][enable]' => 'dblog'], t('Install'));
    $this->assertText('Module Database Logging has been enabled.');

    // Get the front page and ensure that the translated configuration still
    // appears.
    $this->drupalGet('af');
    $this->assertText('Test site slogan in Afrikaans');

    $this->rebuildContainer();
    $override = \Drupal::languageManager()->getLanguageConfigOverride('af', 'locale_test_translate.settings');
    $expected = [
      'translatable_no_default' => 'This translation is preserved',
      'translatable_default_with_translation' => 'This translation is preserved',
      'translatable_default_with_no_translation' => 'This translation is preserved',
    ];
    $this->assertEqual($expected, $override->get());
  }

  /**
   * Test removing a string from Locale deletes configuration translations.
   */
  public function testLocaleRemovalAndConfigOverrideDelete() {
    // Enable the locale module.
    $this->container->get('module_installer')->install(['locale']);
    $this->resetAll();

    $admin_user = $this->drupalCreateUser(['administer modules', 'administer site configuration', 'administer languages', 'access administration pages', 'administer permissions', 'translate interface']);
    $this->drupalLogin($admin_user);

    // Enable import of translations. By default this is disabled for automated
    // tests.
    $this->config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->set('translation.use_source', LOCALE_TRANSLATION_USE_SOURCE_LOCAL)
      ->save();

    // Add predefined language.
    $this->drupalPostForm('admin/config/regional/language/add', ['predefined_langcode' => 'af'], t('Add language'));

    $override = \Drupal::languageManager()->getLanguageConfigOverride('af', 'locale_test_translate.settings');
    $this->assertEqual(['translatable_default_with_translation' => 'Locale can translate Afrikaans'], $override->get());

    // Remove the string from translation to simulate a Locale removal. Note
    // that is no current way of doing this in the UI.
    $locale_storage = \Drupal::service('locale.storage');
    $string = $locale_storage->findString(['source' => 'Locale can translate']);
    \Drupal::service('locale.storage')->delete($string);
    // Force a rebuild of config translations.
    $count = Locale::config()->updateConfigTranslations(['locale_test_translate.settings'], ['af']);
    $this->assertEqual($count, 1, 'Correct count of updated translations');

    $override = \Drupal::languageManager()->getLanguageConfigOverride('af', 'locale_test_translate.settings');
    $this->assertEqual([], $override->get());
    $this->assertTrue($override->isNew(), 'The configuration override was deleted when the Locale string was deleted.');
  }

  /**
   * Test removing a string from Locale changes configuration translations.
   */
  public function testLocaleRemovalAndConfigOverridePreserve() {
    // Enable the locale module.
    $this->container->get('module_installer')->install(['locale']);
    $this->resetAll();

    $admin_user = $this->drupalCreateUser(['administer modules', 'administer site configuration', 'administer languages', 'access administration pages', 'administer permissions', 'translate interface']);
    $this->drupalLogin($admin_user);

    // Enable import of translations. By default this is disabled for automated
    // tests.
    $this->config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->set('translation.use_source', LOCALE_TRANSLATION_USE_SOURCE_LOCAL)
      ->save();

    // Add predefined language.
    $this->drupalPostForm('admin/config/regional/language/add', ['predefined_langcode' => 'af'], t('Add language'));

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
    $this->assertEqual($expected, $override->get());

    // Set the translated string to empty.
    $search = [
      'string' => 'Locale can translate',
      'langcode' => 'af',
      'translation' => 'all',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $textareas = $this->xpath('//textarea');
    $textarea = current($textareas);
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => '',
    ];
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    $override = \Drupal::languageManager()->getLanguageConfigOverride('af', 'locale_test_translate.settings');
    $expected = [
      'translatable_no_default' => 'This translation is preserved',
      'translatable_default_with_no_translation' => 'This translation is preserved',
    ];
    $this->assertEqual($expected, $override->get());
  }

}
