<?php

namespace Drupal\KernelTests\Component\Render;

use Drupal\Core\Url;
use Drupal\Core\Render\Element\Splitbutton;
use Drupal\KernelTests\KernelTestBase;

/**
 * Provides a test that checks for deprecated splitbutton properties.
 *
 * @group Render
 * @group legacy
 */
class SplitbuttonDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['splitbutton_test'];

  /**
   * Check for deprecated splitbutton properties.
   */
  public function testDeprecation() {
    $this->expectDeprecation('Splitbutton using #dropbutton_type is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/3169786');
    $this->expectDeprecation('Splitbutton using #links is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/3169786');
    $element_with_deprecated_properties = [
      '#type' => 'splitbutton',
      '#dropbutton_type' => 'small',
      '#links' => [
        'one' => [
          'title' => 'one',
          'url' => Url::fromRoute('splitbutton.test_link_1'),
        ],
        'two' => [
          'title' => 'two',
          'url' => Url::fromRoute('splitbutton.test_link_2'),
        ],
      ],
    ];

    $plugin_definition = [
      'id' => 'splitbutton',
      'class' => 'Drupal\Core\Render\Element\Splitbutton',
      'provider' => 'core',
    ];

    $splitbutton_element = new Splitbutton([], 'splitbutton', $plugin_definition);
    $splitbutton = $splitbutton_element::preRenderSplitbutton($element_with_deprecated_properties);

    $expected_array_keys = [
      '#type',
      '#dropbutton_type',
      '#links',
      '#variants',
      '#trigger_id',
      '#toggle_attributes',
      '#main_element',
      '#splitbutton_item_list',
      '#splitbutton_multiple',
      '#attributes',
    ];

    $this->assertEquals($expected_array_keys, array_keys($splitbutton));
  }

}
