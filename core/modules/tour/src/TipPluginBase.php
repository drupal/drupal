<?php

/**
 * @file
 * Contains \Drupal\tour\TipPluginBase.
 */

namespace Drupal\tour;

use Drupal\Core\Plugin\PluginBase;
use Drupal\tour\TipPluginInterface;

/**
 * Defines a base tour implementation.
 */
abstract class TipPluginBase extends PluginBase implements TipPluginInterface {

  /**
   * The label which is used for render of this tip.
   *
   * @var string
   */
  protected $label;

  /**
   * Allows tips to take more priority that others.
   *
   * @var string
   */
  protected $weight;

  /**
   * The attributes that will be applied to the markup of this tip.
   *
   * @var array
   */
  protected $attributes;

  /**
   * Implements \Drupal\tour\TipPluginInterface::getLabel().
   */
  public function getLabel() {
    return $this->get('label');
  }

  /**
   * Implements \Drupal\tour\TipPluginInterface::getWeight().
   */
  public function getWeight() {
    return $this->get('weight');
  }

  /**
   * Implements \Drupal\tour\TipPluginInterface::getAttributes().
   */
  public function getAttributes() {
    return $this->get('attributes');
  }

  /**
   * Implements \Drupal\tour\TipPluginInterface::get().
   */
  public function get($key) {
    if (!empty($this->configuration[$key])) {
      return $this->configuration[$key];
    }
  }

  /**
   * Implements \Drupal\tour\TipPluginInterface::set().
   */
  public function set($key, $value) {
    $this->configuration[$key] = $value;
  }
}
