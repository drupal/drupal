<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\ContextualLinkDefault.
 */

namespace Drupal\Core\Menu;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a common base implementation of a contextual link.
 */
class ContextualLinkDefault extends PluginBase implements ContextualLinkInterface {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    $options = array();
    if (!empty($this->pluginDefinition['title_context'])) {
      $options['context'] = $this->pluginDefinition['title_context'];
    }
    return $this->t($this->pluginDefinition['title'], array(), $options);
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
