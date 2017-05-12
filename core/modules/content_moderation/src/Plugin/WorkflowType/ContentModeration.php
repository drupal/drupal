<?php

namespace Drupal\content_moderation\Plugin\WorkflowType;

use Drupal\Component\Serialization\Json;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\content_moderation\ContentModerationState;
use Drupal\Core\Url;
use Drupal\workflows\Plugin\WorkflowTypeFormBase;
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
class ContentModeration extends WorkflowTypeFormBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * Constructs a ContentModeration object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   Moderation information service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, ModerationInformationInterface $moderation_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->moderationInfo = $moderation_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('content_moderation.moderation_information')
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
    if (!isset($this->configuration['entity_types'][$entity_type_id])) {
      return;
    }
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
   * {@inheritdoc}
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
      return $workflow->getState($entity->isPublished() ? 'published' : 'draft');
    }
    return parent::getInitialState($workflow);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, WorkflowInterface $workflow = NULL) {
    $header = [
      'type' => $this->t('Items'),
      'operations' => $this->t('Operations')
    ];
    $form['entity_types_container'] = [
      '#type' => 'details',
      '#title' => $this->t('This workflow applies to:'),
      '#open' => TRUE,
    ];
    $form['entity_types_container']['entity_types'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('There are no entity types.'),
    ];

    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type) {
      if (!$this->moderationInfo->canModerateEntitiesOfEntityType($entity_type)) {
        continue;
      }

      $selected_bundles = [];
      foreach ($this->entityTypeBundleInfo->getBundleInfo($entity_type->id()) as $bundle_id => $bundle) {
        if ($this->appliesToEntityTypeAndBundle($entity_type->id(), $bundle_id)) {
          $selected_bundles[$bundle_id] = $bundle['label'];
        }
      }

      $form['entity_types_container']['entity_types'][$entity_type->id()] = [
        'type' => [
          'label' => ['#markup' => '<strong>' . $this->t('@bundle types', ['@bundle' => $entity_type->getLabel()]) . '</strong>'],
          'selected' => [
            '#prefix' => '<br/><span id="selected-' . $entity_type->id() . '">',
            '#markup' => !empty($selected_bundles) ? implode(', ', $selected_bundles) : $this->t('none'),
            '#suffix' => '</span>',
          ],
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => [
            'select' => [
              'title' => $this->t('Select'),
              'url' => Url::fromRoute('content_moderation.workflow_type_edit_form', ['workflow' => $workflow->id(), 'entity_type_id' => $entity_type->id()]),
              'attributes' => [
                'aria-label' => $this->t('Select'),
                'class' => ['use-ajax'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => Json::encode([
                  'width' => 700,
                ]),
              ],
            ],
          ],
        ],
      ];
    }

    return $form;
  }

}
