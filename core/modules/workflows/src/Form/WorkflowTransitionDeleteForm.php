<?php

namespace Drupal\workflows\Form;

use Drupal\workflows\WorkflowInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the form to delete transitions from Workflow entities.
 *
 * @internal
 */
class WorkflowTransitionDeleteForm extends ConfirmFormBase {

  /**
   * The workflow entity the transition being deleted belongs to.
   *
   * @var \Drupal\workflows\WorkflowInterface
   */
  protected $workflow;

  /**
   * The workflow transition being deleted.
   *
   * @var \Drupal\workflows\TransitionInterface
   */
  protected $transition;

  /**
   * The transition being deleted.
   *
   * @var string
   */
  protected $transitionId;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_transition_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %transition from %workflow?', ['%transition' => $this->transition->label(), '%workflow' => $this->workflow->label()]);
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
   * @param string|null $workflow_transition
   *   The workflow transition being deleted.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?WorkflowInterface $workflow = NULL, $workflow_transition = NULL) {
    try {
      $this->transition = $workflow->getTypePlugin()->getTransition($workflow_transition);
    }
    catch (\InvalidArgumentException) {
      throw new NotFoundHttpException();
    }
    $this->workflow = $workflow;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->workflow
      ->getTypePlugin()
      ->deleteTransition($this->transition->id());
    $this->workflow->save();

    $this->messenger()->addStatus($this->t('%transition transition deleted.', ['%transition' => $this->transition->label()]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
