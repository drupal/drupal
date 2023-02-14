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
   * Returns the selector the tour tip will attach to.
   *
   * This typically maps to the Shepherd Step options `attachTo.element`
   * property.
   *
   * @return null|string
   *   A selector string, or null for an unattached tip.
   *
   * @see https://shepherdjs.dev/docs/Step.html
   */
  public function getSelector(): ?string;

  /**
   * Returns the body content of the tooltip.
   *
   * This typically maps to the Shepherd Step options `text` property.
   *
   * @return array
   *   A render array.
   *
   * @see https://shepherdjs.dev/docs/Step.html
   */
  public function getBody(): array;

  /**
   * Returns the configured placement of the tip relative to the element.
   *
   * If null, the tip will automatically determine the best position based on
   * the element's position in the viewport.
   *
   * This typically maps to the Shepherd Step options `attachTo.on` property.
   *
   * @return string|null
   *   The tip placement relative to the element.
   *
   * @see https://shepherdjs.dev/docs/Step.html
   */
  public function getLocation(): ?string;

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

}
