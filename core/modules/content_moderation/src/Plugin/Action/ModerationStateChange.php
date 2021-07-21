<?php

namespace Drupal\content_moderation\Plugin\Action;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflows\Entity\Workflow;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Changes moderation_state of an entity.
 *
 * @Action(
 *   id = "moderation_state_change",
 *   deriver = "\Drupal\content_moderation\Plugin\Derivative\ModerationStateChangeDeriver"
 * )
 */
class ModerationStateChange extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  use DependencyTrait;

  /**
   * The moderation info service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Moderation state transition validation service.
   *
   * @var \Drupal\content_moderation\StateTransitionValidationInterface
   */
  protected $validator;

  /**
   * Moderation state change constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation info service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user service.
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $validator
   *   Moderation state transition validation service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModerationInformationInterface $moderation_info, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, StateTransitionValidationInterface $validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moderationInfo = $moderation_info;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->validator = $validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('content_moderation.moderation_information'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('content_moderation.state_transition_validation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'workflow' => NULL,
      'state' => NULL,
      'revision_log_message' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $workflow_options = [];
    $workflows = Workflow::loadMultipleByType('content_moderation');

    foreach ($workflows as $workflow) {
      if (in_array($this->pluginDefinition['type'], $workflow->getTypePlugin()->getEntityTypes(), TRUE)) {
        $workflow_options[$workflow->id()] = $workflow->label();
      }
    }

    if (!$default_workflow = $form_state->getValue('workflow')) {
      if (!empty($this->configuration['workflow'])) {
        $default_workflow = $this->configuration['workflow'];
      }
      else {
        $default_workflow = key($workflow_options);
      }
    }

    $form['workflow'] = [
      '#type' => 'select',
      '#title' => $this->t('Workflow'),
      '#options' => $workflow_options,
      '#default_value' => $default_workflow,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [static::class, 'configurationFormAjax'],
        'wrapper' => 'edit-state-wrapper',
      ],
    ];

    $form['workflow_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Change workflow'),
      '#limit_validation_errors' => [['workflow']],
      '#attributes' => [
        'class' => ['js-hide'],
      ],
      '#submit' => [[static::class, 'configurationFormAjaxSubmit']],
    ];

    if ($default_workflow) {
      $state_options = [];
      foreach ($workflows[$default_workflow]->getTypePlugin()->getStates() as $state) {
        $state_options[$state->id()] = $this->t('Change moderation state to @state', ['@state' => $state->label()]);
      }

      $form['state-wrapper'] = [
        '#type' => 'container',
        '#id' => 'edit-state-wrapper',
      ];

      $form['state-wrapper']['state'] = [
        '#type' => 'select',
        '#title' => $this->t('State'),
        '#options' => $state_options,
        '#default_value' => $this->configuration['state'],
        '#required' => TRUE,
      ];

      $form['state-wrapper']['revision_log_message'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Revision log message'),
        '#default_value' => $this->configuration['revision_log_message'],
        '#rows' => 4,
        '#required' => FALSE,
      ];
    }

    return $form;

  }

  /**
   * Ajax callback for the configuration form.
   *
   * @see static::buildConfigurationForm()
   */
  public static function configurationFormAjax($form, FormStateInterface $form_state) {
    return $form['state-wrapper'];
  }

  /**
   * Submit configuration for the non-JS case.
   *
   * @see static::buildConfigurationForm()
   */
  public static function configurationFormAjaxSubmit($form, FormStateInterface $form_state) {
    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['workflow'] = $form_state->getValue('workflow');
    $this->configuration['state'] = $form_state->getValue('state');
    $this->configuration['revision_log_message'] = $form_state->getValue('revision_log_message');
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    if (!empty($this->configuration['workflow'])) {
      $this->addDependency('config', 'workflows.workflow.' . $this->configuration['workflow']);
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ContentEntityInterface $entity = NULL) {
    /** @var \Drupal\node\Entity\Node $revision */
    $revision = $this->loadLatestRevision($entity);
    // Create a new revision if the states don't match.
    if ($revision->moderation_state->value != $this->configuration['state']) {
      $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
      $revision = $storage->createRevision($revision);
    }
    $revision->moderation_state->value = $this->configuration['state'];
    if ($revision instanceof EntityChangedInterface) {
      $revision->setChangedTime(time());
    }
    $revision->setRevisionLogMessage($this->configuration['revision_log_message']);
    $revision->setRevisionCreationTime($revision->getChangedTime());
    $revision->setRevisionUserId($this->currentUser->id());
    $violations = $revision->validate();
    if ($violations->count() > 0) {
      $this->messenger()->addError($violations[0]->getMessage());
      return;
    }
    $revision->save();
  }

  /**
   * Loads the latest revision of an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The latest revision of content entity.
   */
  protected function loadLatestRevision(ContentEntityInterface $entity) {
    $entity_storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $original_entity =
      ($revision_id = $entity_storage->getLatestTranslationAffectedRevisionId($entity->id(), $entity->language()->getId())) ?
        $entity_storage->loadRevision($revision_id)->getTranslation($entity->language()->getId()) :
        NULL;
    return $original_entity;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (!$object || !$object instanceof ContentEntityInterface) {
      $result = AccessResult::forbidden('Not a valid entity.');
      return $return_as_object ? $result : $result->isAllowed();
    }
    if ($workflow = $this->moderationInfo->getWorkflowForEntity($object)) {
      if ($workflow->id() !== $this->configuration['workflow']) {
        $result = AccessResult::forbidden('Not a valid workflow for this entity.');
        $result->addCacheableDependency($workflow);
        return $return_as_object ? $result : $result->isAllowed();
      }
    }
    else {
      $result = AccessResult::forbidden('No workflow found for the entity.');
      return $return_as_object ? $result : $result->isAllowed();
    }
    $object = $this->loadLatestRevision($object);
    // Let content moderation do its job. See content_moderation_entity_access()
    // for more details.
    $access = $object->access('update', $account, TRUE);

    $to_state_id = $this->configuration['state'];
    $from_state = $workflow->getTypePlugin()->getState($object->moderation_state->value);
    // Make sure we can make the transition.
    if ($from_state->canTransitionTo($to_state_id)) {
      $to_state = $workflow->getTypePlugin()->getState($to_state_id);
      // Let the validator do the access check.
      // This not only checks if the transition is valid but
      // also checks if the user have permission to do
      // the transition. While it does repeat some of the access checks
      // this validator can be overridden by groups.
      $valid = $this->validator->isTransitionValid($workflow, $from_state, $to_state, $account, $object);
      if ($valid) {
        // The user has permission to
        // perform the transition. Set to allow if they also have update
        // access.
        $result = AccessResult::allowed()->andIf($access);
      }
      else {
        // The user does not have permission to perform the
        // transition. In keeping consistent with the previous
        // code return neutral.
        $result = AccessResult::neutral()->andIf($access);
      }
    }
    else {
      $result = AccessResult::forbidden('No valid transition found.');
    }
    $result->addCacheableDependency($workflow);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
