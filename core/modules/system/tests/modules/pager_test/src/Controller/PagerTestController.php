<?php

namespace Drupal\pager_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routine for testing the pager.
 */
class PagerTestController extends ControllerBase {

  /**
   * Returns a pager with 'parameters' variable.
   *
   * The 'pager_calls' parameter counts the calls to the pager, subsequent
   * to the initial call.
   */
  public function queryParameters() {

    // Example query.
    $header_0 = array(
      array('data' => 'wid'),
      array('data' => 'type'),
      array('data' => 'timestamp'),
    );
    $query_0 = db_select('watchdog', 'd')->extend('Drupal\Core\Database\Query\PagerSelectExtender')->element(0);
    $query_0->fields('d', array('wid', 'type', 'timestamp'));
    $result_0 = $query_0
      ->limit(5)
      ->orderBy('d.wid')
      ->execute();
    $rows_0 = array();
    foreach ($result_0 as $row) {
      $rows_0[] = array('data' => (array) $row);
    }
    $build['pager_table_0'] = array(
      '#theme' => 'table',
      '#header' => $header_0,
      '#rows' => $rows_0,
      '#empty' => $this->t("There are no watchdog records found in the db"),
    );

    // Counter of calls to the current pager.
    $query_params = pager_get_query_parameters();
    $pager_calls = isset($query_params['pager_calls']) ? ($query_params['pager_calls'] ? $query_params['pager_calls'] : 0) : 0;
    $build['l_pager_pager_0'] = array('#markup' => $this->t('Pager calls: @pager_calls', array('@pager_calls' => $pager_calls)));

    // Pager.
    $build['pager_pager_0'] = array(
      '#type' => 'pager',
      '#element' => 0,
      '#parameters' => array(
        'pager_calls' => ++$pager_calls,
      ),
      '#pre_render' => [
        'Drupal\pager_test\Controller\PagerTestController::showPagerCacheContext',
      ]
    );

    return $build;
  }

  /**
   * #pre_render callback for #type => pager that shows the pager cache context.
   */
  public static function showPagerCacheContext(array $pager) {
    drupal_set_message(\Drupal::service('cache_contexts_manager')->convertTokensToKeys(['url.query_args.pagers:' . $pager['#element']])->getKeys()[0]);
    return $pager;
  }

}
