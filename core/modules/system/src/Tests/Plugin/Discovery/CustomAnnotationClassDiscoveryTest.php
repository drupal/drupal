<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Plugin\Discovery\CustomAnnotationClassDiscoveryTest.
 */

namespace Drupal\system\Tests\Plugin\Discovery;

use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

/**
 * Tests that a custom annotation class is used.
 *
 * @see \Drupal\plugin_test\Plugin\Annotation\PluginExample
 */
class CustomAnnotationClassDiscoveryTest extends DiscoveryTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Custom annotation class discovery',
      'description' => 'Tests that a custom annotation class is used.',
      'group' => 'Plugin API',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->expectedDefinitions = array(
      'example_1' => array(
        'id' => 'example_1',
        'custom' => 'John',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\custom_annotation\Example1',
        'provider' => 'plugin_test',
      ),
      'example_2' => array(
        'id' => 'example_2',
        'custom' => 'Paul',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\custom_annotation\Example2',
        'provider' => 'plugin_test',
      ),
    );
    $root_namespaces = new \ArrayObject(array(
      'Drupal\plugin_test' => array(
        // @todo Remove lib/Drupal/$module, once the switch to PSR-4 is complete.
        DRUPAL_ROOT . '/core/modules/system/tests/modules/plugin_test/lib/Drupal/plugin_test',
        DRUPAL_ROOT . '/core/modules/system/tests/modules/plugin_test/src',
      ),
    ));

    $this->discovery = new AnnotatedClassDiscovery('Plugin/plugin_test/custom_annotation', $root_namespaces, 'Drupal\plugin_test\Plugin\Annotation\PluginExample');
    $this->emptyDiscovery = new AnnotatedClassDiscovery('Plugin/non_existing_module/non_existing_plugin_type', $root_namespaces, 'Drupal\plugin_test\Plugin\Annotation\PluginExample');
  }

}
