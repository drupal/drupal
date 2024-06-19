<?php

declare(strict_types=1);

namespace Drupal\Tests\config_translation\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the content translation behaviors on date formats.
 *
 * @group config_translation
 */
class ConfigTranslationDateFormatUiTest extends BrowserTestBase {

  protected static $modules = [
    'language',
    'config_translation',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable additional languages.
    $langcodes = ['de', 'es'];
    foreach ($langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    $user = $this->drupalCreateUser([
      'administer site configuration',
      'translate configuration',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests date format translation behavior.
   */
  public function testDateFormatUI(): void {
    $this->drupalGet('admin/config/regional/date-time');

    // Assert translation link unlocked date format.
    $this->assertSession()->linkByHrefExists('admin/config/regional/date-time/formats/manage/medium/translate');

    // Assert translation link locked date format.
    $this->assertSession()->linkByHrefExists('admin/config/regional/date-time/formats/manage/html_datetime/translate');

    // Date pattern is visible on unlocked date formats.
    $this->drupalGet('admin/config/regional/date-time/formats/manage/medium/translate/de/add');
    $this->assertSession()->fieldExists('translation[config_names][core.date_format.medium][pattern]');

    // Date pattern is not visible on locked date formats.
    $this->drupalGet('admin/config/regional/date-time/formats/manage/html_datetime/translate/es/add');
    $this->assertSession()->fieldNotExists('translation[config_names][core.date_format.html_datetime][pattern]');
  }

  /**
   * Tests date format translation.
   */
  public function testDateFormatTranslation(): void {

    $this->drupalGet('admin/config/regional/date-time');

    // Check for medium format.
    $this->assertSession()->linkByHrefExists('admin/config/regional/date-time/formats/manage/medium');

    // Save default language configuration for a new format.
    $edit = [
      'label' => 'Custom medium date',
      'id' => 'custom_medium',
      'date_format_pattern' => 'Y. m. d. H:i',
    ];
    $this->drupalGet('admin/config/regional/date-time/formats/add');
    $this->submitForm($edit, 'Add format');

    // Test translating a default shipped format and our custom format.
    $formats = [
      'medium' => 'Default medium date',
      'custom_medium' => 'Custom medium date',
    ];
    foreach ($formats as $id => $label) {
      $translation_base_url = 'admin/config/regional/date-time/formats/manage/' . $id . '/translate';

      $this->drupalGet($translation_base_url);

      // 'Add' link should be present for German translation.
      $translation_page_url = "$translation_base_url/de/add";
      $this->assertSession()->linkByHrefExists($translation_page_url);

      // Make sure original text is present on this page.
      $this->drupalGet($translation_page_url);
      $this->assertSession()->pageTextContains($label);

      // Make sure that the date library is added.
      $this->assertSession()->responseContains('core/modules/system/js/system.date.js');

      // Update translatable fields.
      $edit = [
        'translation[config_names][core.date_format.' . $id . '][label]' => $id . ' - DE',
        'translation[config_names][core.date_format.' . $id . '][pattern]' => 'D',
      ];

      // Save language specific version of form.
      $this->drupalGet($translation_page_url);
      $this->submitForm($edit, 'Save translation');

      // Get translation and check we've got the right value.
      $override = \Drupal::languageManager()->getLanguageConfigOverride('de', 'core.date_format.' . $id);
      $expected = [
        'label' => $id . ' - DE',
        'pattern' => 'D',
      ];
      $this->assertEquals($expected, $override->get());

      // Formatting the date 8 / 27 / 1985 @ 13:37 EST with pattern D should
      // display "Tue".
      $formatted_date = $this->container->get('date.formatter')->format(494015820, $id, NULL, 'America/New_York', 'de');
      $this->assertEquals('Tue', $formatted_date, 'Got the right formatted date using the date format translation pattern.');
    }
  }

}
