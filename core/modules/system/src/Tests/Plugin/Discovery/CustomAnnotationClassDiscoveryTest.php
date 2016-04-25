<?php

namespace Drupal\system\Tests\Plugin\Discovery;

use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

/**
 * Tests that a custom annotation class is used.
 *
 * @group Plugin
 * @see \Drupal\plugin_test\Plugin\Annotation\PluginExample
 */
class CustomAnnotationClassDiscoveryTest extends DiscoveryTestBase {

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

    $base_directory = \Drupal::root() . '/core/modules/system/tests/modules/plugin_test/src';
    $root_namespaces = new \ArrayObject(array('Drupal\plugin_test' => $base_directory));

    $this->discovery = new AnnotatedClassDiscovery('Plugin/plugin_test/custom_annotation', $root_namespaces, 'Drupal\plugin_test\Plugin\Annotation\PluginExample');
    $this->emptyDiscovery = new AnnotatedClassDiscovery('Plugin/non_existing_module/non_existing_plugin_type', $root_namespaces, 'Drupal\plugin_test\Plugin\Annotation\PluginExample');
  }

}
