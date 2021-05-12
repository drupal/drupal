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
 *   https://drupal.org/node/3195193
 */
interface TourTipPluginInterface extends TipPluginInterface {

  /**
   * The selector the tour tip will attach to.
   *
   * @return null|string
   *   A selector string, or null for an unattached tip.
   */
  public function getSelector();

  /**
   * Provides the body content of the tooltip.
   *
   * This is mapped to the `text` property of the Shepherd tooltip options.
   *
   * @return array
   *   A render array.
   */
  public function getBody();

  /**
   * The title of the tour tip.
   *
   * This is what is displayed in the tip's header. It may differ from the tip
   * label, which is defined in the tip's configuration.
   *
   * @return string
   *   The title.
   */
  public function getTitle();

}
