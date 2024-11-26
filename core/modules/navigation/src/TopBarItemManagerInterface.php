<?php

declare(strict_types=1);

namespace Drupal\navigation;

/**
 * Top bar item plugin manager.
 */
interface TopBarItemManagerInterface {

  /**
   * Gets the top bar item plugins by region.
   *
   * @param \Drupal\navigation\TopBarRegion $region
   *   The region.
   *
   * @return array
   *   A list of top bar item plugin definitions.
   */
  public function getDefinitionsByRegion(TopBarRegion $region): array;

  /**
   * Gets the top bar items prepared as render array.
   *
   * @param \Drupal\navigation\TopBarRegion $region
   *   The region.
   *
   * @return array
   *   An array of rendered top bar items, keyed by the plugin ID and sorted by
   *   weight.
   */
  public function getRenderedTopBarItemsByRegion(TopBarRegion $region): array;

}
