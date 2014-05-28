<?php

/**
 * @file
 * Contains \Drupal\comment\CommentTranslationHandler.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\content_translation\ContentTranslationHandler;

/**
 * Defines the translation handler for comments.
 */
class CommentTranslationHandler extends ContentTranslationHandler {

  /**
   * {@inheritdoc}
   */
  protected function entityFormTitle(EntityInterface $entity) {
    return t('Edit comment @subject', array('@subject' => $entity->label()));
  }

}
