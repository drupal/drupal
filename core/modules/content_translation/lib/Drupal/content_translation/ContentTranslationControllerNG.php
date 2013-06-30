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
   * Overrides \Drupal\content_translation\ContentTranslationControllerInterface::removeTranslation().
   */
  public function removeTranslation(EntityInterface $entity, $langcode) {
    $entity->removeTranslation($langcode);
  }

}
