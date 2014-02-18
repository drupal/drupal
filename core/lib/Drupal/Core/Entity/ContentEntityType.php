<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityType.
 */

namespace Drupal\Core\Entity;

/**
 * Defines a config entity type annotation object.
 */
class ContentEntityType extends EntityType {

  /**
   * {@inheritdoc}
   */
  public function getControllerClasses() {
    return parent::getControllerClasses() + array(
      'storage' => 'Drupal\Core\Entity\FieldableDatabaseStorageController',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigPrefix() {
    return FALSE;
  }

}
