<?php

/**
 * @file
 * Contains \Drupal\custom_block\Plugin\Core\Entity\CustomBlock.
 */

namespace Drupal\custom_block\Plugin\Core\Entity;

use Drupal\Core\Entity\EntityNG;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\custom_block\CustomBlockInterface;

/**
 * Defines the custom block entity class.
 *
 * @EntityType(
 *   id = "custom_block",
 *   label = @Translation("Custom Block"),
 *   bundle_label = @Translation("Custom Block type"),
 *   module = "custom_block",
 *   controllers = {
 *     "storage" = "Drupal\custom_block\CustomBlockStorageController",
 *     "access" = "Drupal\custom_block\CustomBlockAccessController",
 *     "render" = "Drupal\custom_block\CustomBlockRenderController",
 *     "form" = {
 *       "add" = "Drupal\custom_block\CustomBlockFormController",
 *       "edit" = "Drupal\custom_block\CustomBlockFormController",
 *       "default" = "Drupal\custom_block\CustomBlockFormController"
 *     },
 *     "translation" = "Drupal\custom_block\CustomBlockTranslationController"
 *   },
 *   base_table = "custom_block",
 *   revision_table = "custom_block_revision",
 *   route_base_path = "admin/structure/custom-blocks/manage/{bundle}",
 *   menu_base_path = "block/%custom_block",
 *   menu_edit_path = "block/%custom_block",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "info",
 *     "uuid" = "uuid"
 *   },
 *   bundle_keys = {
 *     "bundle" = "type"
 *   }
 * )
 */
class CustomBlock extends EntityNG implements CustomBlockInterface {

  /**
   * The block ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $id;

  /**
   * The block revision ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $revision_id;

  /**
   * Indicates whether this is the default block revision.
   *
   * The default revision of a block is the one loaded when no specific revision
   * has been specified. Only default revisions are saved to the block_custom
   * table.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $isDefaultRevision = TRUE;

  /**
   * The block UUID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $uuid;

  /**
   * The custom block type (bundle).
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $type;

  /**
   * The block language code.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $langcode;

  /**
   * The block description.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $info;

  /**
   * The block revision log message.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $log;

  /**
   * The theme the block is being created in.
   *
   * When creating a new custom block from the block library, the user is
   * redirected to the configure form for that block in the given theme. The
   * theme is stored against the block when the custom block add form is shown.
   *
   * @var string
   */
  protected $theme;

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    $duplicate = parent::createDuplicate();
    $duplicate->revision_id->value = NULL;
    $duplicate->id->value = NULL;
    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionId() {
    return $this->revision_id->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTheme($theme) {
    $this->theme = $theme;
  }

  /**
   * {@inheritdoc}
   */
  public function getTheme() {
    return $this->theme;
  }

  /**
   * Initialize the object. Invoked upon construction and wake up.
   */
  protected function init() {
    parent::init();
    // We unset all defined properties except theme, so magic getters apply.
    // $this->theme is a special use-case that is only used in the lifecycle of
    // adding a new block using the block library.
    unset($this->id);
    unset($this->info);
    unset($this->revision_id);
    unset($this->log);
    unset($this->uuid);
    unset($this->type);
    unset($this->new);
  }

  /**
   * {@inheritdoc}
   */
  public function uri() {
    return array(
      'path' => 'block/' . $this->id(),
      'options' => array(
        'entity_type' => $this->entityType,
        'entity' => $this,
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    // Invalidate the block cache to update custom block-based derivatives.
    \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getInstances() {
    return entity_load_multiple_by_properties('block', array('plugin' => 'custom_block:' . $this->uuid->value));
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageControllerInterface $storage_controller, \stdClass $record) {
    if ($this->isNewRevision()) {
      // When inserting either a new custom block or a new custom_block
      // revision, $entity->log must be set because {block_custom_revision}.log
      // is a text column and therefore cannot have a default value. However,
      // it might not be set at this point (for example, if the user submitting
      // the form does not have permission to create revisions), so we ensure
      // that it is at least an empty string in that case.
      // @todo: Make the {block_custom_revision}.log column nullable so that we
      // can remove this check.
      if (!isset($record->log)) {
        $record->log = '';
      }
    }
    elseif (isset($this->original) && (!isset($record->log) || $record->log === '')) {
      // If we are updating an existing custom_block without adding a new
      // revision and the user did not supply a log, keep the existing one.
      $record->log = $this->original->log->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    foreach ($this->getInstances() as $instance) {
      $instance->delete();
    }
    parent::delete();
  }

}
