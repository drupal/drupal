<?php

namespace Drupal\config_translation\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the content translation behaviours on date formats.
 *
 * @group config_translation
 */
class ConfigTranslationDateFormatUiTest extends WebTestBase {

  public static $modules = array(
    'language',
    'config_translation',
    'system'
  );

  protected function setUp() {
    parent::setUp();

    // Enable additional languages.
    $langcodes = ['de', 'es'];
    foreach ($langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    $user = $this->drupalCreateUser(array(
      'administer site configuration',
      'translate configuration',
    ));
    $this->drupalLogin($user);
  }

  /**
   * Tests date format translation behaviour.
   */
  public function testDateFormatUI() {
    $this->drupalGet('admin/config/regional/date-time');

    // Assert translation link unlocked date format.
    $this->assertLinkByHref('admin/config/regional/date-time/formats/manage/medium/translate');

    // Assert translation link locked date format.
    $this->assertLinkByHref('admin/config/regional/date-time/formats/manage/html_datetime/translate');

    // Date pattern is visible on unlocked date formats.
    $this->drupalGet('admin/config/regional/date-time/formats/manage/medium/translate/de/add');
    $this->assertField('translation[config_names][core.date_format.medium][pattern]');

    // Date pattern is not visible on locked date formats.
    $this->drupalGet('admin/config/regional/date-time/formats/manage/html_datetime/translate/es/add');
    $this->assertNoField('translation[config_names][core.date_format.html_datetime][pattern]');
  }

}
