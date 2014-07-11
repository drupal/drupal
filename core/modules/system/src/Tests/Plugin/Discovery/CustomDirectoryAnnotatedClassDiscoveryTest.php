<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Plugin\Discovery\CustomDirectoryAnnotatedClassDiscoveryTest.
 */

namespace Drupal\system\Tests\Plugin\Discovery;

use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

/**
 * Tests that plugins in a custom directory are correctly discovered using
 * annotated classes.
 *
 * @group Plugin
 */
class CustomDirectoryAnnotatedClassDiscoveryTest extends DiscoveryTestBase {

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

    $base_directory = DRUPAL_ROOT . '/core/modules/system/tests/modules/plugin_test/src';
    $namespaces = new \ArrayObject(array('Drupal\plugin_test' => $base_directory));

    $this->discovery = new AnnotatedClassDiscovery('', $namespaces);
    $empty_namespaces = new \ArrayObject();
    $this->emptyDiscovery = new AnnotatedClassDiscovery('', $empty_namespaces);
  }

}
