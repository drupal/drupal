<?php

namespace Drupal\KernelTests\Core\Plugin;

use Drupal\Component\Plugin\Exception\ExceptionInterface;

/**
 * Tests that plugins are correctly instantiated.
 *
 * @group Plugin
 */
class FactoryTest extends PluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'user'];

  /**
   * Tests that DefaultFactory can create a plugin instance.
   */
  public function testDefaultFactory() {
    // Ensure a non-derivative plugin can be instantiated.
    $plugin = $this->testPluginManager->createInstance('user_login', ['title' => 'Enter your login name and password']);
    $this->assertSame('Drupal\\plugin_test\\Plugin\\plugin_test\\mock_block\\MockUserLoginBlock', get_class($plugin), 'Correct plugin class instantiated with default factory.');
    $this->assertSame('Enter your login name and password', $plugin->getTitle(), 'Plugin instance correctly configured.');

    // Ensure that attempting to instantiate non-existing plugins throws a
    // PluginException.
    try {
      $this->testPluginManager->createInstance('non_existing');
      $this->fail('Drupal\Component\Plugin\Exception\ExceptionInterface expected');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(ExceptionInterface::class, $e);
    }
  }

  /**
   * Tests that the Reflection factory can create a plugin instance.
   *
   * The mock plugin classes use different values for their constructors
   * allowing us to test the reflection capabilities as well.
   *
   * We use derivative classes here because the block test type has the
   * reflection factory and it provides some additional variety in plugin
   * object creation.
   */
  public function testReflectionFactory() {
    // Ensure a non-derivative plugin can be instantiated.
    $plugin = $this->mockBlockManager->createInstance('user_login', ['title' => 'Enter your login name and password']);
    $this->assertSame('Drupal\\plugin_test\\Plugin\\plugin_test\\mock_block\\MockUserLoginBlock', get_class($plugin), 'Correct plugin class instantiated.');
    $this->assertSame('Enter your login name and password', $plugin->getTitle(), 'Plugin instance correctly configured.');

    // Ensure a derivative plugin can be instantiated.
    $plugin = $this->mockBlockManager->createInstance('menu:main_menu', ['depth' => 2]);
    $this->assertSame('<ul><li>1<ul><li>1.1</li></ul></li></ul>', $plugin->getContent(), 'Derived plugin instance correctly instantiated and configured.');

    // Ensure that attempting to instantiate non-existing plugins throws a
    // PluginException. Test this for a non-existing base plugin, a non-existing
    // derivative plugin, and a base plugin that may not be used without
    // deriving.
    foreach (['non_existing', 'menu:non_existing', 'menu'] as $invalid_id) {
      try {
        $this->mockBlockManager->createInstance($invalid_id);
        $this->fail('Drupal\Component\Plugin\Exception\ExceptionInterface expected');
      }
      catch (\Exception $e) {
        $this->assertInstanceOf(ExceptionInterface::class, $e);
      }
    }
  }

}
