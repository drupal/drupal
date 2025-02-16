<?php

namespace Drupal\comment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\content_translation\ContentTranslationHandler;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the translation handler for comments.
 */
class CommentTranslationHandler extends ContentTranslationHandler {

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(array &$form, FormStateInterface $form_state, EntityInterface $entity) {
    parent::entityFormAlter($form, $form_state, $entity);

    if (isset($form['content_translation'])) {
      // We do not need to show these values on comment forms: they inherit the
      // basic comment property values.
      $form['content_translation']['status']['#access'] = FALSE;
      $form['content_translation']['name']['#access'] = FALSE;
      $form['content_translation']['created']['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function entityFormTitle(EntityInterface $entity) {
    return $this->t('Edit comment @subject', ['@subject' => $entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function entityFormEntityBuild($entity_type, EntityInterface $entity, array $form, FormStateInterface $form_state) {
    if ($form_state->hasValue('content_translation')) {
      $translation = &$form_state->getValue('content_translation');
      /** @var \Drupal\comment\CommentInterface $entity */
      $translation['status'] = $entity->isPublished();
      $translation['name'] = $entity->getAuthorName();
    }
    parent::entityFormEntityBuild($entity_type, $entity, $form, $form_state);
  }

}
