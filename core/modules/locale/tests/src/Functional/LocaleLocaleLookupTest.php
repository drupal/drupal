<?php

namespace Drupal\Tests\locale\Functional;

use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests LocaleLookup.
 *
 * @group locale
 */
class LocaleLocaleLookupTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['locale', 'locale_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Change the language default object to different values.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $this->config('system.site')->set('default_langcode', 'fr')->save();

    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests that there are no circular dependencies.
   */
  public function testCircularDependency() {
    // Ensure that we can enable early_translation_test on a non-english site.
    $this->drupalPostForm('admin/modules', ['modules[early_translation_test][enable]' => TRUE], t('Install'));
    $this->assertResponse(200);
  }

  /**
   * Test language fallback defaults.
   */
  public function testLanguageFallbackDefaults() {
    $this->drupalGet('');
    // Ensure state of fallback languages persisted by
    // locale_test_language_fallback_candidates_locale_lookup_alter() is empty.
    $this->assertEqual(\Drupal::state()->get('locale.test_language_fallback_candidates_locale_lookup_alter_candidates'), []);
    // Make sure there is enough information provided for alter hooks.
    $context = \Drupal::state()->get('locale.test_language_fallback_candidates_locale_lookup_alter_context');
    $this->assertEqual($context['langcode'], 'fr');
    $this->assertEqual($context['operation'], 'locale_lookup');
  }

  /**
   * Test old plural style @count[number] fix.
   *
   * @dataProvider providerTestFixOldPluralStyle
   */
  public function testFixOldPluralStyle($translation_value, $expected) {
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
    $this->assertNotFalse(strpos($translation, '@count[2]'), 'Source value contains @count[2]');
  }

  /**
   * Provides data for testFixOldPluralStyle().
   *
   * @return array
   *   An array of test data:
   *     - translation value
   *     - expected result
   */
  public function providerTestFixOldPluralStyle() {
    return [
      'non-plural translation' => ['@count[2] non-plural test', '@count[2] non-plural test'],
      'plural translation' => ['@count[2] plural test' . PluralTranslatableMarkup::DELIMITER, '@count plural test'],
    ];
  }

}
