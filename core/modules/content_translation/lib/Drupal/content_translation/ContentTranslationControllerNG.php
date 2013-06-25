<?php

/**
 * @file
 * Contains \Drupal\content_translation\ContentTranslationControllerNG.
 */

namespace Drupal\content_translation;

use Drupal\Core\Entity\EntityInterface;

/**
 * Test content translation controller.
 */
class ContentTranslationControllerNG extends ContentTranslationController {

  /**
   * Overrides \Drupal\content_translation\ContentTranslationController::getAccess().
   */
  public function getAccess(EntityInterface $entity, $op) {
    return $entity->access($op);
  }

  /**
   * Overrides \Drupal\content_translation\ContentTranslationControllerInterface::removeTranslation().
   */
  public function removeTranslation(EntityInterface $entity, $langcode) {
    $translation = $entity->getTranslation($langcode);
    foreach ($translation->getPropertyDefinitions() as $property_name => $langcode) {
      $translation->$property_name = array();
    }
  }

}
