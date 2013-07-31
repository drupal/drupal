<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\Type\Widget\LegacyFieldTypeDiscoveryDecorator.
 */

namespace Drupal\field\Plugin\Type\FieldType;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Custom decorator to add legacy field types.
 *
 * Legacy field types are discovered through the old hook_field_info() hook,
 * and handled by the Drupal\field\Plugin\field\field_type\LegacyConfigFieldItem class.
 *
 * @todo Remove once all core field types have been converted (see
 * http://drupal.org/node/2014671).
 */
class LegacyFieldTypeDiscoveryDecorator implements DiscoveryInterface {

  /**
   * The decorated discovery object.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $decorated;

  /**
   * Creates a \Drupal\field\Plugin\Type\FieldType\LegacyFieldTypeDiscoveryDecorator object.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $discovery
   *   The parent object implementing DiscoveryInterface that is being
   *   decorated.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(DiscoveryInterface $decorated, ModuleHandlerInterface $module_handler) {
    $this->decorated = $decorated;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id) {
    $definitions = $this->getDefinitions();
    return isset($definitions[$plugin_id]) ? $definitions[$plugin_id] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = $this->decorated->getDefinitions();

    // We cannot use HookDiscovery, since it uses
    // Drupal::moduleHandler()->getImplementations(), which
    // throws exceptions during upgrades.
    foreach (array_keys($this->moduleHandler->getModuleList()) as $module) {
      $function = $module . '_field_info';
      if (function_exists($function)) {
        foreach ($function() as $plugin_id => $definition) {
          $definition['id'] = $plugin_id;
          $definition['provider'] = $module;
          $definition['list_class'] = '\Drupal\field\Plugin\field\field_type\LegacyConfigField';
          $definitions[$plugin_id] = $definition;
        }
      }
    }

    return $definitions;
  }

}
