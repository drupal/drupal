<?php

namespace Drupal\workflows\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Url;
use Drupal\workflows\State;
use Drupal\workflows\TransitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity form variant for editing workflow transitions.
 *
 * @internal
 */
class WorkflowTransitionEditForm extends EntityForm {

  /**
   * The ID of the transition that is being edited.
   *
   * @var string
   */
  protected $transitionId;

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

    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->getEntity();
    $workflow_type = $workflow->getTypePlugin();
    $transition = $workflow->getTypePlugin()->getTransition($this->transitionId);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Transition label'),
      '#maxlength' => 255,
      '#default_value' => $transition->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'value',
      '#value' => $this->transitionId,
    ];

    // @todo https://www.drupal.org/node/2830584 Add some ajax to ensure that
    //   only valid transitions are selectable.
    $states = array_map([State::class, 'labelCallback'], $workflow->getTypePlugin()->getStates());
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
    if ($workflow_type->hasFormClass(TransitionInterface::PLUGIN_FORM_KEY)) {
      $form['type_settings'] = [
        '#tree' => TRUE,
      ];
      $subform_state = SubformState::createForSubform($form['type_settings'], $form, $form_state);
      $subform_state->set('transition', $transition);
      $form['type_settings'] += $this->pluginFormFactory
        ->createInstance($workflow_type, TransitionInterface::PLUGIN_FORM_KEY)
        ->buildConfigurationForm($form['type_settings'], $subform_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->getEntity();
    $workflow_type = $workflow->getTypePlugin();
    $transition = $workflow_type->getTransition($this->transitionId);

    $values = $form_state->getValues();
    foreach (array_filter($values['from']) as $from_state_id) {
      if ($workflow_type->hasTransitionFromStateToState($from_state_id, $values['to'])) {
        $existing_transition = $workflow_type->getTransitionFromStateToState($from_state_id, $values['to']);
        if ($existing_transition->id() !== $values['id']) {
          $form_state->setErrorByName('from][' . $from_state_id, $this->t('The transition from %from to %to already exists.', [
            '%from' => $workflow->getTypePlugin()->getState($from_state_id)->label(),
            '%to' => $workflow->getTypePlugin()->getState($values['to'])->label(),
          ]));
        }
      }
    }

    if ($workflow_type->hasFormClass(TransitionInterface::PLUGIN_FORM_KEY)) {
      $subform_state = SubformState::createForSubform($form['type_settings'], $form, $form_state);
      $subform_state->set('transition', $transition);
      $this->pluginFormFactory
        ->createInstance($workflow_type, TransitionInterface::PLUGIN_FORM_KEY)
        ->validateConfigurationForm($form['type_settings'], $subform_state);
    }
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
    $form_state->set('created_transition', FALSE);
    $entity->getTypePlugin()->setTransitionLabel($values['id'], $values['label']);
    $entity->getTypePlugin()->setTransitionFromStates($values['id'], array_filter($values['from']));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->entity;
    $workflow_type = $workflow->getTypePlugin();
    $transition = $workflow_type->getTransition($this->transitionId);

    if ($workflow_type->hasFormClass(TransitionInterface::PLUGIN_FORM_KEY)) {
      $subform_state = SubformState::createForSubform($form['type_settings'], $form, $form_state);
      $subform_state->set('transition', $transition);
      $this->pluginFormFactory
        ->createInstance($workflow_type, TransitionInterface::PLUGIN_FORM_KEY)
        ->submitConfigurationForm($form['type_settings'], $subform_state);
    }

    $workflow->save();
    $this->messenger()->addStatus($this->t('Saved %label transition.', [
      '%label' => $workflow->getTypePlugin()->getTransition($this->transitionId)->label(),
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
        'workflow_transition' => $this->transitionId,
      ]),
    ];

    return $actions;
  }

}
