<?php

/**
 * @file
 * Contains \Drupal\custom_block\Plugin\Core\Entity\CustomBlockType.
 */

namespace Drupal\custom_block\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the custom block type entity.
 *
 * @EntityType(
 *   id = "custom_block_type",
 *   label = @Translation("Custom block type"),
 *   module = "custom_block",
 *   controller_class = "Drupal\custom_block\CustomBlockTypeStorageController",
 *   list_controller_class = "Drupal\custom_block\CustomBlockTypeListController",
 *   form_controller_class = {
 *     "default" = "Drupal\custom_block\CustomBlockTypeFormController"
 *   },
 *   config_prefix = "custom_block.type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class CustomBlockType extends ConfigEntityBase {

  /**
   * The custom block type ID.
   *
   * @var string
   */
  public $id;

  /**
   * The custom block type UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The custom block type label.
   *
   * @var string
   */
  public $label;

  /**
   * The default revision setting for custom blocks of this type.
   *
   * @var bool
   */
  public $revision;

  /**
   * The description of the block type.
   *
   * @var string
   */
  public $description;

  /**
   * Overrides \Drupal\Core\Entity\Entity::uri().
   */
  public function uri() {
    return array(
      'path' => 'admin/structure/custom-blocks/manage/' . $this->id(),
      'options' => array(
        'entity_type' => $this->entityType,
        'entity' => $this,
      )
    );
  }
}
