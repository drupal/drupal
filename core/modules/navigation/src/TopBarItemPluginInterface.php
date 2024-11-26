<?php

declare(strict_types=1);

namespace Drupal\navigation;

/**
 * Interface for top bar plugins.
 */
interface TopBarItemPluginInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string|\Stringable
   *   The translated plugin label.
   */
  public function label(): string|\Stringable;

  /**
   * Returns the plugin region.
   *
   * @return \Drupal\navigation\TopBarRegion
   *   The plugin region.
   */
  public function region(): TopBarRegion;

  /**
   * Builds and returns the renderable array for this top bar item plugin.
   *
   * If a top bar item should not be rendered because it has no content, then
   * this method must also ensure to return no content: it must then only return
   * an empty array, or an empty array with #cache set (with cacheability
   * metadata indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the top bar item.
   */
  public function build();

}
