<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Plugin\DefaultFactoryTest.
 */

namespace Drupal\Tests\Component\Plugin;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Component\Plugin\DefaultFactory
 * @group Plugin
 */
class DefaultFactoryTest extends UnitTestCase {

  /**
   * Tests getPluginClass() with a valid plugin.
   */
  public function testGetPluginClassWithValidPlugin() {
    $plugin_class = 'Drupal\plugin_test\Plugin\plugin_test\fruit\Cherry';
    $class = DefaultFactory::getPluginClass('cherry', ['class' => $plugin_class]);

    $this->assertEquals($plugin_class, $class);
  }

  /**
   * Tests getPluginClass() with a missing class definition.
   *
   * @expectedException \Drupal\Component\Plugin\Exception\PluginException
   * @expectedExceptionMessage The plugin (cherry) did not specify an instance class.
   */
  public function testGetPluginClassWithMissingClass() {
    DefaultFactory::getPluginClass('cherry', []);
  }

  /**
   * Tests getPluginClass() with a not existing class definition.
   *
   * @expectedException \Drupal\Component\Plugin\Exception\PluginException
   * @expectedExceptionMessage Plugin (kiwifruit) instance class "\Drupal\plugin_test\Plugin\plugin_test\fruit\Kiwifruit" does not exist.
   */
  public function testGetPluginClassWithNotExistingClass() {
    DefaultFactory::getPluginClass('kiwifruit', ['class' => '\Drupal\plugin_test\Plugin\plugin_test\fruit\Kiwifruit']);
  }

  /**
   * Tests getPluginClass() with a required interface.
   */
  public function testGetPluginClassWithInterface() {
    $plugin_class = 'Drupal\plugin_test\Plugin\plugin_test\fruit\Cherry';
    $class = DefaultFactory::getPluginClass('cherry', ['class' => $plugin_class], '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');

    $this->assertEquals($plugin_class, $class);
  }

  /**
   * Tests getPluginClass() with a required interface but no implementation.
   *
   * @expectedException \Drupal\Component\Plugin\Exception\PluginException
   * @expectedExceptionMessage Plugin "cherry" (Drupal\plugin_test\Plugin\plugin_test\fruit\Kale) in core should implement interface \Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface.
   */
  public function testGetPluginClassWithInterfaceAndInvalidClass() {
    $plugin_class = 'Drupal\plugin_test\Plugin\plugin_test\fruit\Kale';
    DefaultFactory::getPluginClass('cherry', ['class' => $plugin_class, 'provider' => 'core'], '\Drupal\plugin_test\Plugin\plugin_test\fruit\FruitInterface');
  }

}

