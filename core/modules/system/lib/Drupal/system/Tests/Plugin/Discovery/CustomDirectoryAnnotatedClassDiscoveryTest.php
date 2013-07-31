<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Plugin\Discovery\CustomDirectoryAnnotatedClassDiscoveryTest.
 */

namespace Drupal\system\Tests\Plugin\Discovery;

use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

/**
 * Tests that plugins with annotated classes in a custom directory are correctly discovered.
 */
class CustomDirectoryAnnotatedClassDiscoveryTest extends DiscoveryTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Custom directory annotation class discovery',
      'description' => 'Tests that plugins in a custom directory are correctly discovered using annotated classes.',
      'group' => 'Plugin API',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->expectedDefinitions = array(
      'custom_example_1' => array(
        'id' => 'custom_example_1',
        'custom' => 'Tim',
        'class' => 'Drupal\plugin_test\CustomDirectoryExample1',
        'provider' => 'plugin_test',
      ),
      'custom_example_2' => array(
        'id' => 'custom_example_2',
        'custom' => 'Meghan',
        'class' => 'Drupal\plugin_test\CustomDirectoryExample2',
        'provider' => 'plugin_test',
      ),
    );
    $namespaces = new \ArrayObject(array('Drupal\plugin_test' => DRUPAL_ROOT . '/core/modules/system/tests/modules/plugin_test/lib'));
    $this->discovery = new AnnotatedClassDiscovery('', $namespaces);
    $empty_namespaces = new \ArrayObject();
    $this->emptyDiscovery = new AnnotatedClassDiscovery('', $empty_namespaces);
  }

}
