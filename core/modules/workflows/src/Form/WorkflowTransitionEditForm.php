<?php

namespace Drupal\workflows\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\workflows\State;

/**
 * Class WorkflowTransitionEditForm.
 */
class WorkflowTransitionEditForm extends EntityForm {

  /**
   * The ID of the transition that is being edited.
   *
   * @var string
   */
  protected $transitionId;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_transition_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $workflow_transition = NULL) {
    $this->transitionId = $workflow_transition;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->getEntity();
    $transition = $workflow->getTransition($this->transitionId);
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $transition->label(),
      '#description' => $this->t('Label for the transition.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'value',
      '#value' => $this->transitionId,
    ];

    // @todo https://www.drupal.org/node/2830584 Add some ajax to ensure that
    //   only valid transitions are selectable.
    $states = array_map([State::class, 'labelCallback'], $workflow->getStates());
    $form['from'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('From'),
      '#required' => TRUE,
      '#default_value' => array_keys($transition->from()),
      '#options' => $states,
    ];
    $form['to'] = [
      '#type' => 'radios',
      '#title' => $this->t('To'),
      '#required' => TRUE,
      '#default_value' => $transition->to()->id(),
      '#options' => $states,
      '#disabled' => TRUE,
    ];

    // Add additional form fields from the workflow type plugin.
    $form['type_settings'] = [
      $workflow->get('type') => $workflow->getTypePlugin()->buildTransitionConfigurationForm($form_state, $workflow, $transition),
      '#tree' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->getEntity();
    $values = $form_state->getValues();
    foreach (array_filter($values['from']) as $from_state_id) {
      if ($workflow->hasTransitionFromStateToState($from_state_id, $values['to'])) {
        $transition = $workflow->getTransitionFromStateToState($from_state_id, $values['to']);
        if ($transition->id() !== $values['id']) {
          $form_state->setErrorByName('from][' . $from_state_id, $this->t('The transition from %from to %to already exists.', [
            '%from' => $workflow->getState($from_state_id)->label(),
            '%to' => $workflow->getState($values['to'])->label(),
          ]));
        }
      }
    }
  }

  /**
   * Copies top-level form values to entity properties
   *
   * This form can only change values for a state, which is part of workflow.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the current form should operate upon.
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    if (!$form_state->isValidationComplete()) {
      // Only do something once form validation is complete.
      return;
    }
    /** @var \Drupal\workflows\WorkflowInterface $entity */
    $values = $form_state->getValues();
    $form_state->set('created_transition', FALSE);
    $entity->setTransitionLabel($values['id'], $values['label']);
    $entity->setTransitionFromStates($values['id'], array_filter($values['from']));
    if (isset($values['type_settings'])) {
      $configuration = $entity->getTypePlugin()->getConfiguration();
      $configuration['transitions'][$values['id']] = $values['type_settings'][$entity->getTypePlugin()->getPluginId()];
      $entity->set('type_settings', $configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->entity;
    $workflow->save();
    drupal_set_message($this->t('Saved %label transition.', [
      '%label' => $workflow->getTransition($this->transitionId)->label(),
    ]));
    $form_state->setRedirectUrl($workflow->toUrl('edit-form'));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => ['::submitForm', '::save'],
    ];

    $actions['delete'] = [
      '#type' => 'link',
      '#title' => $this->t('Delete'),
      // Deleting a transition is editing a workflow.
      '#access' => $this->entity->access('edit'),
      '#attributes' => [
        'class' => ['button', 'button--danger'],
      ],
      '#url' => Url::fromRoute('entity.workflow.delete_transition_form', [
        'workflow' => $this->entity->id(),
        'workflow_transition' => $this->transitionId
      ])
    ];

    return $actions;
  }

}
