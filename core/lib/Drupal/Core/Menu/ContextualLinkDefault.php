<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\ContextualLinkDefault.
 */

namespace Drupal\Core\Menu;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a common base implementation of a contextual link.
 */
class ContextualLinkDefault extends PluginBase implements ContextualLinkInterface {

  /**
   * {@inheritdoc}
   *
   * @todo: It might be helpful at some point to move this getTitle logic into
   *   a trait.
   */
  public function getTitle(Request $request = NULL) {
    $options = array();
    if (!empty($this->pluginDefinition['title_context'])) {
      $options['context'] = $this->pluginDefinition['title_context'];
    }
    $args = array();
    if (isset($this->pluginDefinition['title_arguments']) && $title_arguments = $this->pluginDefinition['title_arguments']) {
      $args = (array) $title_arguments;
    }

    return $this->t($this->pluginDefinition['title'], $args, $options);
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
