<?php


/**
 * @file
 * Definition of Drupal\comment\CommentTranslationController.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\content_translation\ContentTranslationController;

/**
 * Defines the translation controller class for comments.
 */
class CommentTranslationController extends ContentTranslationController {

  /**
   * Overrides ContentTranslationController::entityFormTitle().
   */
  protected function entityFormTitle(EntityInterface $entity) {
    return t('Edit comment @subject', array('@subject' => $entity->label()));
  }

}
