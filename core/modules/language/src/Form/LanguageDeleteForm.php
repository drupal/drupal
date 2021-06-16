<?php

namespace Drupal\language\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Defines a confirmation form for deleting a language entity.
 *
 * @internal
 */
class LanguageDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting a language will remove all interface translations associated with it, and content in this language will be set to be language neutral. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('The %language (%langcode) language has been removed.', ['%language' => $this->entity->label(), '%langcode' => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function logDeletionMessage() {
    $this->logger('language')->notice('The %language (%langcode) language has been removed.', ['%language' => $this->entity->label(), '%langcode' => $this->entity->id()]);
  }

}
