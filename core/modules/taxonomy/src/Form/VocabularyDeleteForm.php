<?php

namespace Drupal\taxonomy\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Provides a deletion confirmation form for taxonomy vocabulary.
 *
 * @internal
 */
class VocabularyDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_vocabulary_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the vocabulary %title?', ['%title' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting a vocabulary will delete all the terms in it. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('Deleted vocabulary %name.', ['%name' => $this->entity->label()]);
  }

}
