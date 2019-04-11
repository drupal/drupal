<?php

namespace Drupal\Tests\locale\Kernel;

use Drupal\Core\Asset\AttachedAssets;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests localization of the JavaScript libraries.
 *
 * Currently, only the jQuery datepicker is localized using Drupal translations.
 *
 * @group locale
 */
class LocaleLibraryAlterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['locale'];

  /**
   * Verifies that the datepicker can be localized.
   *
   * @see locale_library_alter()
   */
  public function testLibraryAlter() {
    $this->installSchema('locale', [
      'locales_location',
      'locales_source',
    ]);

    $assets = new AttachedAssets();
    $assets->setLibraries(['core/jquery.ui.datepicker']);
    $js_assets = $this->container->get('asset.resolver')->getJsAssets($assets, FALSE)[1];
    $this->assertArrayHasKey('core/modules/locale/locale.datepicker.js', $js_assets);
  }

}
