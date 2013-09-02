<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Plugin\Discovery\AnnotatedClassDiscoveryTest.
 */

namespace Drupal\system\Tests\Plugin\Discovery;

use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

/**
 * Tests that plugins with annotated classes are correctly discovered.
 */
class AnnotatedClassDiscoveryTest extends DiscoveryTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Annotated class discovery',
      'description' => 'Tests that plugins are correctly discovered using annotated classes.',
      'group' => 'Plugin API',
    );
  }

  public function setUp() {
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
    $namespaces = new \ArrayObject(array('Drupal\plugin_test' => DRUPAL_ROOT . '/core/modules/system/tests/modules/plugin_test/lib'));
    $this->discovery = new AnnotatedClassDiscovery('Plugin/plugin_test/fruit', $namespaces);
    $this->emptyDiscovery = new AnnotatedClassDiscovery('Plugin/non_existing_module/non_existing_plugin_type', $namespaces);
  }

}
