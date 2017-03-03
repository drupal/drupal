<?php

namespace Drupal\workflows\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class WorkflowStateEditForm.
 */
class WorkflowStateEditForm extends EntityForm {

  /**
   * The ID of the state that is being edited.
   *
   * @var string
   */
  protected $stateId;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_state_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $workflow_state = NULL) {
    $this->stateId = $workflow_state;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->getEntity();
    $state = $workflow->getState($this->stateId);
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $state->label(),
      '#description' => $this->t('Label for the state.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->stateId,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#disabled' => TRUE,
    ];

    // Add additional form fields from the workflow type plugin.
    $form['type_settings'] = [
      $workflow->get('type') => $workflow->getTypePlugin()->buildStateConfigurationForm($form_state, $workflow, $state),
      '#tree' => TRUE,
    ];

    $header = [
      'label' => $this->t('Transition'),
      'state' => $this->t('To'),
      'operations' => $this->t('Operations'),
    ];
    $form['transitions'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('There are no transitions to or from this state yet.'),
    ];
    foreach ($state->getTransitions() as $transition) {
      $links['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('entity.workflow.edit_transition_form', [
          'workflow' => $workflow->id(),
          'workflow_transition' => $transition->id()
        ]),
      ];
      $links['delete'] = [
        'title' => t('Delete'),
        'url' => Url::fromRoute('entity.workflow.delete_transition_form', [
          'workflow' => $workflow->id(),
          'workflow_transition' => $transition->id()
        ]),
      ];
      $form['transitions'][$transition->id()] = [
        'label' => [
          '#markup' => $transition->label(),
        ],
        'state' => [
          '#markup' => $transition->to()->label(),
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];
    }

    return $form;
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
    /** @var \Drupal\workflows\WorkflowInterface $entity */
    $values = $form_state->getValues();
    $entity->setStateLabel($values['id'], $values['label']);
    if (isset($values['type_settings'])) {
      $configuration = $entity->getTypePlugin()->getConfiguration();
      $configuration['states'][$values['id']] = $values['type_settings'][$entity->getTypePlugin()->getPluginId()];
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
    drupal_set_message($this->t('Saved %label state.', [
      '%label' => $workflow->getState($this->stateId)->label(),
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
      '#access' => $this->entity->access('delete-state:' . $this->stateId),
      '#attributes' => [
        'class' => ['button', 'button--danger'],
      ],
      '#url' => Url::fromRoute('entity.workflow.delete_state_form', [
        'workflow' => $this->entity->id(),
        'workflow_state' => $this->stateId
      ])
    ];

    return $actions;
  }

}
