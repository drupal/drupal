<?php

namespace Drupal\workflows\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Url;
use Drupal\workflows\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity form variant for editing workflow states.
 *
 * @internal
 */
class WorkflowStateEditForm extends EntityForm {

  /**
   * The ID of the state that is being edited.
   *
   * @var string
   */
  protected $stateId;

  /**
   * The plugin form factory.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * Creates an instance of WorkflowStateEditForm.
   *
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $pluginFormFactory
   *   The plugin form factory.
   */
  public function __construct(PluginFormFactoryInterface $pluginFormFactory) {
    $this->pluginFormFactory = $pluginFormFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin_form.factory')
    );
  }

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
    $workflow_type = $workflow->getTypePlugin();
    $state = $workflow->getTypePlugin()->getState($this->stateId);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('State label'),
      '#maxlength' => 255,
      '#default_value' => $state->label(),
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
    if ($workflow_type->hasFormClass(StateInterface::PLUGIN_FORM_KEY)) {
      $form['type_settings'] = [
        '#tree' => TRUE,
      ];
      $subform_state = SubformState::createForSubform($form['type_settings'], $form, $form_state);
      $subform_state->set('state', $state);
      $form['type_settings'] += $this->pluginFormFactory
        ->createInstance($workflow_type, StateInterface::PLUGIN_FORM_KEY)
        ->buildConfigurationForm($form['type_settings'], $subform_state);
    }

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
          'workflow_transition' => $transition->id(),
        ]),
      ];
      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('entity.workflow.delete_transition_form', [
          'workflow' => $workflow->id(),
          'workflow_transition' => $transition->id(),
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
   * Copies top-level form values to entity properties.
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
    $entity->getTypePlugin()->setStateLabel($values['id'], $values['label']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    /** @var \Drupal\workflows\WorkflowTypeInterface $workflow_type */
    $workflow = $this->entity;
    $workflow_type = $workflow->getTypePlugin();

    if ($workflow_type->hasFormClass(StateInterface::PLUGIN_FORM_KEY)) {
      $subform_state = SubformState::createForSubform($form['type_settings'], $form, $form_state);
      $subform_state->set('state', $workflow_type->getState($this->stateId));
      $this->pluginFormFactory
        ->createInstance($workflow_type, StateInterface::PLUGIN_FORM_KEY)
        ->validateConfigurationForm($form['type_settings'], $subform_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->entity;
    $workflow_type = $workflow->getTypePlugin();

    if ($workflow_type->hasFormClass(StateInterface::PLUGIN_FORM_KEY)) {
      $subform_state = SubformState::createForSubform($form['type_settings'], $form, $form_state);
      $subform_state->set('state', $workflow_type->getState($this->stateId));
      $this->pluginFormFactory
        ->createInstance($workflow_type, StateInterface::PLUGIN_FORM_KEY)
        ->submitConfigurationForm($form['type_settings'], $subform_state);
    }

    $workflow->save();
    $this->messenger()->addStatus($this->t('Saved %label state.', [
      '%label' => $workflow->getTypePlugin()->getState($this->stateId)->label(),
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
        'workflow_state' => $this->stateId,
      ]),
    ];

    return $actions;
  }

}
