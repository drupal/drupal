<?php

/**
 * @file
 * Definition of Drupal\Core\Config\Entity\ConfigEntityListController.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Entity\EntityListController;

/**
 * Defines the default list controller for ConfigEntity objects.
 */
class ConfigEntityListController extends EntityListController {

  /**
   * Overrides Drupal\Core\Entity\EntityListController::load().
   */
  public function load() {
    $entities = parent::load();
    uasort($entities, 'Drupal\Core\Config\Entity\ConfigEntityBase::sort');
    return $entities;
  }

}
