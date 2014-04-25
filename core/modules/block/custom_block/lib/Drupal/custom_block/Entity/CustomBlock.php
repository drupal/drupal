<?php

/**
 * @file
 * Contains \Drupal\custom_block\Entity\CustomBlock.
 */

namespace Drupal\custom_block\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\custom_block\CustomBlockInterface;

/**
 * Defines the custom block entity class.
 *
 * @ContentEntityType(
 *   id = "custom_block",
 *   label = @Translation("Custom Block"),
 *   bundle_label = @Translation("Custom Block type"),
 *   controllers = {
 *     "access" = "Drupal\custom_block\CustomBlockAccessController",
 *     "list_builder" = "Drupal\custom_block\CustomBlockListBuilder",
 *     "view_builder" = "Drupal\custom_block\CustomBlockViewBuilder",
 *     "form" = {
 *       "add" = "Drupal\custom_block\CustomBlockForm",
 *       "edit" = "Drupal\custom_block\CustomBlockForm",
 *       "delete" = "Drupal\custom_block\Form\CustomBlockDeleteForm",
 *       "default" = "Drupal\custom_block\CustomBlockForm"
 *     },
 *     "translation" = "Drupal\custom_block\CustomBlockTranslationHandler"
 *   },
 *   admin_permission = "administer blocks",
 *   base_table = "custom_block",
 *   revision_table = "custom_block_revision",
 *   links = {
 *     "canonical" = "custom_block.edit",
 *     "delete-form" = "custom_block.delete",
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
 *   bundle_entity_type = "custom_block_type"
 * )
 */
class CustomBlock extends ContentEntityBase implements CustomBlockInterface {

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
  public function setTheme($theme) {
    $this->theme = $theme;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTheme() {
    return $this->theme;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Invalidate the block cache to update custom block-based derivatives.
    \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getInstances() {
    return entity_load_multiple_by_properties('block', array('plugin' => 'custom_block:' . $this->uuid()));
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

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
      $record->log = $this->original->getRevisionLog();
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = FieldDefinition::create('integer')
      ->setLabel(t('Custom block ID'))
      ->setDescription(t('The custom block ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The custom block UUID.'))
      ->setReadOnly(TRUE);

    $fields['revision_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['langcode'] = FieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The custom block language code.'));

    $fields['info'] = FieldDefinition::create('string')
      ->setLabel(t('Block description'))
      ->setDescription(t('A brief description of your block.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['type'] = FieldDefinition::create('entity_reference')
      ->setLabel(t('Block type'))
      ->setDescription(t('The block type.'))
      ->setSetting('target_type', 'custom_block_type')
      ->setSetting('max_length', EntityTypeInterface::BUNDLE_MAX_LENGTH);

    $fields['log'] = FieldDefinition::create('string')
      ->setLabel(t('Revision log message'))
      ->setDescription(t('The revision log message.'))
      ->setRevisionable(TRUE);

    $fields['changed'] = FieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the custom block was last edited.'))
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionLog() {
    return $this->get('log')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setInfo($info) {
    $this->set('info', $info);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionLog($log) {
    $this->set('log', $log);
    return $this;
  }

}
