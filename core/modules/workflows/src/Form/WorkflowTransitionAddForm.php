<?php

namespace Drupal\workflows\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\workflows\State;
use Drupal\workflows\TransitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WorkflowTransitionAddForm.
 *
 * @internal
 */
class WorkflowTransitionAddForm extends EntityForm {

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
    return 'workflow_transition_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->getEntity();
    $workflow_type = $workflow->getTypePlugin();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Transition label'),
      '#maxlength' => 255,
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
    ];

    // @todo https://www.drupal.org/node/2830584 Add some ajax to ensure that
    //   only valid transitions are selectable.
    $states = array_map([State::class, 'labelCallback'], $workflow->getTypePlugin()->getStates());
    $form['from'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('From'),
      '#required' => TRUE,
      '#default_value' => [],
      '#options' => $states,
    ];
    $form['to'] = [
      '#type' => 'radios',
      '#title' => $this->t('To'),
      '#required' => TRUE,
      '#options' => $states,
    ];

    // Add additional form fields from the workflow type plugin.
    if ($workflow_type->hasFormClass(TransitionInterface::PLUGIN_FORM_KEY)) {
      $form['type_settings'] = [
        '#tree' => TRUE,
      ];
      $subform_state = SubformState::createForSubform($form['type_settings'], $form, $form_state);
      $form['type_settings'] += $this->pluginFormFactory
        ->createInstance($workflow_type, TransitionInterface::PLUGIN_FORM_KEY)
        ->buildConfigurationForm($form['type_settings'], $subform_state);
    }

    return $form;
  }

  /**
   * Determines if the workflow transition already exists.
   *
   * @param string $transition_id
   *   The workflow transition ID.
   *
   * @return bool
   *   TRUE if the workflow transition exists, FALSE otherwise.
   */
  public function exists($transition_id) {
    /** @var \Drupal\workflows\WorkflowInterface $original_workflow */
    $original_workflow = \Drupal::entityTypeManager()->getStorage('workflow')->loadUnchanged($this->getEntity()->id());
    return $original_workflow->getTypePlugin()->hasTransition($transition_id);
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
    $entity->getTypePlugin()->addTransition($values['id'], $values['label'], array_filter($values['from']), $values['to']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->getEntity();
    $workflow_type = $workflow->getTypePlugin();

    $values = $form_state->getValues();
    foreach (array_filter($values['from']) as $from_state_id) {
      if ($workflow->getTypePlugin()->hasTransitionFromStateToState($from_state_id, $values['to'])) {
        $form_state->setErrorByName('from][' . $from_state_id, $this->t('The transition from %from to %to already exists.', [
          '%from' => $workflow->getTypePlugin()->getState($from_state_id)->label(),
          '%to' => $workflow->getTypePlugin()->getState($values['to'])->label(),
        ]));
      }
    }

    if ($workflow_type->hasFormClass(TransitionInterface::PLUGIN_FORM_KEY)) {
      $subform_state = SubformState::createForSubform($form['type_settings'], $form, $form_state);
      $this->pluginFormFactory
        ->createInstance($workflow_type, TransitionInterface::PLUGIN_FORM_KEY)
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
    $transition = $workflow_type->getTransition($form_state->getValue('id'));

    if ($workflow_type->hasFormClass(TransitionInterface::PLUGIN_FORM_KEY)) {
      $subform_state = SubformState::createForSubform($form['type_settings'], $form, $form_state);
      $subform_state->set('transition', $transition);
      $this->pluginFormFactory
        ->createInstance($workflow_type, TransitionInterface::PLUGIN_FORM_KEY)
        ->submitConfigurationForm($form['type_settings'], $subform_state);
    }

    $workflow->save();
    $this->messenger()->addStatus($this->t('Created %label transition.', [
      '%label' => $form_state->getValue('label'),
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
    return $actions;
  }

}
