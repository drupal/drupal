<?php

namespace Drupal\Component\Plugin;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;

/**
 * Provides plugin metadata inspection.
 *
 * @see \Drupal\Component\Plugin\PluginInspectionInterface
 */
trait PluginInspectionTrait {

  /**
   * {@inheritdoc}
   */
  abstract public function getPluginId();

  /**
   * {@inheritdoc}
   */
  abstract public function getPluginDefinition();

  /**
   * {@inheritdoc}
   */
  public function isDeprecated(): bool {
    return !is_null($this->getDeprecationMessage());
  }

  /**
   * {@inheritdoc}
   */
  public function getDeprecationMessage(): ?string {
    $plugin_definition = $this->getPluginDefinition();
    if (is_array($plugin_definition)) {
      return $plugin_definition['deprecation_message'] ?? NULL;
    }
    if ($plugin_definition instanceof PluginDefinitionInterface) {
      if ($plugin_definition->deprecationMessage ?? NULL) {
        return $plugin_definition->deprecationMessage;
      }
      if (property_exists($plugin_definition, 'additional')) {
        return $plugin_definition->get('additional')['deprecation_message'] ?? NULL;
      }
    }
    return NULL;
  }

  /**
   * Checks a plugin to see if it has been deprecated.
   *
   * This is intended to be used as part of plugin construction. Simply add a
   * call to this method in your plugin base class's constructor.
   */
  protected function checkDeprecation() {
    $message = $this->getDeprecationMessage();
    if (!is_null($message)) {
      @trigger_error($message, E_USER_DEPRECATED);
    }
  }

}
