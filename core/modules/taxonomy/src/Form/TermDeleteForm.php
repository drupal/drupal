<?php

namespace Drupal\taxonomy\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a deletion confirmation form for taxonomy term.
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
    return $this->t('Deleted term %name.', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $term */
    $term = $this->getEntity();
    if ($term->isDefaultTranslation()) {
      $storage = $this->entityManager->getStorage('taxonomy_vocabulary');
      $vocabulary = $storage->load($this->entity->bundle());

      // @todo Move to storage http://drupal.org/node/1988712
      taxonomy_check_vocabulary_hierarchy($vocabulary, array('tid' => $term->id()));
    }
  }

}
