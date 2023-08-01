<?php

namespace Drupal\pager_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Pager\PagerParametersInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routine for testing the pager.
 */
class PagerTestController extends ControllerBase implements TrustedCallbackInterface {

  /**
   * The pager request service.
   *
   * @var \Drupal\Core\Pager\PagerParametersInterface
   */
  protected $pagerParams;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('pager.parameters'));
  }

  /**
   * Construct a new PagerTestController object.
   *
   * @param \Drupal\Core\Pager\PagerParametersInterface $pager_params
   *   The pager parameters.
   */
  public function __construct(PagerParametersInterface $pager_params) {
    $this->pagerParams = $pager_params;
  }

  /**
   * Builds a render array for a multi-page test table.
   *
   * @param int $element
   *   The pager element to be used for paging.
   * @param int $limit
   *   The limit of rows per page for the specified element.
   *
   * @return array
   *   A render array.
   */
  protected function buildTestTable($element, $limit) {
    $header = [
      ['data' => 'wid'],
      ['data' => 'type'],
      ['data' => 'timestamp'],
    ];
    $query = Database::getConnection()->select('watchdog', 'd')->extend(PagerSelectExtender::class)->element($element);
    $result = $query
      ->fields('d', ['wid', 'type', 'timestamp'])
      ->limit($limit)
      ->orderBy('d.wid')
      ->execute();
    $rows = [];
    foreach ($result as $row) {
      $rows[] = ['data' => (array) $row];
    }
    return [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t("There are no watchdog records found in the db"),
    ];
  }

  /**
   * Returns a pager with 'parameters' variable.
   *
   * The 'pager_calls' parameter counts the calls to the pager, subsequent
   * to the initial call.
   */
  public function queryParameters() {

    // Example query.
    $build['pager_table_0'] = $this->buildTestTable(0, 5);

    // Counter of calls to the current pager.
    $query_params = $this->pagerParams->getQueryParameters();
    $pager_calls = isset($query_params['pager_calls']) ? ($query_params['pager_calls'] ? $query_params['pager_calls'] : 0) : 0;
    $build['l_pager_pager_0'] = ['#markup' => $this->t('Pager calls: @pager_calls', ['@pager_calls' => $pager_calls])];

    // Pager.
    $build['pager_pager_0'] = [
      '#type' => 'pager',
      '#element' => 0,
      '#parameters' => [
        'pager_calls' => ++$pager_calls,
      ],
      '#pre_render' => [
        'Drupal\pager_test\Controller\PagerTestController::showPagerCacheContext',
      ],
    ];

    return $build;
  }

  /**
   * Returns a page with multiple pagers.
   */
  public function multiplePagers() {

    // Build three tables with same query and different pagers.
    $build['pager_table_0'] = $this->buildTestTable(0, 20);
    $build['pager_pager_0'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['test-pager-0']],
      'pager' => [
        '#type' => 'pager',
        '#element' => 0,
      ],
    ];

    $build['pager_table_1'] = $this->buildTestTable(1, 20);
    $build['pager_pager_1'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['test-pager-1']],
      'pager' => [
        '#type' => 'pager',
        '#element' => 1,
      ],
    ];

    $build['pager_table_4'] = $this->buildTestTable(4, 20);
    $build['pager_pager_4'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['test-pager-4']],
      'pager' => [
        '#type' => 'pager',
        '#element' => 4,
      ],
    ];

    return $build;
  }

  /**
   * #pre_render callback for #type => pager that shows the pager cache context.
   */
  public static function showPagerCacheContext(array $pager) {
    \Drupal::messenger()->addStatus(\Drupal::service('cache_contexts_manager')->convertTokensToKeys(['url.query_args.pagers:' . $pager['#element']])->getKeys()[0]);
    return $pager;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['showPagerCacheContext'];
  }

}
