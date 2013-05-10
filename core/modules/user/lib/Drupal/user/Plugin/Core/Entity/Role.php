<?php

/**
 * @file
 * Contains Drupal\user\Plugin\Core\Entity\Role.
 */

namespace Drupal\user\Plugin\Core\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\user\RoleInterface;

/**
 * Defines the user role entity class.
 *
 * @EntityType(
 *   id = "user_role",
 *   label = @Translation("Role"),
 *   module = "user",
 *   controllers = {
 *     "storage" = "Drupal\user\RoleStorageController",
 *     "list" = "Drupal\user\RoleListController",
 *     "form" = {
 *       "default" = "Drupal\user\RoleFormController"
 *     }
 *   },
 *   config_prefix = "user.role",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label"
 *   }
 * )
 */
class Role extends ConfigEntityBase implements RoleInterface {

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

  /**
   * {@inheritdoc}
   */
  public function uri() {
    return array(
      'path' => 'admin/people/roles/manage/' . $this->id(),
      'options' => array(
        'entity_type' => $this->entityType,
        'entity' => $this,
      ),
    );
  }

}
