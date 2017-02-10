<?php

namespace Drupal\workflows\Form;

use Drupal\workflows\Entity\Workflow;
use Drupal\workflows\State;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * The form for editing workflows.
 */
class WorkflowEditForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $workflow->label(),
      '#description' => $this->t('Label for the Workflow.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $workflow->id(),
      '#machine_name' => [
        'exists' => [Workflow::class, 'load'],
      ],
      '#disabled' => TRUE,
    ];

    $header = [
      'state' => $this->t('State'),
      'weight' => $this->t('Weight'),
      'operations' => $this->t('Operations')
    ];
    $form['states_container'] = [
      '#type' => 'details',
      '#title' => $this->t('States'),
      '#open' => TRUE,
      '#collapsible' => 'FALSE',
    ];
    $form['states_container']['states'] = [
      '#type' => 'table',
      '#header' => $header,
      '#title' => $this->t('States'),
      '#empty' => $this->t('There are no states yet.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'state-weight',
        ],
      ],
    ];

    $states = $workflow->getStates();

    // Warn the user if there are no states.
    if (empty($states)) {
      drupal_set_message(
        $this->t(
          'This workflow has no states and will be disabled until there is at least one, <a href=":add-state">add a new state.</a>',
          [':add-state' => $workflow->toUrl('add-state-form')->toString()]
        ),
        'warning'
      );
    }

    foreach ($states as $state) {
      $links = [
        'edit' => [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('entity.workflow.edit_state_form', ['workflow' => $workflow->id(), 'workflow_state' => $state->id()]),
          'attributes' => ['aria-label' => $this->t('Edit @state state', ['@state' => $state->label()])],
        ]
      ];
      if ($this->entity->access('delete-state:' . $state->id())) {
        $links['delete'] = [
          'title' => t('Delete'),
          'url' => Url::fromRoute('entity.workflow.delete_state_form', [
            'workflow' => $workflow->id(),
            'workflow_state' => $state->id()
          ]),
          'attributes' => ['aria-label' => $this->t('Delete @state state', ['@state' => $state->label()])],
        ];
      }
      $form['states_container']['states'][$state->id()] = [
        '#attributes' => ['class' => ['draggable']],
        'state' => ['#markup' => $state->label()],
        '#weight' => $state->weight(),
        'weight' => [
          '#type' => 'weight',
          '#title' => t('Weight for @title', ['@title' => $state->label()]),
          '#title_display' => 'invisible',
          '#default_value' => $state->weight(),
          '#attributes' => ['class' => ['state-weight']],
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];
    }
    $form['states_container']['state_add'] = [
      '#markup' => $workflow->toLink($this->t('Add a new state'), 'add-state-form')->toString(),
    ];

    $header = [
      'label' => $this->t('Label'),
      'weight' => $this->t('Weight'),
      'from' => $this->t('From'),
      'to' => $this->t('To'),
      'operations' => $this->t('Operations')
    ];
    $form['transitions_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Transitions'),
      '#open' => TRUE,
    ];
    $form['transitions_container']['transitions'] = [
      '#type' => 'table',
      '#header' => $header,
      '#title' => $this->t('Transitions'),
      '#empty' => $this->t('There are no transitions yet.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'transition-weight',
        ],
      ],
    ];
    foreach ($workflow->getTransitions() as $transition) {
      $links['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('entity.workflow.edit_transition_form', ['workflow' => $workflow->id(), 'workflow_transition' => $transition->id()]),
        'attributes' => ['aria-label' => $this->t('Edit \'@transition\' transition', ['@transition' => $transition->label()])],
      ];
      $links['delete'] = [
        'title' => t('Delete'),
        'url' => Url::fromRoute('entity.workflow.delete_transition_form', ['workflow' => $workflow->id(), 'workflow_transition' => $transition->id()]),
        'attributes' => ['aria-label' => $this->t('Delete \'@transition\' transition', ['@transition' => $transition->label()])],
      ];
      $form['transitions_container']['transitions'][$transition->id()] = [
        '#attributes' => ['class' => ['draggable']],
        'label' => ['#markup' => $transition->label()],
        '#weight' => $transition->weight(),
        'weight' => [
          '#type' => 'weight',
          '#title' => t('Weight for @title', ['@title' => $transition->label()]),
          '#title_display' => 'invisible',
          '#default_value' => $transition->weight(),
          '#attributes' => ['class' => ['transition-weight']],
        ],
        'from' => [
          '#theme' => 'item_list',
          '#items' => array_map([State::class, 'labelCallback'], $transition->from()),
          '#context' => ['list_style' => 'comma-list'],
        ],
        'to' => ['#markup' => $transition->to()->label()],
        'operations' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];
    }
    $form['transitions_container']['transition_add'] = [
      '#markup' => $workflow->toLink($this->t('Add a new transition'), 'add-transition-form')->toString(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->entity;
    $workflow->save();
    drupal_set_message($this->t('Saved the %label Workflow.', ['%label' => $workflow->label()]));
    $form_state->setRedirectUrl($workflow->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // This form can only set the workflow's ID, label and the weights for each
    // state.
    /** @var \Drupal\workflows\WorkflowInterface $entity */
    $values = $form_state->getValues();
    $entity->set('label', $values['label']);
    $entity->set('id', $values['id']);
    foreach ($values['states'] as $state_id => $state_values) {
      $entity->setStateWeight($state_id, $state_values['weight']);
    }
    foreach ($values['transitions'] as $transition_id => $transition_values) {
      $entity->setTransitionWeight($transition_id, $transition_values['weight']);
    }
  }

}
