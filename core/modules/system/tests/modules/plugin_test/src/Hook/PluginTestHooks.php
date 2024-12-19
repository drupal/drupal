<?php

declare(strict_types=1);

namespace Drupal\plugin_test\Hook;

use Drupal\plugin_test\Plugin\plugin_test\fruit\Apple;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for plugin_test.
 */
class PluginTestHooks {

  /**
   * Implements hook_test_plugin_info().
   */
  #[Hook('test_plugin_info')]
  public function testPluginInfo(): array {
    return [
      'apple' => [
        'id' => 'apple',
        'class' => Apple::class,
      ],
    ];
  }

  /**
   * Implements hook_plugin_test_alter().
   */
  #[Hook('plugin_test_alter')]
  public function pluginTestAlter(&$definitions): void {
    foreach ($definitions as &$definition) {
      $definition['altered'] = TRUE;
    }
    $definitions['user_login']['altered_single'] = TRUE;
  }

}
