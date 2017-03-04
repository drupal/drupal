<?php

namespace Drupal\locale\Tests;

use Drupal\Core\Asset\AttachedAssets;
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
  public static $modules = ['locale'];

  /**
   * Verifies that the datepicker can be localized.
   *
   * @see locale_library_alter()
   */
  public function testLibraryAlter() {
    $assets = new AttachedAssets();
    $assets->setLibraries(['core/jquery.ui.datepicker']);
    $js_assets = $this->container->get('asset.resolver')->getJsAssets($assets, FALSE)[1];
    $this->assertTrue(array_key_exists('core/modules/locale/locale.datepicker.js', $js_assets), 'locale.datepicker.js added to scripts.');
  }

}
