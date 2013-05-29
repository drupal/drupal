<?php

/**
 * @file
 * Definition of \Drupal\views\Plugin\views\filter\Bundle.
 */

namespace Drupal\views\Plugin\views\filter;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * Filter class which allows filtering by entity bundles.
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("bundle")
 */
class Bundle extends InOperator {

  /**
   * The entity type for the filter.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The entity info for the entity type.
   *
   * @var array
   */
  protected $entityInfo;

  /**
   * Overrides \Drupal\views\Plugin\views\filter\InOperator::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->entityType = $this->getEntityType();
    $this->entityInfo = entity_get_info($this->entityType);
    $this->real_field = $this->entityInfo['entity_keys']['bundle'];
  }

  /**
   * Overrides \Drupal\views\Plugin\views\filter\InOperator::getValueOptions().
   */
  public function getValueOptions() {
    if (!isset($this->value_options)) {
      $types = entity_get_bundles($this->entityType);
      $this->value_title = t('@entity types', array('@entity' => $this->entityInfo['label']));

      $options = array();
      foreach ($types as $type => $info) {
        $options[$type] = $info['label'];
      }

      asort($options);
      $this->value_options = $options;
    }

    return $this->value_options;
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
