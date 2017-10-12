<?php

namespace Drupal\workflows\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Workflow entities.
 *
 * @internal
 */
class WorkflowDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->entity->getTypePlugin()->workflowHasData($this->entity)) {
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $this->t('This workflow is in use. You cannot remove this workflow until you have removed all content using it.')];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.workflow.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    drupal_set_message($this->t(
      'Workflow %label deleted.',
      ['%label' => $this->entity->label()]
    ));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
