<?php

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
   * Returns id of the tip.
   *
   * @return string
   *   The id of the tip.
   */
  public function id();

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
   * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. The
   *   attributes property is no longer used.
   * @see https://www.drupal.org/node/3204093
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
   * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use
   *   getBody() instead, and do not include the tip label in the returned
   *   output.
   * @see https://www.drupal.org/node/3195234
   *
   * @return array
   *   A renderable array.
   */
  public function getOutput();

}
