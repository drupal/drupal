<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\filter\Bundle.
 */

namespace Drupal\views\Plugin\views\filter;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter class which allows filtering by entity bundles.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("bundle")
 */
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
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a Bundle object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * Overrides \Drupal\views\Plugin\views\filter\InOperator::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->entityTypeId = $this->getEntityType();
    $this->entityType = \Drupal::entityManager()->getDefinition($this->entityTypeId);
    $this->real_field = $this->entityType->getKey('bundle');
  }

  /**
   * Overrides \Drupal\views\Plugin\views\filter\InOperator::getValueOptions().
   */
  public function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $types = entity_get_bundles($this->entityTypeId);
      $this->valueTitle = $this->t('@entity types', array('@entity' => $this->entityType->getLabel()));

      $options = array();
      foreach ($types as $type => $info) {
        $options[$type] = $info['label'];
      }

      asort($options);
      $this->valueOptions = $options;
    }

    return $this->valueOptions;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\filter\InOperator::query().
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
    $bundle_entity_storage = $this->entityManager->getStorage($bundle_entity_type);

    foreach (array_keys($this->value) as $bundle) {
      if ($bundle_entity = $bundle_entity_storage->load($bundle)) {
        $dependencies[$bundle_entity->getConfigDependencyKey()][] = $bundle_entity->getConfigDependencyName();
      }
    }

    return $dependencies;
  }

}
