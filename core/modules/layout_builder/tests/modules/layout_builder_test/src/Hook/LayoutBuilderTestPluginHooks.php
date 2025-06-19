<?php

declare(strict_types=1);

namespace Drupal\layout_builder_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Plugin hook implementations for layout_builder_test.
 */
class LayoutBuilderTestPluginHooks {

  /**
   * Implements hook_plugin_filter_TYPE__CONSUMER_alter().
   */
  #[Hook('plugin_filter_block__layout_builder_alter')]
  public function pluginFilterBlockLayoutBuilderAlter(array &$definitions, array $extra): void {
    // Explicitly remove the "Help" blocks from the list.
    unset($definitions['help_block']);
    // Explicitly remove the "Sticky at top of lists field_block".
    $disallowed_fields = ['sticky'];
    // Remove "Changed" field if this is the first section.
    if ($extra['delta'] === 0) {
      $disallowed_fields[] = 'changed';
    }
    foreach ($definitions as $plugin_id => $definition) {
      // Field block IDs are in the form 'field_block:{entity}:{bundle}:{name}',
      // for example 'field_block:node:article:revision_timestamp'.
      preg_match('/field_block:.*:.*:(.*)/', $plugin_id, $parts);
      if (isset($parts[1]) && in_array($parts[1], $disallowed_fields, TRUE)) {
        // Unset any field blocks that match our predefined list.
        unset($definitions[$plugin_id]);
      }
    }
  }

}
