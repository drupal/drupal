<?php

/**
 * @file
 * Contains \Drupal\custom_block\CustomBlockStorageController.
 */

namespace Drupal\custom_block;

use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Controller class for custom blocks.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageControllerNG class,
 * adding required special handling for custom block entities.
 */
class CustomBlockStorageController extends DatabaseStorageControllerNG implements EntityStorageControllerInterface {

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
      'type' => 'uuid_field',
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
