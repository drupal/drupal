<?php

/**
 * @file
 * Contains \Drupal\custom_block\Entity\CustomBlock.
 */

namespace Drupal\custom_block\Entity;

use Drupal\Core\Entity\ContentEntityBase;
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
 *   controllers = {
 *     "storage" = "Drupal\custom_block\CustomBlockStorageController",
 *     "access" = "Drupal\custom_block\CustomBlockAccessController",
 *     "list" = "Drupal\custom_block\CustomBlockListController",
 *     "view_builder" = "Drupal\custom_block\CustomBlockViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\custom_block\CustomBlockFormController",
 *       "edit" = "Drupal\custom_block\CustomBlockFormController",
 *       "delete" = "Drupal\custom_block\Form\CustomBlockDeleteForm",
 *       "default" = "Drupal\custom_block\CustomBlockFormController"
 *     },
 *     "translation" = "Drupal\custom_block\CustomBlockTranslationController"
 *   },
 *   admin_permission = "administer blocks",
 *   base_table = "custom_block",
 *   revision_table = "custom_block_revision",
 *   route_base_path = "admin/structure/block/custom-blocks/manage/{bundle}",
 *   links = {
 *     "canonical" = "/block/{custom_block}",
 *     "edit-form" = "/block/{custom_block}"
 *   },
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
class CustomBlock extends ContentEntityBase implements CustomBlockInterface {

  /**
   * The block ID.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $id;

  /**
   * The block revision ID.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $revision_id;

  /**
   * Indicates whether this is the default block revision.
   *
   * The default revision of a block is the one loaded when no specific revision
   * has been specified. Only default revisions are saved to the block_custom
   * table.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $isDefaultRevision = TRUE;

  /**
   * The block UUID.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $uuid;

  /**
   * The custom block type (bundle).
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $type;

  /**
   * The block language code.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $langcode;

  /**
   * The block description.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $info;

  /**
   * The block revision log message.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
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
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    parent::preSave($storage_controller);

    // Before saving the custom block, set changed time.
    $this->changed->value = REQUEST_TIME;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    parent::postSave($storage_controller, $update);

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
    parent::preSaveRevision($storage_controller, $record);

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

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['id'] = array(
      'label' => t('ID'),
      'description' => t('The custom block ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The custom block UUID.'),
      'type' => 'uuid_field',
    );
    $properties['revision_id'] = array(
      'label' => t('Revision ID'),
      'description' => t('The revision ID.'),
      'type' => 'integer_field',
    );
    $properties['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The custom block language code.'),
      'type' => 'language_field',
    );
    $properties['info'] = array(
      'label' => t('Subject'),
      'description' => t('The custom block name.'),
      'type' => 'string_field',
    );
    $properties['type'] = array(
      'label' => t('Block type'),
      'description' => t('The block type.'),
      'type' => 'string_field',
    );
    $properties['log'] = array(
      'label' => t('Revision log message'),
      'description' => t('The revision log message.'),
      'type' => 'string_field',
    );
    $properties['changed'] = array(
      'label' => t('Changed'),
      'description' => t('The time that the custom block was last edited.'),
      'type' => 'integer_field',
      'property_constraints' => array(
        'value' => array('EntityChanged' => array()),
      ),
    );
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

}
