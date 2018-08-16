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
  public static $modules = ['node', 'user'];

  /**
   * Test that DefaultFactory can create a plugin instance.
   */
  public function testDefaultFactory() {
    // Ensure a non-derivative plugin can be instantiated.
    $plugin = $this->testPluginManager->createInstance('user_login', ['title' => 'Please enter your login name and password']);
    $this->assertIdentical(get_class($plugin), 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockUserLoginBlock', 'Correct plugin class instantiated with default factory.');
    $this->assertIdentical($plugin->getTitle(), 'Please enter your login name and password', 'Plugin instance correctly configured.');

    // Ensure that attempting to instantiate non-existing plugins throws a
    // PluginException.
    try {
      $this->testPluginManager->createInstance('non_existing');
      $this->fail('Drupal\Component\Plugin\Exception\ExceptionInterface expected');
    }
    catch (ExceptionInterface $e) {
      $this->pass('Drupal\Component\Plugin\Exception\ExceptionInterface expected and caught.');
    }
    catch (\Exception $e) {
      $this->fail('Drupal\Component\Plugin\Exception\ExceptionInterface expected, but ' . get_class($e) . ' was thrown.');
    }
  }

  /**
   * Test that the Reflection factory can create a plugin instance.
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
    $plugin = $this->mockBlockManager->createInstance('user_login', ['title' => 'Please enter your login name and password']);
    $this->assertIdentical(get_class($plugin), 'Drupal\plugin_test\Plugin\plugin_test\mock_block\MockUserLoginBlock', 'Correct plugin class instantiated.');
    $this->assertIdentical($plugin->getTitle(), 'Please enter your login name and password', 'Plugin instance correctly configured.');

    // Ensure a derivative plugin can be instantiated.
    $plugin = $this->mockBlockManager->createInstance('menu:main_menu', ['depth' => 2]);
    $this->assertIdentical($plugin->getContent(), '<ul><li>1<ul><li>1.1</li></ul></li></ul>', 'Derived plugin instance correctly instantiated and configured.');

    // Ensure that attempting to instantiate non-existing plugins throws a
    // PluginException. Test this for a non-existing base plugin, a non-existing
    // derivative plugin, and a base plugin that may not be used without
    // deriving.
    foreach (['non_existing', 'menu:non_existing', 'menu'] as $invalid_id) {
      try {
        $this->mockBlockManager->createInstance($invalid_id);
        $this->fail('Drupal\Component\Plugin\Exception\ExceptionInterface expected');
      }
      catch (ExceptionInterface $e) {
        $this->pass('Drupal\Component\Plugin\Exception\ExceptionInterface expected and caught.');
      }
      catch (\Exception $e) {
        $this->fail('An unexpected Exception of type "' . get_class($e) . '" was thrown with message ' . $e->getMessage());
      }
    }
  }

}
