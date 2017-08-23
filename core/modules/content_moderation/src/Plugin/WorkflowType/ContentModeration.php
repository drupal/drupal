<?php

namespace Drupal\content_moderation\Plugin\WorkflowType;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
 *   forms = {
 *     "configure" = "\Drupal\content_moderation\Form\ContentModerationConfigureForm",
 *     "state" = "\Drupal\content_moderation\Form\ContentModerationStateForm"
 *   },
 * )
 */
class ContentModeration extends WorkflowTypeBase implements ContentModerationInterface, ContainerFactoryPluginInterface {

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
  public function getState($state_id) {
    $state = parent::getState($state_id);
    if (isset($this->configuration['states'][$state->id()]['published']) && isset($this->configuration['states'][$state->id()]['default_revision'])) {
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
  public function workflowHasData(WorkflowInterface $workflow) {
    return (bool) $this->entityTypeManager
      ->getStorage('content_moderation_state')
      ->getQuery()
      ->condition('workflow', $workflow->id())
      ->count()
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function workflowStateHasData(WorkflowInterface $workflow, StateInterface $state) {
    return (bool) $this->entityTypeManager
      ->getStorage('content_moderation_state')
      ->getQuery()
      ->condition('workflow', $workflow->id())
      ->condition('moderation_state', $state->id())
      ->count()
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypes() {
    return array_keys($this->configuration['entity_types']);
  }

  /**
   * {@inheritdoc}
   */
  public function getBundlesForEntityType($entity_type_id) {
    return isset($this->configuration['entity_types'][$entity_type_id]) ? $this->configuration['entity_types'][$entity_type_id] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function appliesToEntityTypeAndBundle($entity_type_id, $bundle_id) {
    return in_array($bundle_id, $this->getBundlesForEntityType($entity_type_id), TRUE);
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
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
    return [
      'states' => [
        'draft' => [
          'label' => 'Draft',
          'published' => FALSE,
          'default_revision' => FALSE,
          'weight' => 0,
        ],
        'published' => [
          'label' => 'Published',
          'published' => TRUE,
          'default_revision' => TRUE,
          'weight' => 1,
        ],
      ],
      'transitions' => [
        'create_new_draft' => [
          'label' => 'Create New Draft',
          'to' => 'draft',
          'weight' => 0,
          'from' => [
            'draft',
            'published',
          ],
        ],
        'publish' => [
          'label' => 'Publish',
          'to' => 'published',
          'weight' => 1,
          'from' => [
            'draft',
            'published',
          ],
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
  public function getInitialState($entity = NULL) {
    // Workflows are not tied to entities, but Content Moderation adds the
    // relationship between Workflows and entities. Content Moderation needs the
    // entity object to be able to determine the initial state based on
    // publishing status.
    if (!($entity instanceof ContentEntityInterface)) {
      throw new \InvalidArgumentException('A content entity object must be supplied.');
    }
    if ($entity instanceof EntityPublishedInterface) {
      return $this->getState($entity->isPublished() && !$entity->isNew() ? 'published' : 'draft');
    }
    // Workflows determines the initial state for non-publishable entities.
    return parent::getInitialState();
  }

}
