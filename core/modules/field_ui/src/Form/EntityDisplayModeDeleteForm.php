<?php

/**
 * @file
 * Contains \Drupal\field_ui\Form\EntityDisplayModeDeleteForm.
 */

namespace Drupal\field_ui\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the delete form for entity display modes.
 */
class EntityDisplayModeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->urlInfo('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity_type = $this->entity->getEntityType();
    return t('Are you sure you want to delete the %label @entity-type?', array('%label' => $this->entity->label(), '@entity-type' => $entity_type->getLowercaseLabel()));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $entity_type = $this->entity->getEntityType();
    return t('Deleting a @entity-type will cause any output still requesting to use that @entity-type to use the default display settings.', array('@entity-type' => $entity_type->getLowercaseLabel()));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type = $this->entity->getEntityType();
    drupal_set_message(t('Deleted the %label @entity-type.', array('%label' => $this->entity->label(), '@entity-type' => strtolower($entity_type->getLabel()))));
    $this->entity->delete();
    \Drupal::entityManager()->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
