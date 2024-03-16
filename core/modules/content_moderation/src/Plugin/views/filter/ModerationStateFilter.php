<?php

namespace Drupal\content_moderation\Plugin\views\filter;

use Drupal\content_moderation\Plugin\views\ModerationStateJoinViewsHandlerTrait;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\DependentWithRemovalPluginInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter for the moderation state of an entity.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("moderation_state_filter")]
class ModerationStateFilter extends InOperator implements DependentWithRemovalPluginInterface {

  use ModerationStateJoinViewsHandlerTrait;

  /**
   * {@inheritdoc}
   */
  protected $valueFormType = 'select';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The storage handler of the workflow entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $workflowStorage;

  /**
   * Creates an instance of ModerationStateFilter.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info, EntityStorageInterface $workflow_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
    $this->workflowStorage = $workflow_storage;
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
      $container->get('entity_type.manager')->getStorage('workflow')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), $this->entityTypeManager->getDefinition('workflow')->getListCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), $this->entityTypeManager->getDefinition('workflow')->getListCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }
    $this->valueOptions = [];

    // Find all workflows which are moderating entity types of the same type the
    // view is displaying.
    foreach ($this->workflowStorage->loadByProperties(['type' => 'content_moderation']) as $workflow) {
      /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface $workflow_type */
      $workflow_type = $workflow->getTypePlugin();
      if (in_array($this->getEntityType(), $workflow_type->getEntityTypes(), TRUE)) {
        foreach ($workflow_type->getStates() as $state_id => $state) {
          $this->valueOptions[$workflow->label()][implode('-', [$workflow->id(), $state_id])] = $state->label();
        }
      }
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple() {
    if (empty($this->value)) {
      return;
    }

    $this->ensureMyTable();

    $entity_type = $this->entityTypeManager->getDefinition($this->getEntityType());
    $bundle_condition = NULL;
    if ($entity_type->hasKey('bundle')) {
      // Get a list of bundles that are being moderated by the workflows
      // configured in this filter.
      $workflow_ids = $this->getWorkflowIds();
      $moderated_bundles = [];
      foreach ($this->bundleInfo->getBundleInfo($this->getEntityType()) as $bundle_id => $bundle) {
        if (isset($bundle['workflow']) && in_array($bundle['workflow'], $workflow_ids, TRUE)) {
          $moderated_bundles[] = $bundle_id;
        }
      }

      // If we have a list of moderated bundles, restrict the query to show only
      // entities in those bundles.
      if ($moderated_bundles) {
        $entity_base_table_alias = $this->relationship ?: $this->table;

        // The bundle field of an entity type is not revisionable so we need to
        // join the base table.
        $entity_base_table = $entity_type->getBaseTable();
        $entity_revision_base_table = $entity_type->isTranslatable() ? $entity_type->getRevisionDataTable() : $entity_type->getRevisionTable();
        if ($this->table === $entity_revision_base_table) {
          $entity_revision_base_table_alias = $this->relationship ?: $this->table;
          $configuration = [
            'table' => $entity_base_table,
            'field' => $entity_type->getKey('id'),
            'left_table' => $entity_revision_base_table_alias,
            'left_field' => $entity_type->getKey('id'),
            'type' => 'INNER',
          ];

          $join = Views::pluginManager('join')->createInstance('standard', $configuration);
          $entity_base_table_alias = $this->query->addRelationship($entity_base_table, $join, $entity_revision_base_table_alias);
        }

        $bundle_condition = $this->view->query->getConnection()->condition('AND');
        $bundle_condition->condition("$entity_base_table_alias.{$entity_type->getKey('bundle')}", $moderated_bundles, 'IN');
      }
      // Otherwise, force the query to return an empty result.
      else {
        $this->query->addWhereExpression($this->options['group'], '1 = 0');
        return;
      }
    }

    if ($this->operator === 'in') {
      $operator = "=";
    }
    else {
      $operator = "<>";
    }

    // The values are strings composed from the workflow ID and the state ID, so
    // we need to create a complex WHERE condition.
    $field = $this->view->query->getConnection()->condition('OR');
    foreach ((array) $this->value as $value) {
      [$workflow_id, $state_id] = explode('-', $value, 2);

      $and = $this->view->query->getConnection()->condition('AND');
      $and
        ->condition("$this->tableAlias.workflow", $workflow_id, '=')
        ->condition("$this->tableAlias.$this->realField", $state_id, $operator);

      $field->condition($and);
    }

    if ($bundle_condition) {
      // The query must match the bundle AND the workflow/state conditions.
      $bundle_condition->condition($field);
      $this->query->addWhere($this->options['group'], $bundle_condition);
    }
    else {
      $this->query->addWhere($this->options['group'], $field);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    if ($workflow_ids = $this->getWorkflowIds()) {
      /** @var \Drupal\workflows\WorkflowInterface $workflow */
      foreach ($this->workflowStorage->loadMultiple($workflow_ids) as $workflow) {
        $dependencies[$workflow->getConfigDependencyKey()][] = $workflow->getConfigDependencyName();
      }
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    // See if this handler is responsible for any of the dependencies being
    // removed. If this is the case, indicate that this handler needs to be
    // removed from the View.
    $remove = FALSE;
    // Get all the current dependencies for this handler.
    $current_dependencies = $this->calculateDependencies();
    foreach ($current_dependencies as $group => $dependency_list) {
      // Check if any of the handler dependencies match the dependencies being
      // removed.
      foreach ($dependency_list as $config_key) {
        if (isset($dependencies[$group]) && array_key_exists($config_key, $dependencies[$group])) {
          // This handlers dependency matches a dependency being removed,
          // indicate that this handler needs to be removed.
          $remove = TRUE;
          break 2;
        }
      }
    }
    return $remove;
  }

  /**
   * Gets the list of Workflow IDs configured for this filter.
   *
   * @return array
   *   And array of workflow IDs.
   */
  protected function getWorkflowIds() {
    $workflow_ids = [];
    foreach ((array) $this->value as $value) {
      [$workflow_id] = explode('-', $value, 2);
      $workflow_ids[] = $workflow_id;
    }

    return array_unique($workflow_ids);
  }

}
