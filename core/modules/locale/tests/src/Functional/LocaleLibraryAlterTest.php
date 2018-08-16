<?php

namespace Drupal\Tests\locale\Functional;

use Drupal\Core\Asset\AttachedAssets;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests localization of the JavaScript libraries.
 *
 * Currently, only the jQuery datepicker is localized using Drupal translations.
 *
 * @group locale
 */
class LocaleLibraryAlterTest extends BrowserTestBase {

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
