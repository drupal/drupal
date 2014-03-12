<?php

/**
 * @file
 * Contains \Drupal\aggregator\Controller\AggregatorController.
 */

namespace Drupal\aggregator\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\aggregator\FeedInterface;
use Drupal\aggregator\ItemInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns responses for aggregator module routes.
 */
class AggregatorController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection;
   */
  protected $database;

  /**
   * Constructs a \Drupal\aggregator\Controller\AggregatorController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Presents the aggregator feed creation form.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function feedAdd() {
    $feed = $this->entityManager()->getStorageController('aggregator_feed')
      ->create(array(
        'refresh' => 3600,
      ));
    return $this->entityFormBuilder()->getForm($feed);
  }

  /**
   * Displays all the items captured from the particular feed.
   *
   * @param \Drupal\aggregator\FeedInterface $aggregator_feed
   *   The feed for which to display all items.
   *
   * @return array
   *   The rendered list of items for the feed.
   */
  public function viewFeed(FeedInterface $aggregator_feed) {
    $entity_manager = $this->entityManager();
    $feed_source = $entity_manager->getViewBuilder('aggregator_feed')
      ->view($aggregator_feed, 'default');
    // Load aggregator feed item for the particular feed id.
    $items = $entity_manager->getStorageController('aggregator_item')->loadByFeed($aggregator_feed->id());
    // Print the feed items.
    $build = $this->buildPageList($items, $feed_source);
    return $build;
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
    // @todo after https://drupal.org/node/1972246 find a new place for it.
    aggregator_refresh($aggregator_feed);
    return $this->redirect('aggregator.admin_overview');
  }

  /**
   * Displays the aggregator administration page.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function adminOverview() {
    $result = $this->database->query('SELECT f.fid, f.title, f.url, f.refresh, f.checked, f.link, f.description, f.hash, f.etag, f.modified, f.image, COUNT(i.iid) AS items FROM {aggregator_feed} f LEFT JOIN {aggregator_item} i ON f.fid = i.fid GROUP BY f.fid, f.title, f.url, f.refresh, f.checked, f.link, f.description, f.hash, f.etag, f.modified, f.image ORDER BY f.title');

    $header = array($this->t('Title'), $this->t('Items'), $this->t('Last update'), $this->t('Next update'), $this->t('Operations'));
    $rows = array();
    foreach ($result as $feed) {
      $row = array();
      $row[] = l($feed->title, "aggregator/sources/$feed->fid");
      $row[] = format_plural($feed->items, '1 item', '@count items');
      $row[] = ($feed->checked ? $this->t('@time ago', array('@time' => format_interval(REQUEST_TIME - $feed->checked))) : $this->t('never'));
      $row[] = ($feed->checked && $feed->refresh ? $this->t('%time left', array('%time' => format_interval($feed->checked + $feed->refresh - REQUEST_TIME))) : $this->t('never'));
      $links = array();
      $links['edit'] = array(
        'title' => $this->t('Edit'),
        'route_name' => 'aggregator.feed_edit',
        'route_parameters' => array('aggregator_feed' => $feed->fid),
      );
      $links['delete'] = array(
        'title' => $this->t('Delete'),
        'route_name' => 'aggregator.feed_delete',
        'route_parameters' => array('aggregator_feed' => $feed->fid),
      );
      $links['remove'] = array(
        'title' => $this->t('Remove items'),
        'route_name' => 'aggregator.feed_items_delete',
        'route_parameters' => array('aggregator_feed' => $feed->fid),
      );
      $links['update'] = array(
        'title' => $this->t('Update items'),
        'route_name' => 'aggregator.feed_refresh',
        'route_parameters' => array('aggregator_feed' => $feed->fid),
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
      '#empty' => $this->t('No feeds available. <a href="@link">Add feed</a>.', array('@link' => $this->urlGenerator()->generateFromPath('admin/config/services/aggregator/add/feed'))),
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
    $items = $this->entityManager()->getStorageController('aggregator_item')->loadAll();
    $build = $this->buildPageList($items);
    $build['#attached']['drupal_add_feed'][] = array('aggregator/rss', $this->config('system.site')->get('name') . ' ' . $this->t('aggregator'));
    return $build;
  }

  /**
   * Displays all the feeds used by the Aggregator module.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function sources() {
    $entity_manager = $this->entityManager();

    $feeds = $entity_manager->getStorageController('aggregator_feed')->loadMultiple();

    $build = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('aggregator-wrapper')),
      '#sorted' => TRUE,
    );

    foreach ($feeds as $feed) {
      // Most recent items:
      $summary_items = array();
      $aggregator_summary_items = $this->config('aggregator.settings')
        ->get('source.list_max');
      if ($aggregator_summary_items) {
        $items = $entity_manager->getStorageController('aggregator_item')
          ->loadByFeed($feed->id());
        if ($items) {
          $summary_items = $entity_manager->getViewBuilder('aggregator_item')
            ->viewMultiple($items, 'summary');
        }
      }
      $feed->url = $this->url('aggregator.feed_view', array('aggregator_feed' => $feed->id()));
      $build[$feed->id()] = array(
        '#theme' => 'aggregator_summary_items',
        '#summary_items' => $summary_items,
        '#source' => $feed,
      );
    }
    $build['feed_icon'] = array(
      '#theme' => 'feed_icon',
      '#url' => 'aggregator/opml',
      '#title' => $this->t('OPML feed'),
    );
    return $build;
  }

  /**
   * Generates an OPML representation of all feeds.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response containing the OPML.
   */
  public function opmlPage() {
    $result = $this->database->query('SELECT * FROM {aggregator_feed} ORDER BY title');

    $feeds = $result->fetchAll();
    $aggregator_page_opml = array(
      '#theme' => 'aggregator_page_opml',
      '#feeds' => $feeds,
    );
    $output = drupal_render($aggregator_page_opml);

    $response = new Response();
    $response->headers->set('Content-Type', 'text/xml; charset=utf-8');
    $response->setContent($output);

    return $response;
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
