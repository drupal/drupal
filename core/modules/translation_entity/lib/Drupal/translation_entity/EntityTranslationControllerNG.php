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
   * Overrides EntityTranslationController::removeTranslation().
   */
  public function removeTranslation(EntityInterface $entity, $langcode) {
    $translation = $entity->getTranslation($langcode);
    foreach ($translation->getPropertyDefinitions() as $property_name => $langcode) {
      $translation->$property_name = array();
    }
  }
}
