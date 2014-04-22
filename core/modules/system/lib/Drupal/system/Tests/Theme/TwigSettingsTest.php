<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\TwigSettingsTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\PhpStorage\PhpStorageFactory;

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
    $this->assertTrue($this->container->get('twig')->isAutoReload(), 'Automatic reloading of Twig templates enabled.');

    // Disable auto reload and check the service container again.
    $this->settingsSet('twig_auto_reload', FALSE);
    $this->rebuildContainer();

    $this->assertFalse($this->container->get('twig')->isAutoReload(), 'Automatic reloading of Twig templates disabled.');
  }

  /**
   * Ensures Twig engine debug setting can be overridden.
   */
  function testTwigDebugOverride() {
    // Enable debug and rebuild the service container.
    $this->settingsSet('twig_debug', TRUE);
    $this->rebuildContainer();

    // Check isDebug() via the Twig service container.
    $this->assertTrue($this->container->get('twig')->isDebug(), 'Twig debug enabled.');
    $this->assertTrue($this->container->get('twig')->isAutoReload(), 'Twig automatic reloading is enabled when debug is enabled.');

    // Override auto reload when debug is enabled.
    $this->settingsSet('twig_auto_reload', FALSE);
    $this->rebuildContainer();
    $this->assertFalse($this->container->get('twig')->isAutoReload(), 'Twig automatic reloading can be disabled when debug is enabled.');

    // Disable debug and check the service container again.
    $this->settingsSet('twig_debug', FALSE);
    $this->rebuildContainer();

    $this->assertFalse($this->container->get('twig')->isDebug(), 'Twig debug disabled.');
  }

  /**
   * Ensures Twig template cache setting can be overridden.
   */
  function testTwigCacheOverride() {
    $extension = twig_extension();
    $theme_handler = $this->container->get('theme_handler');
    $theme_handler->enable(array('test_theme'));
    $theme_handler->setDefault('test_theme');

    // The registry still works on theme globals, so set them here.
    $GLOBALS['theme'] = 'test_theme';
    $GLOBALS['theme_info'] = $theme_handler->listInfo()['test_theme'];

    // Reset the theme registry, so that the new theme is used.
    $this->container->set('theme.registry', NULL);

    // Load array of Twig templates.
    // reset() is necessary to invalidate caches tagged with 'theme_registry'.
    $registry = $this->container->get('theme.registry');
    $registry->reset();
    $templates = $registry->getRuntime();

    // Get the template filename and the cache filename for
    // theme_test.template_test.html.twig.
    $info = $templates->get('theme_test_template_test');
    $template_filename = $info['path'] . '/' . $info['template'] . $extension;
    $cache_filename = $this->container->get('twig')->getCacheFilename($template_filename);

    // Navigate to the page and make sure the template gets cached.
    $this->drupalGet('theme-test/template-test');
    $this->assertTrue(PhpStorageFactory::get('twig')->exists($cache_filename), 'Cached Twig template found.');

    // Disable the Twig cache and rebuild the service container.
    $this->settingsSet('twig_cache', FALSE);
    $this->rebuildContainer();

    // This should return false after rebuilding the service container.
    $new_cache_filename = $this->container->get('twig')->getCacheFilename($template_filename);
    $this->assertFalse($new_cache_filename, 'Twig environment does not return cache filename after caching is disabled.');
  }

}
