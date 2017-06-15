<?php

namespace Drupal\content_moderation\Plugin\WorkflowType;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\content_moderation\ContentModerationState;
use Drupal\workflows\Plugin\WorkflowTypeBase;
use Drupal\workflows\StateInterface;
use Drupal\workflows\WorkflowInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Attaches workflows to content entity types and their bundles.
 *
 * @WorkflowType(
 *   id = "content_moderation",
 *   label = @Translation("Content moderation"),
 *   required_states = {
 *     "draft",
 *     "published",
 *   },
 * )
 */
class ContentModeration extends WorkflowTypeBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an instance of the ContentModeration WorkflowType plugin.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initializeWorkflow(WorkflowInterface $workflow) {
    $workflow
      ->addState('draft', $this->t('Draft'))
      ->setStateWeight('draft', -5)
      ->addState('published', $this->t('Published'))
      ->setStateWeight('published', 0)
      ->addTransition('create_new_draft', $this->t('Create New Draft'), ['draft', 'published'], 'draft')
      ->addTransition('publish', $this->t('Publish'), ['draft', 'published'], 'published');
    return $workflow;
  }

  /**
   * {@inheritdoc}
   */
  public function checkWorkflowAccess(WorkflowInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'view') {
      return AccessResult::allowedIfHasPermission($account, 'view content moderation');
    }
    return parent::checkWorkflowAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function decorateState(StateInterface $state) {
    if (isset($this->configuration['states'][$state->id()])) {
      $state = new ContentModerationState($state, $this->configuration['states'][$state->id()]['published'], $this->configuration['states'][$state->id()]['default_revision']);
    }
    else {
      $state = new ContentModerationState($state);
    }
    return $state;
  }

  /**
   * {@inheritdoc}
   */
  public function buildStateConfigurationForm(FormStateInterface $form_state, WorkflowInterface $workflow, StateInterface $state = NULL) {
    /** @var \Drupal\content_moderation\ContentModerationState $state */
    $is_required_state = isset($state) ? in_array($state->id(), $this->getRequiredStates(), TRUE) : FALSE;

    $form = [];
    $form['published'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Published'),
      '#description' => $this->t('When content reaches this state it should be published.'),
      '#default_value' => isset($state) ? $state->isPublishedState() : FALSE,
      '#disabled' => $is_required_state,
    ];

    $form['default_revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default revision'),
      '#description' => $this->t('When content reaches this state it should be made the default revision; this is implied for published states.'),
      '#default_value' => isset($state) ? $state->isDefaultRevisionState() : FALSE,
      '#disabled' => $is_required_state,
      // @todo Add form #state to force "make default" on when "published" is
      // on for a state.
      // @see https://www.drupal.org/node/2645614
    ];
    return $form;
  }

  /**
   * Gets the entity types the workflow is applied to.
   *
   * @return string[]
   *   The entity types the workflow is applied to.
   */
  public function getEntityTypes() {
    return array_keys($this->configuration['entity_types']);
  }

  /**
   * Gets any bundles the workflow is applied to for the given entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID to get the bundles for.
   *
   * @return string[]
   *   The bundles of the entity type the workflow is applied to or an empty
   *   array if the entity type is not applied to the workflow.
   */
  public function getBundlesForEntityType($entity_type_id) {
    return isset($this->configuration['entity_types'][$entity_type_id]) ? $this->configuration['entity_types'][$entity_type_id] : [];
  }

  /**
   * Checks if the workflow applies to the supplied entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID to check.
   * @param string $bundle_id
   *   The bundle ID to check.
   *
   * @return bool
   *   TRUE if the workflow applies to the supplied entity type ID and bundle
   *   ID. FALSE if not.
   */
  public function appliesToEntityTypeAndBundle($entity_type_id, $bundle_id) {
    return in_array($bundle_id, $this->getBundlesForEntityType($entity_type_id), TRUE);
  }

  /**
   * Removes an entity type ID / bundle ID from the workflow.
   *
   * @param string $entity_type_id
   *   The entity type ID to remove.
   * @param string $bundle_id
   *   The bundle ID to remove.
   */
  public function removeEntityTypeAndBundle($entity_type_id, $bundle_id) {
    $key = array_search($bundle_id, $this->configuration['entity_types'][$entity_type_id], TRUE);
    if ($key !== FALSE) {
      unset($this->configuration['entity_types'][$entity_type_id][$key]);
      if (empty($this->configuration['entity_types'][$entity_type_id])) {
        unset($this->configuration['entity_types'][$entity_type_id]);
      }
      else {
        $this->configuration['entity_types'][$entity_type_id] = array_values($this->configuration['entity_types'][$entity_type_id]);
      }
    }
  }

  /**
   * Add an entity type ID / bundle ID to the workflow.
   *
   * @param string $entity_type_id
   *   The entity type ID to add. It is responsibility of the caller to provide
   *   a valid entity type ID.
   * @param string $bundle_id
   *   The bundle ID to add. It is responsibility of the caller to provide a
   *   valid bundle ID.
   */
  public function addEntityTypeAndBundle($entity_type_id, $bundle_id) {
    if (!$this->appliesToEntityTypeAndBundle($entity_type_id, $bundle_id)) {
      $this->configuration['entity_types'][$entity_type_id][] = $bundle_id;
      sort($this->configuration['entity_types'][$entity_type_id]);
      ksort($this->configuration['entity_types']);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    // This plugin does not store anything per transition.
    return [
      'states' => [
        'draft' => [
          'published' => FALSE,
          'default_revision' => FALSE,
        ],
        'published' => [
          'published' => TRUE,
          'default_revision' => TRUE,
        ],
      ],
      'entity_types' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    foreach ($this->getEntityTypes() as $entity_type_id) {
      $entity_definition = $this->entityTypeManager->getDefinition($entity_type_id);
      foreach ($this->getBundlesForEntityType($entity_type_id) as $bundle) {
        $dependency = $entity_definition->getBundleConfigDependency($bundle);
        $dependencies[$dependency['type']][] = $dependency['name'];
      }
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    // When bundle config entities are removed, ensure they are cleaned up from
    // the workflow.
    foreach ($dependencies['config'] as $removed_config) {
      if ($entity_type_id = $removed_config->getEntityType()->getBundleOf()) {
        $bundle_id = $removed_config->id();
        $this->removeEntityTypeAndBundle($entity_type_id, $bundle_id);
        $changed = TRUE;
      }
    }

    // When modules that provide entity types are removed, ensure they are also
    // removed from the workflow.
    if (!empty($dependencies['module'])) {
      // Gather all entity definitions provided by the dependent modules which
      // are being removed.
      $module_entity_definitions = [];
      foreach ($this->entityTypeManager->getDefinitions() as $entity_definition) {
        if (in_array($entity_definition->getProvider(), $dependencies['module'])) {
          $module_entity_definitions[] = $entity_definition;
        }
      }

      // For all entity types provided by the uninstalled modules, remove any
      // configuration for those types.
      foreach ($module_entity_definitions as $module_entity_definition) {
        foreach ($this->getBundlesForEntityType($module_entity_definition->id()) as $bundle) {
          $this->removeEntityTypeAndBundle($module_entity_definition->id(), $bundle);
          $changed = TRUE;
        }
      }
    }

    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();
    // Ensure that states and entity types are ordered consistently.
    ksort($configuration['states']);
    ksort($configuration['entity_types']);
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getInitialState(WorkflowInterface $workflow, $entity = NULL) {
    if ($entity instanceof EntityPublishedInterface) {
      return $workflow->getState($entity->isPublished() && !$entity->isNew() ? 'published' : 'draft');
    }
    return parent::getInitialState($workflow);
  }

}
