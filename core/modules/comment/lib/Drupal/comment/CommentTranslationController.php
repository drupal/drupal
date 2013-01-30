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
   * Overrides EntityTranslationController::getAccess().
   */
  public function getAccess(EntityInterface $entity, $op) {
    switch ($op) {
      case 'view':
        return user_access('access comments');
      case 'update':
        return comment_access('edit', $entity);
      case 'delete':
        return user_access('administer comments');
      case 'create':
        return user_access('post comments');
    }
    return parent::getAccess($entity, $op);
  }

  /**
   * Overrides EntityTranslationController::entityFormTitle().
   */
  protected function entityFormTitle(EntityInterface $entity) {
    return t('Edit comment @subject', array('@subject' => $entity->label()));
  }

}
