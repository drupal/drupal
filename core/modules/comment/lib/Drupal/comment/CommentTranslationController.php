<?php


/**
 * @file
 * Definition of Drupal\comment\CommentTranslationController.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\translation_entity\EntityTranslationControllerNG;

/**
 * Defines the translation controller class for comments.
 */
class CommentTranslationController extends EntityTranslationControllerNG {

  /**
   * Overrides EntityTranslationController::entityFormTitle().
   */
  protected function entityFormTitle(EntityInterface $entity) {
    return t('Edit comment @subject', array('@subject' => $entity->label()));
  }

}
