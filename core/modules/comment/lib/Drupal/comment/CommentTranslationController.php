<?php


/**
 * @file
 * Definition of Drupal\comment\CommentTranslationController.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\translation_entity\EntityTranslationController;

/**
 * Defines the translation controller class for comments.
 */
class CommentTranslationController extends EntityTranslationController {

  /**
   * Overrides EntityTranslationController::entityFormTitle().
   */
  protected function entityFormTitle(EntityInterface $entity) {
    return t('Edit comment @subject', array('@subject' => $entity->label()));
  }

}
