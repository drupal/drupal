<?php

/**
 * @file
 * Contains \Drupal\block_content\Entity\BlockContentType.
 */

namespace Drupal\block_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\block_content\BlockContentTypeInterface;

/**
 * Defines the custom block type entity.
 *
 * @ConfigEntityType(
 *   id = "block_content_type",
 *   label = @Translation("Custom block type"),
 *   controllers = {
 *     "form" = {
 *       "default" = "Drupal\block_content\BlockContentTypeForm",
 *       "add" = "Drupal\block_content\BlockContentTypeForm",
 *       "edit" = "Drupal\block_content\BlockContentTypeForm",
 *       "delete" = "Drupal\block_content\Form\BlockContentTypeDeleteForm"
 *     },
 *     "list_builder" = "Drupal\block_content\BlockContentTypeListBuilder"
 *   },
 *   admin_permission = "administer blocks",
 *   config_prefix = "type",
 *   bundle_of = "block_content",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "delete-form" = "block_content.type_delete",
 *     "edit-form" = "block_content.type_edit"
 *   }
 * )
 */
class BlockContentType extends ConfigEntityBundleBase implements BlockContentTypeInterface {

  /**
   * The custom block type ID.
   *
   * @var string
   */
  public $id;

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
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update && !$this->isSyncing()) {
      entity_invoke_bundle_hook('create', 'block_content', $this->id());
      if (!$this->isSyncing()) {
        block_content_add_body_field($this->id);
      }
    }
    elseif ($this->getOriginalId() != $this->id) {
      entity_invoke_bundle_hook('rename', 'block_content', $this->getOriginalId(), $this->id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    foreach ($entities as $entity) {
      entity_invoke_bundle_hook('delete', 'block_content', $entity->id());
    }
  }

}
