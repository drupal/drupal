<?php

declare(strict_types=1);

namespace Drupal\navigation\Plugin\Block;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\shortcut\Plugin\Block\ShortcutNavigationBlock;

/**
 * Defines a shortcuts navigation block class.
 *
 * @internal
 *
 * @deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. Use
 *  \Drupal\shortcut\Plugin\Block\ShortcutNavigationBlock instead.
 *
 * @see https://www.drupal.org/node/3587759
 */
final class NavigationShortcutsBlock extends ShortcutNavigationBlock {

  /**
   * Constructs a new ShortcutsNavigationBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected ModuleHandlerInterface $moduleHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $moduleHandler);
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. Use \Drupal\shortcut\Plugin\Block\ShortcutNavigationBlock instead. See https://www.drupal.org/node/3587759', E_USER_DEPRECATED);
  }

}
