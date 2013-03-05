<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\Derivative\AggregatorFeedBlock.
 */

namespace Drupal\aggregator\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Provides block plugin definitions for aggregator feeds.
 *
 * @see \Drupal\aggregator\Plugin\block\block\AggregatorFeedBlock
 */
class AggregatorFeedBlock implements DerivativeInterface {

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
    $result = db_query('SELECT fid, title, block FROM {aggregator_feed} WHERE block <> 0 AND fid = :fid', array(':fid' => $derivative_id))->fetchObject();
    $this->derivatives[$derivative_id] = $base_plugin_definition;
    $this->derivatives[$derivative_id]['delta'] = $result->fid;
    $this->derivatives[$derivative_id]['admin_label'] = t('@title feed latest items', array('@title' => $result->title));
    return $this->derivatives[$derivative_id];
  }

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    // Add a block plugin definition for each feed.
    $result = db_query('SELECT fid, title FROM {aggregator_feed} WHERE block <> 0 ORDER BY fid');
    foreach ($result as $feed) {
      $this->derivatives[$feed->fid] = $base_plugin_definition;
      $this->derivatives[$feed->fid]['delta'] = $feed->fid;
      $this->derivatives[$feed->fid]['admin_label'] = t('@title feed latest items', array('@title' => $feed->title));
    }
    return $this->derivatives;
  }

}
