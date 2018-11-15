<?php

namespace Drupal\taxonomy\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a deletion confirmation form for taxonomy term.
 *
 * @internal
 */
class TermDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // The cancel URL is the vocabulary collection, terms have no global
    // list page.
    return new Url('entity.taxonomy_vocabulary.collection');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting a term will delete all its children if there are any. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('Deleted term %name.', ['%name' => $this->entity->label()]);
  }

}
