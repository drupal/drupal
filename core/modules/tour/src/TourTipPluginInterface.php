<?php

namespace Drupal\tour;

/**
 * Defines an interface for tour items.
 *
 * @see \Drupal\tour\Annotation\Tip
 * @see \Drupal\tour\TipPluginBase
 * @see \Drupal\tour\TipPluginManager
 * @see plugin_api
 *
 * @todo move all methods to TipPluginInterface and deprecate this interface in
 *   https://drupal.org/i/3276336
 */
interface TourTipPluginInterface extends TipPluginInterface {

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
  public function getSelector() : ?string;

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

}
