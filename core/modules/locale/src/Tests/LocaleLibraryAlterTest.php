<?php
/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleLibraryAlterTest.
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
class LocaleLibraryAlterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale');

  /**
   * Verifies that the datepicker can be localized.
   *
   * @see locale_library_alter()
   */
  public function testLibraryAlter() {
    $attached['#attached']['library'][] = 'core/jquery.ui.datepicker';
    drupal_render($attached);
    drupal_process_attached($attached);
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, 'locale.datepicker.js'), 'locale.datepicker.js added to scripts.');
  }
}
