<?php

namespace Drupal\workflows\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\workflows\Entity\Workflow;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adding workflows.
 */
class WorkflowAddForm extends EntityForm {

  /**
   * The workflow type plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $workflowTypePluginManager;

  /**
   * WorkflowAddForm constructor.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $workflow_type_plugin_manager
   *   The workflow type plugin manager.
   */
  public function __construct(PluginManagerInterface $workflow_type_plugin_manager) {
    $this->workflowTypePluginManager = $workflow_type_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.workflows.type')
    );
  }

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
    ];

    $workflow_types = array_map(function ($plugin_definition) {
      return $plugin_definition['label'];
    }, $this->workflowTypePluginManager->getDefinitions());
    $form['workflow_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Workflow type'),
      '#required' => TRUE,
      '#options' => $workflow_types,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->entity;
    // Initialize the workflow using the selected type plugin.
    $workflow = $workflow->getTypePlugin()->initializeWorkflow($workflow);
    $return = $workflow->save();
    if (empty($workflow->getStates())) {
      drupal_set_message($this->t('Created the %label Workflow. In order for the workflow to be enabled there needs to be at least one state.', [
        '%label' => $workflow->label(),
      ]));
      $form_state->setRedirectUrl($workflow->toUrl('add-state-form'));
    }
    else {
      drupal_set_message($this->t('Created the %label Workflow.', [
        '%label' => $workflow->label(),
      ]));
      $form_state->setRedirectUrl($workflow->toUrl('edit-form'));
    }
    return $return;
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
    $entity->set('type', $values['workflow_type']);
  }

}
