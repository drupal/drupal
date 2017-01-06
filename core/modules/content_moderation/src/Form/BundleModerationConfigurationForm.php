<?php

namespace Drupal\content_moderation\Form;

use Drupal\content_moderation\Plugin\WorkflowType\ContentModeration;
use Drupal\workflows\WorkflowInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for configuring moderation usage on a given entity bundle.
 */
class BundleModerationConfigurationForm extends EntityForm {

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   *
   * Blank out the base form ID so that form alters that use the base form ID to
   * target both add and edit forms don't pick up this form.
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\Core\Config\Entity\ConfigEntityInterface $bundle */
    $bundle = $this->getEntity();
    $bundle_of_entity_type = $this->entityTypeManager->getDefinition($bundle->getEntityType()->getBundleOf());
    /* @var \Drupal\workflows\WorkflowInterface[] $workflows */
    $workflows = $this->entityTypeManager->getStorage('workflow')->loadMultiple();

    $options = array_map(function (WorkflowInterface $workflow) {
      return $workflow->label();
    }, array_filter($workflows, function (WorkflowInterface $workflow) {
      return $workflow->status() && $workflow->getTypePlugin() instanceof ContentModeration;
    }));

    $selected_workflow = array_reduce($workflows, function ($carry, WorkflowInterface $workflow) use ($bundle_of_entity_type, $bundle) {
      $plugin = $workflow->getTypePlugin();
      if ($plugin instanceof ContentModeration && $plugin->appliesToEntityTypeAndBundle($bundle_of_entity_type->id(), $bundle->id())) {
        return $workflow->id();
      }
      return $carry;
    });
    $form['workflow'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the workflow to apply'),
      '#default_value' => $selected_workflow,
      '#options' => $options,
      '#required' => FALSE,
      '#empty_value' => '',
    ];

    $form['original_workflow'] = [
      '#type' => 'value',
      '#value' => $selected_workflow,
    ];

    $form['bundle'] = [
      '#type' => 'value',
      '#value' => $bundle->id(),
    ];

    $form['entity_type'] = [
      '#type' => 'value',
      '#value' => $bundle_of_entity_type->id(),
    ];

    // Add a special message when moderation is being disabled.
    if ($selected_workflow) {
      $form['enable_workflow_note'] = [
        '#type' => 'item',
        '#description' => $this->t('After disabling moderation, any existing forward drafts will be accessible via the "Revisions" tab.'),
        '#access' => !empty($selected_workflow)
      ];
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // If moderation is enabled, revisions MUST be enabled as well. Otherwise we
    // can't have forward revisions.
    drupal_set_message($this->t('Your settings have been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_type_id = $form_state->getValue('entity_type');
    $bundle_id = $form_state->getValue('bundle');
    $new_workflow_id = $form_state->getValue('workflow');
    $original_workflow_id = $form_state->getValue('original_workflow');
    if ($new_workflow_id === $original_workflow_id) {
      // Nothing to do.
      return;
    }
    if ($original_workflow_id) {
      /* @var \Drupal\workflows\WorkflowInterface $workflow */
      $workflow = $this->entityTypeManager->getStorage('workflow')->load($original_workflow_id);
      $workflow->getTypePlugin()->removeEntityTypeAndBundle($entity_type_id, $bundle_id);
      $workflow->save();
    }
    if ($new_workflow_id) {
      /* @var \Drupal\workflows\WorkflowInterface $workflow */
      $workflow = $this->entityTypeManager->getStorage('workflow')->load($new_workflow_id);
      $workflow->getTypePlugin()->addEntityTypeAndBundle($entity_type_id, $bundle_id);
      $workflow->save();
    }
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
