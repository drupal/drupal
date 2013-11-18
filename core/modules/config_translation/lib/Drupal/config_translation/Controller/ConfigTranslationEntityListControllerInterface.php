<?php

/**
 * @file
 * Contains \Drupal\config_translation\Controller\ConfigTranslationEntityListControllerInterface.
 */

namespace Drupal\config_translation\Controller;

use Drupal\Core\Entity\EntityListControllerInterface;

/**
 * Defines an interface for configuration translation entity list controllers.
 */
interface ConfigTranslationEntityListControllerInterface extends EntityListControllerInterface {

  /**
   * Sorts an array by value.
   *
   * @param array $a
   *   First item for comparison.
   * @param array $b
   *   Second item for comparison.
   *
   * @return int
   *   The comparison result for uasort().
   */
  public function sortRows($a, $b);

}
