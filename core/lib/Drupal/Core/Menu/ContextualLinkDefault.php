<?php

namespace Drupal\Core\Menu;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a common base implementation of a contextual link.
 */
class ContextualLinkDefault extends PluginBase implements ContextualLinkInterface {

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    // The title from YAML file discovery may be a TranslatableMarkup object.
    return (string) $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return $this->pluginDefinition['route_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->pluginDefinition['group'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->pluginDefinition['options'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->pluginDefinition['weight'];
  }

}
