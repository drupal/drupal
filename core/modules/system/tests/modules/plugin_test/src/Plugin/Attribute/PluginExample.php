<?php

declare(strict_types=1);

namespace Drupal\plugin_test\Plugin\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Defines a custom PluginExample attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class PluginExample extends Plugin {

  /**
   * Constructs a PluginExample attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param string $custom
   *   Some other sample plugin metadata.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?string $custom = NULL,
  ) {}

}
