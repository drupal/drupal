<?php

/**
 * @file
 * Definition of Drupal\field\Plugin\Type\LegacyDiscoveryDecorator.
 */

namespace Drupal\field\Plugin\Type;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Plugin\Discovery\HookDiscovery;

/**
 * Custom decorator to add legacy widgets.
 *
 * Legacy widgets are discovered through the old hook_field_widget_info() hook,
 * and handled by the Drupal\field\Plugin\field\widget\LegacyWidget class.
 *
 * @todo Remove once all core widgets have been converted.
 */
class LegacyDiscoveryDecorator implements DiscoveryInterface {

  /**
   * The decorated discovery object.
   *
   * @var Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $decorated;

  /**
   * Creates a Drupal\field\Plugin\Discovery\LegacyDiscoveryDecorator object.
   *
   * @param Drupal\Component\Plugin\Discovery\DiscoveryInterface $discovery
   *   The parent object implementing DiscoveryInterface that is being
   *   decorated.
   */
  public function __construct(DiscoveryInterface $decorated) {
    $this->decorated = $decorated;
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinition().
   */
  public function getDefinition($plugin_id) {
    $definitions = $this->getDefinitions();
    return isset($definitions[$plugin_id]) ? $definitions[$plugin_id] : NULL;
  }

  /**
   * Implements Drupal\Component\Plugin\Discovery\DiscoveryInterface::getDefinitions().
   */
  public function getDefinitions() {
    $definitions = $this->decorated->getDefinitions();

    $legacy_discovery = new HookDiscovery('field_widget_info');
    if ($legacy_definitions = $legacy_discovery->getDefinitions()) {
      foreach ($legacy_definitions as $plugin_id => &$definition) {
        $definition['class'] = '\Drupal\field\Plugin\field\widget\LegacyWidget';

        // Transform properties for which the format has changed.
        if (isset($definition['field types'])) {
          $definition['field_types'] = $definition['field types'];
          unset($definition['field types']);
        }
        if (isset($definition['behaviors']['multiple values'])) {
          $definition['multiple_values'] = $definition['behaviors']['multiple values'];
          unset($definition['behaviors']['multiple values']);
        }

        $definitions[$plugin_id] = $definition;
      }
    }
    return $definitions;
  }

}
