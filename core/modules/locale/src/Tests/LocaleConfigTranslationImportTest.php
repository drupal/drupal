<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleConfigTranslationImportTest.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Core\Url;

/**
 * Tests translation update's effects on configuration translations.
 *
 * @group locale
 */
class LocaleConfigTranslationImportTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'locale_test_translate');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(array('administer modules', 'administer site configuration', 'administer languages', 'access administration pages', 'administer permissions'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Test update changes configuration translations if enabled after language.
   */
  public function testConfigTranslationImport() {

    // Add a language. The Afrikaans translation file of locale_test_translate
    // (test.af.po) has been prepared with a configuration translation.
    ConfigurableLanguage::createFromLangcode('af')->save();

    // Enable locale module.
    $this->container->get('module_installer')->install(array('locale'));
    $this->resetAll();

    // Enable import of translations. By default this is disabled for automated
    // tests.
    $this->config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->save();

    // Add translation permissions now that the locale module has been enabled.
    $edit = array(
      'authenticated[translate interface]' => 'translate interface',
    );
    $this->drupalPostForm('admin/people/permissions', $edit, t('Save permissions'));

    // Check and update the translation status. This will import the Afrikaans
    // translations of locale_test_translate module.
    $this->drupalGet('admin/reports/translations/check');

    // Override the Drupal core translation status to be up to date.
    // Drupal core should not be a subject in this test.
    $status = locale_translation_get_status();
    $status['drupal']['af']->type = 'current';
    \Drupal::state()->set('locale.translation_status', $status);

    $this->drupalPostForm('admin/reports/translations', array(), t('Update translations'));

    // Check if configuration translations have been imported.
    $override =  \Drupal::languageManager()->getLanguageConfigOverride('af', 'system.maintenance');
    $this->assertEqual($override->get('message'), 'Ons is tans besig met onderhoud op @site. Wees asseblief geduldig, ons sal binnekort weer terug wees.');
  }

}
