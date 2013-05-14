<?php

/**
 * @file
 * Contains \Drupal\tour\Plugin\Core\Entity\TourInterface.
 */

namespace Drupal\tour;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a tour entity.
 */
interface TourInterface extends ConfigEntityInterface {

  /**
   * The paths that this tour will appear on.
   *
   * @return array
   *   Returns array of paths for the tour.
   */
  public function getPaths();

  /**
   * Returns tip plugin.
   *
   * @param string $id
   *   The identifier of the tip.
   *
   * @return \Drupal\tour\TipPluginInterface
   *   The tip plugin.
   */
  public function getTip($id);

  /**
   * Returns the tips for this tour.
   *
   * @return array
   *   An array of tip plugins.
   */
  public function getTips();

}
