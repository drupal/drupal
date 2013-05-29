<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\TwigSettingsTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;
use Drupal\Component\PhpStorage\PhpStorageFactory;

/**
 * Tests Twig engine configuration via settings.php.
 */
class TwigSettingsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test');

  public static function getInfo() {
    return array(
      'name' => 'Twig Settings',
      'description' => 'Tests overriding Twig engine settings via settings.php.',
      'group' => 'Theme',
    );
  }

  /**
   * Ensures Twig template auto reload setting can be overridden.
   */
  function testTwigAutoReloadOverride() {
    // Enable auto reload and rebuild the service container.
    $this->settingsSet('twig_auto_reload', TRUE);
    $this->rebuildContainer();

    // Check isAutoReload() via the Twig service container.
    $this->assertTrue(drupal_container()->get('twig')->isAutoReload(), 'Automatic reloading of Twig templates enabled.');

    // Disable auto reload and check the service container again.
    $this->settingsSet('twig_auto_reload', FALSE);
    $this->rebuildContainer();

    $this->assertFalse(drupal_container()->get('twig')->isAutoReload(), 'Automatic reloading of Twig templates disabled.');
  }

  /**
   * Ensures Twig engine debug setting can be overridden.
   */
  function testTwigDebugOverride() {
    // Enable debug and rebuild the service container.
    $this->settingsSet('twig_debug', TRUE);
    $this->rebuildContainer();

    // Check isDebug() via the Twig service container.
    $this->assertTrue(drupal_container()->get('twig')->isDebug(), 'Twig debug enabled.');
    $this->assertTrue(drupal_container()->get('twig')->isAutoReload(), 'Twig automatic reloading is enabled when debug is enabled.');

    // Override auto reload when debug is enabled.
    $this->settingsSet('twig_auto_reload', FALSE);
    $this->rebuildContainer();
    $this->assertFalse(drupal_container()->get('twig')->isAutoReload(), 'Twig automatic reloading can be disabled when debug is enabled.');

    // Disable debug and check the service container again.
    $this->settingsSet('twig_debug', FALSE);
    $this->rebuildContainer();

    $this->assertFalse(drupal_container()->get('twig')->isDebug(), 'Twig debug disabled.');
  }

  /**
   * Ensures Twig template cache setting can be overridden.
   */
  function testTwigCacheOverride() {
    $extension = twig_extension();
    theme_enable(array('test_theme'));
    config('system.theme')
      ->set('default', 'test_theme')
      ->save();

    $cache = array();
    // Prime the theme cache.
    foreach (module_implements('theme') as $module) {
      _theme_process_registry($cache, $module, 'module', $module, drupal_get_path('module', $module));
    }

    // Load array of Twig templates.
    $templates = drupal_find_theme_templates($cache, $extension, drupal_get_path('theme', 'test_theme'));

    // Get the template filename and the cache filename for
    // theme_test.template_test.html.twig.
    $template_filename = $templates['theme_test_template_test']['path'] . '/' . $templates['theme_test_template_test']['template'] . $extension;
    $cache_filename = drupal_container()->get('twig')->getCacheFilename($template_filename);

    // Navigate to the page and make sure the template gets cached.
    $this->drupalGet('theme-test/template-test');
    $this->assertTrue(PhpStorageFactory::get('twig')->exists($cache_filename), 'Cached Twig template found.');

    // Disable the Twig cache and rebuild the service container.
    $this->settingsSet('twig_cache', FALSE);
    $this->rebuildContainer();

    // This should return false after rebuilding the service container.
    $new_cache_filename = drupal_container()->get('twig')->getCacheFilename($template_filename);
    $this->assertFalse($new_cache_filename, 'Twig environment does not return cache filename after caching is disabled.');
  }

}
