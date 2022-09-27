<?php

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
  public function testDateFormatUI() {
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

}
