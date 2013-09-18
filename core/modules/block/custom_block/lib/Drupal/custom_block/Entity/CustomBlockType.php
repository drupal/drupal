<?php

/**
 * @file
 * Contains \Drupal\custom_block\Entity\CustomBlockType.
 */

namespace Drupal\custom_block\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\custom_block\CustomBlockTypeInterface;

/**
 * Defines the custom block type entity.
 *
 * @EntityType(
 *   id = "custom_block_type",
 *   label = @Translation("Custom block type"),
 *   module = "custom_block",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *     "access" = "Drupal\custom_block\CustomBlockTypeAccessController",
 *     "form" = {
 *       "default" = "Drupal\custom_block\CustomBlockTypeFormController",
 *       "add" = "Drupal\custom_block\CustomBlockTypeFormController",
 *       "edit" = "Drupal\custom_block\CustomBlockTypeFormController",
 *       "delete" = "Drupal\custom_block\Form\CustomBlockTypeDeleteForm"
 *     },
 *     "list" = "Drupal\custom_block\CustomBlockTypeListController"
 *   },
 *   config_prefix = "custom_block.type",
 *   bundle_of = "custom_block",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "admin/structure/block/custom-blocks/manage/{custom_block_type}"
 *   }
 * )
 */
class CustomBlockType extends ConfigEntityBase implements CustomBlockTypeInterface {

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
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);

    if (!$update) {
      entity_invoke_bundle_hook('create', 'custom_block', $this->id());
      custom_block_add_body_field($this->id);
    }
    elseif ($this->originalID != $this->id) {
      entity_invoke_bundle_hook('rename', 'custom_block', $this->originalID, $this->id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::postDelete($storage_controller, $entities);

    foreach ($entities as $entity) {
      entity_invoke_bundle_hook('delete', 'custom_block', $entity->id());
    }
  }

}
