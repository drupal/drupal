<?php

/**
 * @file
 * Contains \Drupal\custom_block\CustomBlockStorageController.
 */

namespace Drupal\custom_block;

use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for custom blocks.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for custom block entities.
 */
class CustomBlockStorageController extends DatabaseStorageControllerNG {

  /**
   * Overrides \Drupal\Core\Entity\DatabaseStorageController::preSaveRevision().
   */
  protected function preSaveRevision(\stdClass $record, EntityInterface $entity) {
    if ($entity->isNewRevision()) {
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
    elseif (isset($entity->original) && (!isset($record->log) || $record->log === '')) {
      // If we are updating an existing custom_block without adding a new
      // revision, we need to make sure $entity->log is reset whenever it is
      // empty. Therefore, this code allows us to avoid clobbering an existing
      // log entry with an empty one.
      $record->log = $entity->original->log->value;
    }
  }

  /**
   * Overrides \Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   */
  protected function attachLoad(&$blocks, $load_revision = FALSE) {
    // Create an array of block types for passing as a load argument.
    // Note that blocks at this point are still \StdClass objects returned from
    // the database.
    foreach ($blocks as $id => $entity) {
      $types[$entity->type] = $entity->type;
    }

    // Besides the list of blocks, pass one additional argument to
    // hook_custom_block_load(), containing a list of block types that were
    // loaded.
    $this->hookLoadArguments = array($types);
    parent::attachLoad($blocks, $load_revision);
  }

  /**
   * Overrides \Drupal\Core\Entity\DatabaseStorageController::postSave().
   */
  protected function postSave(EntityInterface $block, $update) {
    // Invalidate the block cache to update custom block-based derivatives.
    drupal_container()->get('plugin.manager.block')->clearCachedDefinitions();
  }

  /**
   * Implements \Drupal\Core\Entity\DataBaseStorageControllerNG::basePropertyDefinitions().
   */
  public function baseFieldDefinitions() {
    $properties['id'] = array(
      'label' => t('ID'),
      'description' => t('The custom block ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The custom block UUID.'),
      'type' => 'string_field',
    );
    $properties['revision_id'] = array(
      'label' => t('Revision ID'),
      'description' => t('The revision ID.'),
      'type' => 'integer_field',
    );
    $properties['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The comment language code.'),
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
    return $properties;
  }

}
