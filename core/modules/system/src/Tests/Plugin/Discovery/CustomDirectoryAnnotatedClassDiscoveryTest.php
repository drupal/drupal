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
      'apple' => array(
        'id' => 'apple',
        'label' => 'Apple',
        'color' => 'green',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Apple',
        'provider' => 'plugin_test',
      ),
      'banana' => array(
        'id' => 'banana',
        'label' => 'Banana',
        'color' => 'yellow',
        'uses' => array(
          'bread' => t('Banana bread'),
        ),
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Banana',
        'provider' => 'plugin_test',
      ),
      'cherry' => array(
        'id' => 'cherry',
        'label' => 'Cherry',
        'color' => 'red',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Cherry',
        'provider' => 'plugin_test',
      ),
      'orange' => array(
        'id' => 'orange',
        'label' => 'Orange',
        'color' => 'orange',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Orange',
        'provider' => 'plugin_test',
      ),
    );
    // Due to the transition from PSR-0 to PSR-4, plugin classes can be in
    // either one of
    // - core/modules/system/tests/modules/plugin_test/lib/Drupal/plugin_test/
    // - core/modules/system/tests/modules/plugin_test/src/
    // To avoid false positives with "Drupal\plugin_test\Drupal\plugin_test\..",
    // only one of them can be registered.
    // Note: This precaution is only needed if the plugin namespace is identical
    // with the module namespace. Usually this is not the case, because every
    // plugin namespace is like "Drupal\$module\Plugin\..".
    // @todo Clean this up, once the transition to PSR-4 is complete.
    $extension_dir = DRUPAL_ROOT . '/core/modules/system/tests/modules/plugin_test';
    $base_directory = is_dir($extension_dir . '/lib/Drupal/plugin_test')
      ? $extension_dir . '/lib/Drupal/plugin_test'
      : $extension_dir . '/src';
    $namespaces = new \ArrayObject(array('Drupal\plugin_test' => $base_directory));
    $this->discovery = new AnnotatedClassDiscovery('', $namespaces);
    $empty_namespaces = new \ArrayObject();
    $this->emptyDiscovery = new AnnotatedClassDiscovery('', $empty_namespaces);
  }

}
