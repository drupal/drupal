<?php

/**
 * @file
 * Contains Drupal\locale\Tests\LocaleUpdateInterfaceTest.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the user interface of project interface translations.
 *
 * @group locale
 */
class LocaleUpdateInterfaceTest extends LocaleUpdateBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('update', 'locale', 'locale_test_translate');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $admin_user = $this->drupalCreateUser(array('administer modules', 'administer site configuration', 'administer languages', 'access administration pages', 'translate interface'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the user interfaces of the interface translation update system.
   *
   * Testing the Available updates summary on the side wide status page and the
   * Avaiable translation updates page.
   */
  public function testInterface() {
    // No language added.
    // Check status page and Available translation updates page.
    $this->drupalGet('admin/reports/status');
    $this->assertNoText(t('Translation update status'), 'No status message');

    $this->drupalGet('admin/reports/translations');
    $this->assertRaw(t('No translatable languages available. <a href="@add_language">Add a language</a> first.', array('@add_language' => url('admin/config/regional/language'))), 'Language message');

    // Add German language.
    $this->addLanguage('de');

    // Drupal core is probably in 8.x, but tests may also be executed with
    // stable releases. As this is an uncontrolled factor in the test, we will
    // mark Drupal core as translated and continue with the prepared modules.
    $status = locale_translation_get_status();
    $status['drupal']['de']->type = 'current';
    \Drupal::state()->set('locale.translation_status', $status);

    // One language added, all translations up to date.
    $this->drupalGet('admin/reports/status');
    $this->assertText(t('Translation update status'), 'Status message');
    $this->assertText(t('Up to date'), 'Translations up to date');
    $this->drupalGet('admin/reports/translations');
    $this->assertText(t('All translations up to date.'), 'Translations up to date');

    // Set locale_test_translate module to have a local translation available.
    $status = locale_translation_get_status();
    $status['locale_test_translate']['de']->type = 'local';
    \Drupal::state()->set('locale.translation_status', $status);

    // Check if updates are available for German.
    $this->drupalGet('admin/reports/status');
    $this->assertText(t('Translation update status'), 'Status message');
    $this->assertRaw(t('Updates available for: @languages. See the <a href="@updates">Available translation updates</a> page for more information.', array('@languages' => t('German'), '@updates' => url('admin/reports/translations'))), 'Updates available message');
    $this->drupalGet('admin/reports/translations');
    $this->assertText(t('Updates for: @modules', array('@modules' => 'Locale test translate')), 'Translations avaiable');

    // Set locale_test_translate module to have a dev release and no
    // translation found.
    $status = locale_translation_get_status();
    $status['locale_test_translate']['de']->version = '1.3-dev';
    $status['locale_test_translate']['de']->type = '';
    \Drupal::state()->set('locale.translation_status', $status);

    // Check if no updates were found.
    $this->drupalGet('admin/reports/status');
    $this->assertText(t('Translation update status'), 'Status message');
    $this->assertRaw(t('Missing translations for: @languages. See the <a href="@updates">Available translation updates</a> page for more information.', array('@languages' => t('German'), '@updates' => url('admin/reports/translations'))), 'Missing translations message');
    $this->drupalGet('admin/reports/translations');
    $this->assertText(t('Missing translations for one project'), 'No translations found');
    $this->assertText(t('@module (@version).', array('@module' => 'Locale test translate', '@version' => '1.3-dev')), 'Release details');
    $this->assertText(t('No translation files are provided for development releases.'), 'Release info');
  }

}
