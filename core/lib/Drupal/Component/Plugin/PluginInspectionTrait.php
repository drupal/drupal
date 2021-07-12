<?php

namespace Drupal\Component\Plugin;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;

/**
 * Provides plugin metadata inspection.
 */
trait PluginInspectionTrait {

  /**
   * The plugin_id.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The plugin implementation definition.
   *
   * @var mixed
   */
  protected $pluginDefinition;

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return $this->pluginDefinition;
  }

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
    if (is_array($this->pluginDefinition)) {
      return $this->pluginDefinition['deprecation_message'] ?? NULL;
    }
    if ($this->pluginDefinition instanceof PluginDefinitionInterface) {
      if ($this->pluginDefinition->deprecationMessage ?? NULL) {
        return $this->pluginDefinition->deprecationMessage;
      }
      if (property_exists($this->pluginDefinition, 'additional')) {
        return $this->pluginDefinition->get('additional')['deprecation_message'] ?? NULL;
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
