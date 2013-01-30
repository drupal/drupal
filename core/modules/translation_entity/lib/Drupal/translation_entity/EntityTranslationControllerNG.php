<?php

/**
 * @file
 * Contains \Drupal\translation_entity\EntityTranslationControllerNG.
 */

namespace Drupal\translation_entity;

use Drupal\Core\Entity\EntityInterface;

/**
 * Test entity translation controller.
 */
class EntityTranslationControllerNG extends EntityTranslationController {

  /**
   * Overrides \Drupal\translation_entity\EntityTranslationController::getAccess().
   */
  public function getAccess(EntityInterface $entity, $op) {
    return $entity->access($op);
  }

  /**
   * Overrides \Drupal\translation_entity\EntityTranslationControllerInterface::removeTranslation().
   */
  public function removeTranslation(EntityInterface $entity, $langcode) {
    $translation = $entity->getTranslation($langcode);
    foreach ($translation->getPropertyDefinitions() as $property_name => $langcode) {
      $translation->$property_name = array();
    }
  }

}
