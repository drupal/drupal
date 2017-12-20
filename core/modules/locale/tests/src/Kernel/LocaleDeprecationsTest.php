<?php

namespace Drupal\Tests\locale\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecations in the locale module.
 *
 * @group locale
 * @group legacy
 */
class LocaleDeprecationsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['locale', 'system'];

  /**
   * @expectedDeprecation locale_translation_manual_status() is deprecated in Drupal 8.5.0 and will be removed before Drupal 9.0.0. It is unused by Drupal core. Duplicate this function in your own extension if you need its behavior.
   */
  public function testLocaleTranslationManualStatusDeprecation() {
    module_load_include('pages.inc', 'locale');
    $this->assertNotNull(\locale_translation_manual_status());
  }

}
