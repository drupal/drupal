<?php

namespace Drupal\views\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * Filter class which allows filtering by entity bundles.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("bundle")]
class Bundle extends InOperator {

  /**
   * The entity type for the filter.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity type definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The bundle key.
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public string $real_field;

  /**
   * Constructs a Bundle object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfoService
   *   The bundle info service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeBundleInfoInterface $bundleInfoService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->entityTypeId = $this->getEntityType();
    $this->entityType = \Drupal::entityTypeManager()->getDefinition($this->entityTypeId);
    $this->real_field = $this->entityType->getKey('bundle');
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $types = $this->bundleInfoService->getBundleInfo($this->entityTypeId);
      $this->valueTitle = $this->t('@entity types', ['@entity' => $this->entityType->getLabel()]);

      $options = [];
      foreach ($types as $type => $info) {
        $options[$type] = $info['label'];
      }

      asort($options);
      $this->valueOptions = $options;
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Make sure that the entity base table is in the query.
    $this->ensureMyTable();
    parent::query();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    $bundle_entity_type = $this->entityType->getBundleEntityType();
    $bundle_entity_storage = $this->entityTypeManager->getStorage($bundle_entity_type);

    foreach (array_keys($this->value) as $bundle) {
      if ($bundle_entity = $bundle_entity_storage->load($bundle)) {
        $dependencies[$bundle_entity->getConfigDependencyKey()][] = $bundle_entity->getConfigDependencyName();
      }
    }

    return $dependencies;
  }

}
