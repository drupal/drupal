<?php

/**
 * @file
 * Contains \Drupal\tour\TipPluginInterface.
 */

namespace Drupal\tour;

/**
 * Defines an interface for tour items.
 *
 * @see \Drupal\tour\Annotation\Tip
 * @see \Drupal\tour\TipPluginBase
 * @see \Drupal\tour\TipPluginManager
 * @see plugin_api
 */
interface TipPluginInterface {

  /**
   * Returns label of the tip.
   *
   * @return string
   *   The label of the tip.
   */
  public function getLabel();

  /**
   * Returns weight of the tip.
   *
   * @return string
   *   The weight of the tip.
   */
  public function getWeight();

  /**
   * Returns an array of attributes for the tip wrapper.
   *
   * @return array
   *   An array of classes and values.
   */
  public function getAttributes();

  /**
   * Used for returning values by key.
   *
   * @var string
   *   Key of the value.
   *
   * @return string
   *   Value of the key.
   */
  public function get($key);

  /**
   * Used for returning values by key.
   *
   * @var string
   *   Key of the value.
   *
   * @var string
   *   Value of the key.
   */
  public function set($key, $value);

  /**
   * Returns a renderable array.
   *
   * @return array
   *   A renderable array.
   */
  public function getOutput();

}
