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

    // @see element_info_test_element_plugin_alter()
    $this->container->get('state')->set('hook_element_plugin_alter:remove_weight', TRUE);
    // The definition will be cached.
    $this->assertArrayHasKey('weight', $info_manager->getDefinitions());

    // Clearing the caches removes the definition.
    $info_manager->clearCachedDefinitions();
    $this->assertArrayNotHasKey('weight', $info_manager->getDefinitions());
  }

}
