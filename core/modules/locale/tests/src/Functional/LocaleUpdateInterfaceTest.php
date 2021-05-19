<?php

namespace Drupal\Tests\locale\Functional;

use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup;

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
  protected static $modules = ['locale_test_translate'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'administer modules',
      'administer site configuration',
      'administer languages',
      'access administration pages',
      'translate interface',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the user interfaces of the interface translation update system.
   *
   * Testing the Available updates summary on the side wide status page and the
   * Available translation updates page.
   */
  public function testInterface() {
    // No language added.
    // Check status page and Available translation updates page.
    $this->drupalGet('admin/reports/status');
    $this->assertNoText('Translation update status');

    $this->drupalGet('admin/reports/translations');
    $this->assertRaw(t('No translatable languages available. <a href=":add_language">Add a language</a> first.', [':add_language' => Url::fromRoute('entity.configurable_language.collection')->toString()]));

    // Add German language.
    $this->addLanguage('de');

    // Override Drupal core translation status as 'up-to-date'.
    $status = locale_translation_get_status();
    $status['drupal']['de']->type = 'current';
    \Drupal::keyValue('locale.translation_status')->set('drupal', $status['drupal']);

    // One language added, all translations up to date.
    $this->drupalGet('admin/reports/status');
    $this->assertText('Translation update status');
    $this->assertText('Up to date');
    $this->drupalGet('admin/reports/translations');
    $this->assertText('All translations up to date.');

    // Set locale_test_translate module to have a local translation available.
    $status = locale_translation_get_status();
    $status['locale_test_translate']['de']->type = 'local';
    \Drupal::keyValue('locale.translation_status')->set('locale_test_translate', $status['locale_test_translate']);

    // Check if updates are available for German.
    $this->drupalGet('admin/reports/status');
    $this->assertText('Translation update status');
    $this->assertRaw(t('Updates available for: @languages. See the <a href=":updates">Available translation updates</a> page for more information.', ['@languages' => t('German'), ':updates' => Url::fromRoute('locale.translate_status')->toString()]));
    $this->drupalGet('admin/reports/translations');
    $this->assertText('Updates for: Locale test translate');

    // Set locale_test_translate module to have a dev release and no
    // translation found.
    $status = locale_translation_get_status();
    $status['locale_test_translate']['de']->version = '1.3-dev';
    $status['locale_test_translate']['de']->type = '';
    \Drupal::keyValue('locale.translation_status')->set('locale_test_translate', $status['locale_test_translate']);

    // Check if no updates were found.
    $this->drupalGet('admin/reports/status');
    $this->assertText('Translation update status');
    $this->assertRaw(t('Missing translations for: @languages. See the <a href=":updates">Available translation updates</a> page for more information.', ['@languages' => t('German'), ':updates' => Url::fromRoute('locale.translate_status')->toString()]));
    $this->drupalGet('admin/reports/translations');
    $this->assertText('Missing translations for one project');
    $release_details = new FormattableMarkup('@module (@version). @info', [
      '@module' => 'Locale test translate',
      '@version' => '1.3-dev',
      '@info' => t('File not found at %local_path', ['%local_path' => 'core/modules/locale/tests/test.de.po']),
    ]);
    $this->assertRaw($release_details->__toString());

    // Override Drupal core translation status as 'no translations found'.
    $status = locale_translation_get_status();
    $status['drupal']['de']->type = '';
    $status['drupal']['de']->timestamp = 0;
    $status['drupal']['de']->version = '8.1.1';
    \Drupal::keyValue('locale.translation_status')->set('drupal', $status['drupal']);

    // Check if Drupal core is not translated.
    $this->drupalGet('admin/reports/translations');
    $this->assertText('Missing translations for 2 projects');
    $this->assertText('Drupal core (8.1.1).');

    // Override Drupal core translation status as 'translations available'.
    $status = locale_translation_get_status();
    $status['drupal']['de']->type = 'local';
    $status['drupal']['de']->files['local']->timestamp = REQUEST_TIME;
    $status['drupal']['de']->files['local']->info['version'] = '8.1.1';
    \Drupal::keyValue('locale.translation_status')->set('drupal', $status['drupal']);

    // Check if translations are available for Drupal core.
    $this->drupalGet('admin/reports/translations');
    $this->assertText('Updates for: Drupal core');
    $this->assertText('Drupal core (' . $this->container->get('date.formatter')->format(REQUEST_TIME, 'html_date') . ')');
    $this->assertSession()->buttonExists('Update translations');
  }

}
