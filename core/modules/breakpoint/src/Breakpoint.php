<?php

/**
 * @file
 * Contains \Drupal\breakpoint\Breakpoint.
 */

namespace Drupal\breakpoint;

use Drupal\Core\Plugin\PluginBase;

/**
 * Default object used for breakpoint plugins.
 *
 * @see \Drupal\breakpoint\BreakpointManager
 * @see plugin_api
 */
class Breakpoint extends PluginBase implements BreakpointInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t($this->pluginDefinition['label'], array(), array('context' => 'breakpoint'));
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return (int) $this->pluginDefinition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMediaQuery() {
    return $this->pluginDefinition['mediaQuery'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMultipliers() {
    return $this->pluginDefinition['multipliers'];
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->pluginDefinition['provider'];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->pluginDefinition['group'];
  }

}
