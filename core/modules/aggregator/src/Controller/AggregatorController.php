<?php

/**
 * @file
 * Contains \Drupal\aggregator\Controller\AggregatorController.
 */

namespace Drupal\aggregator\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\aggregator\FeedInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for aggregator module routes.
 */
class AggregatorController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a \Drupal\aggregator\Controller\AggregatorController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *    The date formatter service.
   */
  public function __construct(DateFormatter $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter')
    );
  }

  /**
   * Presents the aggregator feed creation form.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function feedAdd() {
    $feed = $this->entityManager()->getStorage('aggregator_feed')
      ->create(array(
        'refresh' => 3600,
      ));
    return $this->entityFormBuilder()->getForm($feed);
  }

  /**
   * Builds a listing of aggregator feed items.
   *
   * @param \Drupal\aggregator\ItemInterface[] $items
   *   The items to be listed.
   * @param array|string $feed_source
   *   The feed source URL.
   *
   * @return array
   *   The rendered list of items for the feed.
   */
  protected function buildPageList(array $items, $feed_source = '') {
    // Assemble output.
    $build = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('aggregator-wrapper')),
    );
    $build['feed_source'] = is_array($feed_source) ? $feed_source : array('#markup' => $feed_source);
    if ($items) {
      $build['items'] = $this->entityManager()->getViewBuilder('aggregator_item')
        ->viewMultiple($items, 'default');
      $build['pager'] = array('#theme' => 'pager');
    }
    return $build;
  }

  /**
   * Refreshes a feed, then redirects to the overview page.
   *
   * @param \Drupal\aggregator\FeedInterface $aggregator_feed
   *   An object describing the feed to be refreshed.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirection to the admin overview page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the query token is missing or invalid.
   */
  public function feedRefresh(FeedInterface $aggregator_feed) {
    $message = $aggregator_feed->refreshItems()
      ? $this->t('There is new syndicated content from %site.', array('%site' => $aggregator_feed->label()))
      : $this->t('There is no new syndicated content from %site.', array('%site' => $aggregator_feed->label()));
    drupal_set_message($message);
    return $this->redirect('aggregator.admin_overview');
  }

  /**
   * Displays the aggregator administration page.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function adminOverview() {
    $entity_manager = $this->entityManager();
    $feeds = $entity_manager->getStorage('aggregator_feed')
      ->loadMultiple();

    $header = array($this->t('Title'), $this->t('Items'), $this->t('Last update'), $this->t('Next update'), $this->t('Operations'));
    $rows = array();
    /** @var \Drupal\aggregator\FeedInterface[] $feeds */
    foreach ($feeds as $feed) {
      $row = array();
      $row[] = $feed->link();
      $row[] = $this->dateFormatter->formatInterval($entity_manager->getStorage('aggregator_item')->getItemCount($feed), '1 item', '@count items');
      $last_checked = $feed->getLastCheckedTime();
      $refresh_rate = $feed->getRefreshRate();
      $row[] = ($last_checked ? $this->t('@time ago', array('@time' => $this->dateFormatter->formatInterval(REQUEST_TIME - $last_checked))) : $this->t('never'));
      $row[] = ($last_checked && $refresh_rate ? $this->t('%time left', array('%time' => $this->dateFormatter->formatInterval($last_checked + $refresh_rate - REQUEST_TIME))) : $this->t('never'));
      $links['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('entity.aggregator_feed.edit_form', ['aggregator_feed' => $feed->id()]),
      ];
      $links['delete'] = array(
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('entity.aggregator_feed.delete_form', ['aggregator_feed' => $feed->id()]),
      );
      $links['delete_items'] = array(
        'title' => $this->t('Delete items'),
        'url' => Url::fromRoute('aggregator.feed_items_delete', ['aggregator_feed' => $feed->id()]),
      );
      $links['update'] = array(
        'title' => $this->t('Update items'),
        'url' => Url::fromRoute('aggregator.feed_refresh', ['aggregator_feed' => $feed->id()])
      );
      $row[] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $links,
        ),
      );
      $rows[] = $row;
    }
    $build['feeds'] = array(
      '#prefix' => '<h3>' . $this->t('Feed overview') . '</h3>',
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No feeds available. <a href="@link">Add feed</a>.', array('@link' => $this->url('aggregator.feed_add'))),
    );

    return $build;
  }

  /**
   * Displays the most recent items gathered from any feed.
   *
   * @return string
   *   The rendered list of items for the feed.
   */
  public function pageLast() {
    $items = $this->entityManager()->getStorage('aggregator_item')->loadAll(20);
    $build = $this->buildPageList($items);
    $build['#attached']['drupal_add_feed'][] = array('aggregator/rss', $this->config('system.site')->get('name') . ' ' . $this->t('aggregator'));
    return $build;
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\aggregator\FeedInterface $aggregator_feed
   *   The aggregator feed.
   *
   * @return string
   *   The feed label.
   */
  public function feedTitle(FeedInterface $aggregator_feed) {
    return Xss::filter($aggregator_feed->label());
  }

}
