<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Render\Element;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group Render
 */
class PluginAlterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['element_info_test'];

  /**
   * Tests hook_element_plugin_alter().
   */
  public function testPluginAlter(): void {
    $info_manager = $this->container->get('plugin.manager.element_info');
    $this->assertArrayHasKey('weight', $info_manager->getDefinitions());

    // @see ElementInfoTestHooks::elementPluginAlter().
    $this->container->get('state')->set('hook_element_plugin_alter:remove_weight', TRUE);
    // The definition will be cached.
    $this->assertArrayHasKey('weight', $info_manager->getDefinitions());

    // Clearing the caches removes the definition.
    $info_manager->clearCachedDefinitions();
    $this->assertArrayNotHasKey('weight', $info_manager->getDefinitions());
  }

  /**
   * Tests hook_element_plugin_alter().
   */
  public function testPluginClassSwap(): void {
    $info_manager = $this->container->get('plugin.manager.element_info');
    $test_details = [
      '#type' => 'details',
      '#title' => 'Title',
      '#description' => 'Description',
      '#open' => TRUE,
    ];

    // @see ElementInfoTestHooks::elementPluginAlter().
    $expected = [
      'class' => 'Drupal\element_info_test\Render\Element\Details',
      'provider' => 'element_info_test',
      'id' => 'details',
    ];
    $this->assertEquals($expected, $info_manager->getDefinitions()['details']);
    \Drupal::service('renderer')->renderRoot($test_details);
    $this->assertArrayHasKey('#custom', $test_details);
  }

}
