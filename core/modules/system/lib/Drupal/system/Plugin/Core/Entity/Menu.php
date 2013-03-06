<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Core\Entity\Menu.
 */

namespace Drupal\system\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the Menu configuration entity class.
 *
 * @Plugin(
 *   id = "menu",
 *   label = @Translation("Menu"),
 *   module = "system",
 *   controller_class = "Drupal\Core\Config\Entity\ConfigStorageController",
 *   config_prefix = "menu.menu",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Menu extends ConfigEntityBase {

  /**
   * The menu machine name.
   *
   * @var string
   */
  public $id;

  /**
   * The menu UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The human-readable name of the menu entity.
   *
   * @var string
   */
  public $label;

  /**
   * The menu description.
   *
   * @var string
   */
  public $description;

}
