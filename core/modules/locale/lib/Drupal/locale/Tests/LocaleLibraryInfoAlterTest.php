<?php
/**
 * @file
 * Definition of Drupal\locale\Tests\LocaleLibraryInfoAlterTest.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests localization of the JavaScript libraries.
 *
 * Currently, only the jQuery datepicker is localized using Drupal translations.
 */
class LocaleLibraryInfoAlterTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Javascript library localisation',
      'description' => 'Tests localization of the JavaScript libraries.',
      'group' => 'Locale',
    );
  }

  function setUp() {
    parent::setUp(array('locale'));
  }

  /**
     * Verifies that the datepicker can be localized.
     *
     * @see locale_library_info_alter()
     */
  public function testLibraryInfoAlter() {
    drupal_add_library('system', 'jquery.ui.datepicker');
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, 'locale.datepicker.js'), t('locale.datepicker.js added to scripts.'));
  }
}
