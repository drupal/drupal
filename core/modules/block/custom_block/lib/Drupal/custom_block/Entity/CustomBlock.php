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
use Drupal\Core\Field\FieldDefinition;
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
 *   links = {
 *     "canonical" = "custom_block.edit",
 *     "edit-form" = "custom_block.edit",
 *     "admin-form" = "custom_block.type_edit"
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
 *   },
 *   bundle_entity_type = "custom_block_type"
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
    $fields['id'] = FieldDefinition::create('integer')
      ->setLabel(t('Custom block ID'))
      ->setDescription(t('The custom block ID.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The custom block UUID.'))
      ->setReadOnly(TRUE);

    $fields['revision_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The revision ID.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = FieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The custom block language code.'));

    $fields['info'] = FieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setDescription(t('The custom block name.'));

    $fields['type'] = FieldDefinition::create('string')
      ->setLabel(t('Block type'))
      ->setDescription(t('The block type.'));

    $fields['log'] = FieldDefinition::create('string')
      ->setLabel(t('Revision log message'))
      ->setDescription(t('The revision log message.'));

    // @todo Convert to a "changed" field in https://drupal.org/node/2145103.
    $fields['changed'] = FieldDefinition::create('integer')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the custom block was last edited.'))
      ->setPropertyConstraints('value', array('EntityChanged' => array()));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

}
