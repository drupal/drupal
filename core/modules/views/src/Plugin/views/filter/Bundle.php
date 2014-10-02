<?php

/**
 * @file
 * Definition of \Drupal\views\Plugin\views\filter\Bundle.
 */

namespace Drupal\views\Plugin\views\filter;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

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

}
