<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\Derivative\AggregatorCategoryBlock.
 */

namespace Drupal\aggregator\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Provides block plugin definitions for aggregator categories.
 *
 * @see \Drupal\aggregator\Plugin\block\block\AggregatorCategoryBlock
 */
class AggregatorCategoryBlock implements DerivativeInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinition().
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $result = db_query('SELECT cid, title FROM {aggregator_category} ORDER BY title WHERE cid = :cid', array(':cid' => $derivative_id))->fetchObject();
    $this->derivatives[$derivative_id] = $base_plugin_definition;
    $this->derivatives[$derivative_id]['delta'] = $result->cid;
    $this->derivatives[$derivative_id]['admin_label'] = t('@title category latest items', array('@title' => $result->title));
    return $this->derivatives[$derivative_id];
  }

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    // Provide a block plugin definition for each aggregator category.
    $result = db_query('SELECT cid, title FROM {aggregator_category} ORDER BY title');
    foreach ($result as $category) {
      $this->derivatives[$category->cid] = $base_plugin_definition;
      $this->derivatives[$category->cid]['delta'] = $category->cid;
      $this->derivatives[$category->cid]['admin_label'] = t('@title category latest items', array('@title' => $category->title));
    }
    return $this->derivatives;
  }

}
