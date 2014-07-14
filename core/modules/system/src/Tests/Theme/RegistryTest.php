<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Theme\RegistryTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Utility\ThemeRegistry;

/**
 * Tests the behavior of the ThemeRegistry class.
 *
 * @group Theme
 */
class RegistryTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test');

  protected $profile = 'testing';
  /**
   * Tests the behavior of the theme registry class.
   */
  function testRaceCondition() {
    // The theme registry is not marked as persistable in case we don't have a
    // proper request.
    \Drupal::request()->setMethod('GET');
    $cid = 'test_theme_registry';

    // Directly instantiate the theme registry, this will cause a base cache
    // entry to be written in __construct().
    $cache = \Drupal::cache();
    $lock_backend = \Drupal::lock();
    $registry = new ThemeRegistry($cid, $cache, $lock_backend, array('theme_registry' => TRUE), $this->container->get('module_handler')->isLoaded());

    $this->assertTrue(\Drupal::cache()->get($cid), 'Cache entry was created.');

    // Trigger a cache miss for an offset.
    $this->assertTrue($registry->get('theme_test_template_test'), 'Offset was returned correctly from the theme registry.');
    // This will cause the ThemeRegistry class to write an updated version of
    // the cache entry when it is destroyed, usually at the end of the request.
    // Before that happens, manually delete the cache entry we created earlier
    // so that the new entry is written from scratch.
    \Drupal::cache()->delete($cid);

    // Destroy the class so that it triggers a cache write for the offset.
    $registry->destruct();

    $this->assertTrue(\Drupal::cache()->get($cid), 'Cache entry was created.');

    // Create a new instance of the class. Confirm that both the offset
    // requested previously, and one that has not yet been requested are both
    // available.
    $registry = new ThemeRegistry($cid, $cache, $lock_backend, array('theme_registry' => TRUE), $this->container->get('module_handler')->isLoaded());
    $this->assertTrue($registry->get('theme_test_template_test'), 'Offset was returned correctly from the theme registry');
    $this->assertTrue($registry->get('theme_test_template_test_2'), 'Offset was returned correctly from the theme registry');
  }
}
