<?php

namespace Drupal\KernelTests\Core\Plugin\Discovery;

use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

/**
 * Tests that plugins are correctly discovered using annotated classes.
 *
 * @group Plugin
 */
class AnnotatedClassDiscoveryTest extends DiscoveryTestBase {

  protected function setUp() {
    parent::setUp();
    $this->expectedDefinitions = array(
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
          'loaf' => array(
            'singular' => '@count loaf',
            'plural' => '@count loaves',
            'context' => NULL,
          ),
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
      'kale' => array(
        'id' => 'kale',
        'label' => 'Kale',
        'color' => 'green',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Kale',
        'provider' => 'plugin_test',
      ),
      'orange' => array(
        'id' => 'orange',
        'label' => 'Orange',
        'color' => 'orange',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Orange',
        'provider' => 'plugin_test',
      ),
      'big_apple' => array(
        'id' => 'big_apple',
        'label' => 'Big Apple',
        'color' => 'green',
        'class' => 'Drupal\plugin_test_extended\Plugin\plugin_test\fruit\BigApple',
        'provider' => 'plugin_test_extended',
      ),
    );

    $base_directory = \Drupal::root() . '/core/modules/system/tests/modules/plugin_test/src';
    $base_directory2 = \Drupal::root() . '/core/modules/system/tests/modules/plugin_test_extended/src';
    $namespaces = new \ArrayObject(array('Drupal\plugin_test' => $base_directory, 'Drupal\plugin_test_extended' => $base_directory2));

    $annotation_namespaces = ['Drupal\plugin_test\Plugin\Annotation', 'Drupal\plugin_test_extended\Plugin\Annotation'];
    $this->discovery = new AnnotatedClassDiscovery('Plugin/plugin_test/fruit', $namespaces, 'Drupal\Component\Annotation\Plugin', $annotation_namespaces);
    $this->emptyDiscovery = new AnnotatedClassDiscovery('Plugin/non_existing_module/non_existing_plugin_type', $namespaces);
  }

}
