<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\TwigSettingsTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\PhpStorage\PhpStorageFactory;

/**
 * Tests overriding Twig engine settings via settings.php.
 *
 * @group Theme
 */
class TwigSettingsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test');

  /**
   * Ensures Twig template auto reload setting can be overridden.
   */
  function testTwigAutoReloadOverride() {
    // Enable auto reload and rebuild the service container.
    $parameters = $this->container->getParameter('twig.config');
    $parameters['auto_reload'] = TRUE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();

    // Check isAutoReload() via the Twig service container.
    $this->assertTrue($this->container->get('twig')->isAutoReload(), 'Automatic reloading of Twig templates enabled.');

    // Disable auto reload and check the service container again.
    $parameters = $this->container->getParameter('twig.config');
    $parameters['auto_reload'] = FALSE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();

    $this->assertFalse($this->container->get('twig')->isAutoReload(), 'Automatic reloading of Twig templates disabled.');
  }

  /**
   * Ensures Twig engine debug setting can be overridden.
   */
  function testTwigDebugOverride() {
    // Enable debug and rebuild the service container.
    $parameters = $this->container->getParameter('twig.config');
    $parameters['debug'] = TRUE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();

    // Check isDebug() via the Twig service container.
    $this->assertTrue($this->container->get('twig')->isDebug(), 'Twig debug enabled.');
    $this->assertTrue($this->container->get('twig')->isAutoReload(), 'Twig automatic reloading is enabled when debug is enabled.');

    // Override auto reload when debug is enabled.
    $parameters = $this->container->getParameter('twig.config');
    $parameters['auto_reload'] = FALSE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();
    $this->assertFalse($this->container->get('twig')->isAutoReload(), 'Twig automatic reloading can be disabled when debug is enabled.');

    // Disable debug and check the service container again.
    $parameters = $this->container->getParameter('twig.config');
    $parameters['debug'] = FALSE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();

    $this->assertFalse($this->container->get('twig')->isDebug(), 'Twig debug disabled.');
  }

  /**
   * Ensures Twig template cache setting can be overridden.
   */
  function testTwigCacheOverride() {
    $extension = twig_extension();
    $theme_handler = $this->container->get('theme_handler');
    $theme_handler->install(array('test_theme'));
    $theme_handler->setDefault('test_theme');

    // The registry still works on theme globals, so set them here.
    \Drupal::theme()->setActiveTheme(\Drupal::service('theme.initialization')->getActiveThemeByName('test_theme'));

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
    $parameters = $this->container->getParameter('twig.config');
    $parameters['cache'] = FALSE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();

    // This should return false after rebuilding the service container.
    $this->assertFalse($this->container->get('twig')->getCache(), 'Twig environment has caching disabled.');
  }

  /**
   * Tests twig inline templates with auto_reload.
   */
  public function testTwigInlineWithAutoReload() {
    $parameters = $this->container->getParameter('twig.config');
    $parameters['auto_reload'] = TRUE;
    $parameters['debug'] = TRUE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();

    $this->drupalGet('theme-test/inline-template-test');
    $this->assertResponse(200);
    $this->drupalGet('theme-test/inline-template-test');
    $this->assertResponse(200);
  }

}
