<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Functional;

use Drupal\Component\Gettext\PoItem;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\WaitTerminateTestTrait;

/**
 * Tests LocaleLookup.
 *
 * @group locale
 */
class LocaleLocaleLookupTest extends BrowserTestBase {

  use WaitTerminateTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['locale', 'locale_test'];


  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The \Drupal\locale\LocaleTranslation service stores localization cache
    // data after the response is flushed to the client. We do not want to race
    // with any string translations that may be saving from the login below.
    $this->setWaitForTerminate();

    // Change the language default object to different values.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $this->config('system.site')->set('default_langcode', 'fr')->save();

    $this->drupalLogin($this->drupalCreateUser([
      'administer modules',
    ]));
  }

  /**
   * Tests that there are no circular dependencies.
   */
  public function testCircularDependency(): void {
    // Ensure that we can enable early_translation_test on a non-english site.
    $this->drupalGet('admin/modules');
    $this->submitForm(['modules[early_translation_test][enable]' => TRUE], 'Install');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests language fallback defaults.
   */
  public function testLanguageFallbackDefaults(): void {
    $this->drupalGet('');
    // Ensure state of fallback languages persisted by
    // locale_test_language_fallback_candidates_locale_lookup_alter() is empty.
    $this->assertEquals([], \Drupal::state()->get('locale.test_language_fallback_candidates_locale_lookup_alter_candidates'));
    // Make sure there is enough information provided for alter hooks.
    $context = \Drupal::state()->get('locale.test_language_fallback_candidates_locale_lookup_alter_context');
    $this->assertEquals('fr', $context['langcode']);
    $this->assertEquals('locale_lookup', $context['operation']);
  }

  /**
   * Tests old plural style @count[number] fix.
   *
   * @dataProvider providerTestFixOldPluralStyle
   */
  public function testFixOldPluralStyle($translation_value, $expected): void {
    $string_storage = \Drupal::service('locale.storage');
    $string = $string_storage->findString(['source' => 'Member for', 'context' => '']);
    $lid = $string->getId();
    $string_storage->createTranslation([
      'lid' => $lid,
      'language' => 'fr',
      'translation' => $translation_value,
    ])->save();
    _locale_refresh_translations(['fr'], [$lid]);

    // Check that 'count[2]' was fixed for render value.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains($expected);

    // Check that 'count[2]' was saved for source value.
    $translation = $string_storage->findTranslation(['language' => 'fr', 'lid' => $lid])->translation;
    $this->assertSame($translation_value, $translation, 'Source value not changed');
    $this->assertStringContainsString('@count[2]', $translation, 'Source value contains @count[2]');
  }

  /**
   * Provides data for testFixOldPluralStyle().
   *
   * @return array
   *   An array of test data:
   *     - translation value
   *     - expected result
   */
  public static function providerTestFixOldPluralStyle() {
    return [
      'non-plural translation' => ['@count[2] non-plural test', '@count[2] non-plural test'],
      'plural translation' => ['@count[2] plural test' . PoItem::DELIMITER, '@count plural test'],
    ];
  }

}
