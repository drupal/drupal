<?php

/**
 * @file
 * Contains \Drupal\tour\TipPluginBase.
 */

namespace Drupal\tour;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\tour\TipPluginInterface;

/**
 * Defines a base tour implementation.
 */
abstract class TipPluginBase extends PluginBase implements TipPluginInterface {

  /**
   * The label which is used for render of this tip.
   *
   * @var string
   *   The label of this tip.
   */
  protected $label;

  /**
   * Allows tips to take more priority that others.
   *
   * @var string
   *   A number which pertains to ordering.
   */
  protected $weight;

  /**
   * The attributes that will be applied to the markup of this tip.
   *
   * @var array
   *   An array of attributes.
   */
  protected $attributes;

  /**
   * Overrides \Drupal\Component\Plugin\PluginBase::__construct().
   */
  public function __construct(array $configuration, $plugin_id, CacheDecorator $discovery) {
    parent::__construct($configuration, $plugin_id, $discovery);

    $this->definition = $this->discovery->getDefinition($plugin_id);
    $this->module = $this->definition['module'];
  }

  /**
   * Implements \Drupal\tour\Plugin\tour\tour\TourInterface::getLabel().
   */
  public function getLabel() {
    return $this->get('label');
  }

  /**
   * Implements \Drupal\tour\Plugin\tour\tour\TourInterface::getWeight().
   */
  public function getWeight() {
    return $this->get('weight');
  }

  /**
   * Implements \Drupal\tour\Plugin\tour\tour\TourInterface::getAttributes().
   */
  public function getAttributes() {
    return $this->get('attributes');
  }

  /**
   * Implements \Drupal\tour\Plugin\tour\tour\TourInterface::get().
   */
  public function get($key) {
    if (!empty($this->configuration[$key])) {
      return $this->configuration[$key];
    }
  }

  /**
   * Implements \Drupal\tour\Plugin\tour\tour\TourInterface::set().
   */
  public function set($key, $value) {
    $this->configuration[$key] = $value;
  }
}
