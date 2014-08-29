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
 *
 * @group locale
 */
class LocaleLibraryInfoAlterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale');

  /**
   * Verifies that the datepicker can be localized.
   *
   * @see locale_library_info_alter()
   */
  public function testLibraryInfoAlter() {
    $attached['#attached']['library'][] = 'core/jquery.ui.datepicker';
    drupal_render($attached);
    drupal_process_attached($attached);
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, 'locale.datepicker.js'), 'locale.datepicker.js added to scripts.');
  }
}
