<?php

/**
 * @file
 * Contains Drupal\user\Plugin\Core\Entity\Role.
 */

namespace Drupal\user\Plugin\Core\Entity;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the user role entity class.
 *
 * @Plugin(
 *   id = "user_role",
 *   label = @Translation("Role"),
 *   module = "user",
 *   controller_class = "Drupal\user\RoleStorageController",
 *   config_prefix = "user.role",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label"
 *   }
 * )
 */
class Role extends ConfigEntityBase {

  /**
   * The machine name of this role.
   *
   * @var string
   */
  public $id;

  /**
   * The UUID of this role.
   *
   * @var string
   */
  public $uuid;

  /**
   * The human-readable label of this role.
   *
   * @var string
   */
  public $label;

  /**
   * The weight of this role in administrative listings.
   *
   * @var int
   */
  public $weight;

}
