<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Functional;

use Drupal\Core\Url;

/**
 * Tests for the user interface of project interface translations.
 *
 * @group locale
 */
class LocaleUpdateInterfaceTest extends LocaleUpdateBase {

  /**
   * {@inheritdoc}
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
  public function testInterface(): void {
    // No language added.
    // Check status page and Available translation updates page.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextNotContains('Translation update status');

    $this->drupalGet('admin/reports/translations');
    $this->assertSession()->pageTextContains("No translatable languages available. Add a language first.");
    $this->assertSession()->linkByHrefExists(Url::fromRoute('entity.configurable_language.collection')->toString());

    // Add German language.
    $this->addLanguage('de');

    // Override Drupal core translation status as 'up-to-date'.
    $status = locale_translation_get_status();
    $status['drupal']['de']->type = 'current';
    \Drupal::keyValue('locale.translation_status')->set('drupal', $status['drupal']);

    // One language added, all translations up to date.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContains('Translation update status');
    $this->assertSession()->pageTextContains('Up to date');
    $this->drupalGet('admin/reports/translations');
    $this->assertSession()->pageTextContains('All translations up to date.');

    // Set locale_test_translate module to have a local translation available.
    $status = locale_translation_get_status();
    $status['locale_test_translate']['de']->type = 'local';
    \Drupal::keyValue('locale.translation_status')->set('locale_test_translate', $status['locale_test_translate']);

    // Check if updates are available for German.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContains('Translation update status');
    $this->assertSession()->pageTextContains("Updates available for: German. See the Available translation updates page for more information.");
    $this->assertSession()->linkByHrefExists(Url::fromRoute('locale.translate_status')->toString());
    $this->drupalGet('admin/reports/translations');
    $this->assertSession()->pageTextContains('Updates for: Locale test translate');

    // Set locale_test_translate module to have a dev release and no
    // translation found.
    $status = locale_translation_get_status();
    $status['locale_test_translate']['de']->version = '1.3-dev';
    $status['locale_test_translate']['de']->type = '';
    \Drupal::keyValue('locale.translation_status')->set('locale_test_translate', $status['locale_test_translate']);

    // Check if no updates were found.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContains('Translation update status');
    $this->assertSession()->pageTextContains("Missing translations for: German. See the Available translation updates page for more information.");
    $this->assertSession()->linkByHrefExists(Url::fromRoute('locale.translate_status')->toString());
    $this->drupalGet('admin/reports/translations');
    $this->assertSession()->pageTextContains('Missing translations for one project');
    $this->assertSession()->pageTextContains('Locale test translate (1.3-dev). File not found at core/modules/locale/tests/test.de.po');

    // Override Drupal core translation status as 'no translations found'.
    $status = locale_translation_get_status();
    $status['drupal']['de']->type = '';
    $status['drupal']['de']->timestamp = 0;
    $status['drupal']['de']->version = '8.1.1';
    \Drupal::keyValue('locale.translation_status')->set('drupal', $status['drupal']);

    // Check if Drupal core is not translated.
    $this->drupalGet('admin/reports/translations');
    $this->assertSession()->pageTextContains('Missing translations for 2 projects');
    $this->assertSession()->pageTextContains('Drupal core (8.1.1).');

    // Override Drupal core translation status as 'translations available'.
    $status = locale_translation_get_status();
    $status['drupal']['de']->type = 'local';
    $status['drupal']['de']->files['local']->timestamp = \Drupal::time()->getRequestTime();
    $status['drupal']['de']->files['local']->info['version'] = '8.1.1';
    \Drupal::keyValue('locale.translation_status')->set('drupal', $status['drupal']);

    // Check if translations are available for Drupal core.
    $this->drupalGet('admin/reports/translations');
    $this->assertSession()->pageTextContains('Updates for: Drupal core');
    $this->assertSession()->pageTextContains('Drupal core (' . $this->container->get('date.formatter')->format(\Drupal::time()->getRequestTime(), 'html_date') . ')');
    $this->assertSession()->buttonExists('Update translations');
  }

}
