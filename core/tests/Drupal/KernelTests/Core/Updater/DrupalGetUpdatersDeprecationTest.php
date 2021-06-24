<?php

namespace Drupal\KernelTests\Core\Updater;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group Updater
 * @group legacy
 */
class DrupalGetUpdatersDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * @see drupal_get_updaters()
   * @see drupal_static_reset()
   */
  public function testDrupalGetUpdatersDeprecation(): void {
    $this->expectDeprecation('drupal_get_updaters() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Updater\Updater::getUpdaterRegistry() instead. See https://www.drupal.org/node/3047258');
    $updaters = drupal_get_updaters();
    $this->assertSame(['module', 'theme'], array_keys($updaters));
    $this->expectDeprecation("Using drupal_static_reset() with 'drupal_get_updaters' as parameter is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Updater\Updater::resetRegistryCache() instead. See https://www.drupal.org/node/3047258");
    drupal_static_reset('drupal_get_updaters');
  }

}
