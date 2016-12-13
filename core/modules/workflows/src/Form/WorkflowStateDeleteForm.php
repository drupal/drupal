<?php

namespace Drupal\workflows\Form;

use Drupal\workflows\WorkflowInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the form to delete states from Workflow entities.
 */
class WorkflowStateDeleteForm extends ConfirmFormBase {

  /**
   * The workflow entity the state being deleted belongs to.
   *
   * @var \Drupal\workflows\WorkflowInterface
   */
  protected $workflow;

  /**
   * The state being deleted.
   *
   * @var string
   */
  protected $stateId;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_state_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %state from %workflow?', ['%state' => $this->workflow->getState($this->stateId)->label(), '%workflow' => $this->workflow->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->workflow->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *   The workflow entity being edited.
   * @param string|null $workflow_state
   *   The workflow state being deleted.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, WorkflowInterface $workflow = NULL, $workflow_state = NULL) {
    if (!$workflow->hasState($workflow_state)) {
      throw new NotFoundHttpException();
    }
    $this->workflow = $workflow;
    $this->stateId = $workflow_state;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $workflow_label = $this->workflow->getState($this->stateId)->label();
    $this->workflow
      ->deleteState($this->stateId)
      ->save();

    drupal_set_message($this->t(
      'State %label deleted.',
      ['%label' => $workflow_label]
    ));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
