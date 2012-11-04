<?php

/**
 * @file
 * Definition of Drupal\translation_entity\EntityTranslationController.
 */

namespace Drupal\entity_test;

use Drupal\Core\Entity\EntityInterface;
use Drupal\translation_entity\EntityTranslationController;

/**
 * Test entity translation controller.
 */
class EntityTestTranslationController extends EntityTranslationController {

  /**
   * Overrides EntityTranslationControllerInterface::removeTranslation().
   */
  public function removeTranslation(EntityInterface $entity, $langcode) {
    $translation = $entity->getTranslation($langcode);
    foreach ($translation->getPropertyDefinitions() as $property_name => $langcode) {
      $translation->$property_name = array();
    }
  }

}
