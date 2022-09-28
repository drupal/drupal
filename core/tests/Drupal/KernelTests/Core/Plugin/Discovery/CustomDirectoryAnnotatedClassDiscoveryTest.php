<?php

namespace Drupal\KernelTests\Core\Plugin\Discovery;

use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Tests that plugins in a custom directory are correctly discovered using
 * annotated classes.
 *
 * @group Plugin
 */
class CustomDirectoryAnnotatedClassDiscoveryTest extends DiscoveryTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->expectedDefinitions = [
      'custom_example_1' => [
        'id' => 'custom_example_1',
        'custom' => 'Tim',
        'class' => 'Drupal\plugin_test\CustomDirectoryExample1',
        'provider' => 'plugin_test',
      ],
      'custom_example_2' => [
        'id' => 'custom_example_2',
        'custom' => 'Meghan',
        'class' => 'Drupal\plugin_test\CustomDirectoryExample2',
        'provider' => 'plugin_test',
      ],
      'apple' => [
        'id' => 'apple',
        'label' => 'Apple',
        'color' => 'green',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Apple',
        'provider' => 'plugin_test',
      ],
      'banana' => [
        'id' => 'banana',
        'label' => 'Banana',
        'color' => 'yellow',
        'uses' => [
          'bread' => new TranslatableMarkup('Banana bread'),
          'loaf' => [
            'singular' => '@count loaf',
            'plural' => '@count loaves',
            'context' => NULL,
          ],
        ],
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Banana',
        'provider' => 'plugin_test',
      ],
      'cherry' => [
        'id' => 'cherry',
        'label' => 'Cherry',
        'color' => 'red',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Cherry',
        'provider' => 'plugin_test',
      ],
      'kale' => [
        'id' => 'kale',
        'label' => 'Kale',
        'color' => 'green',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Kale',
        'provider' => 'plugin_test',
      ],
      'orange' => [
        'id' => 'orange',
        'label' => 'Orange',
        'color' => 'orange',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\Orange',
        'provider' => 'plugin_test',
      ],
      'extending_non_installed_class' => [
        'id' => 'extending_non_installed_class',
        'label' => 'A plugin whose class is extending from a non-installed module class',
        'color' => 'pink',
        'class' => 'Drupal\plugin_test\Plugin\plugin_test\fruit\ExtendingNonInstalledClass',
        'provider' => 'plugin_test',
      ],
    ];

    $base_directory = $this->root . '/core/modules/system/tests/modules/plugin_test/src';
    $namespaces = new \ArrayObject(['Drupal\plugin_test' => $base_directory]);

    $this->discovery = new AnnotatedClassDiscovery('', $namespaces);
    $empty_namespaces = new \ArrayObject();
    $this->emptyDiscovery = new AnnotatedClassDiscovery('', $empty_namespaces);
  }

}
