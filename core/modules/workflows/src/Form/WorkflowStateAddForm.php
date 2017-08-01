<?php

namespace Drupal\workflows\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\workflows\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WorkflowStateAddForm.
 */
class WorkflowStateAddForm extends EntityForm {

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
    return 'workflow_state_add_form';
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
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => '',
      '#description' => $this->t('Label for the state.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
    ];

    if ($workflow_type->hasFormClass(StateInterface::PLUGIN_FORM_KEY)) {
      $form['type_settings'] = [
        '#tree' => TRUE,
      ];
      $subform_state = SubformState::createForSubform($form['type_settings'], $form, $form_state);
      $form['type_settings'] += $this->pluginFormFactory
        ->createInstance($workflow_type, StateInterface::PLUGIN_FORM_KEY)
        ->buildConfigurationForm($form['type_settings'], $subform_state);
    }

    return $form;
  }

  /**
   * Determines if the workflow state already exists.
   *
   * @param string $state_id
   *   The workflow state ID.
   *
   * @return bool
   *   TRUE if the workflow state exists, FALSE otherwise.
   */
  public function exists($state_id) {
    /** @var \Drupal\workflows\WorkflowInterface $original_workflow */
    $original_workflow = \Drupal::entityTypeManager()->getStorage('workflow')->loadUnchanged($this->getEntity()->id());
    return $original_workflow->getTypePlugin()->hasState($state_id);
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
    $type_plugin = $entity->getTypePlugin();

    // Replicate the validation that Workflow::addState() does internally as the
    // form values have not been validated at this point.
    if (!$type_plugin->hasState($values['id']) && !preg_match('/[^a-z0-9_]+/', $values['id'])) {
      $type_plugin->addState($values['id'], $values['label']);
    }
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
    $state = $workflow_type->getState($form_state->getValue('id'));

    if ($workflow_type->hasFormClass(StateInterface::PLUGIN_FORM_KEY)) {
      $subform_state = SubformState::createForSubform($form['type_settings'], $form, $form_state);
      $subform_state->set('state', $state);
      $this->pluginFormFactory
        ->createInstance($workflow_type, StateInterface::PLUGIN_FORM_KEY)
        ->submitConfigurationForm($form['type_settings'], $subform_state);
    }

    $workflow->save();
    drupal_set_message($this->t('Created %label state.', [
      '%label' => $workflow->getTypePlugin()->getState($form_state->getValue('id'))->label(),
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
