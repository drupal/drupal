<?php

/**
 * @file
 * Contains \Drupal\aggregator\Controller\AggregatorController.
 */

namespace Drupal\aggregator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\aggregator\CategoryStorageControllerInterface;
use Drupal\aggregator\FeedInterface;
use Drupal\aggregator\ItemInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns responses for aggregator module routes.
 */
class AggregatorController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection;
   */
  protected $database;

  /**
   * The category storage controller.
   *
   * @var \Drupal\aggregator\CategoryStorageControllerInterface
   */
  protected $categoryStorage;

  /**
   * Constructs a \Drupal\aggregator\Controller\AggregatorController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\aggregator\CategoryStorageControllerInterface $category_storage
   *   The category storage service.
   */
  public function __construct(Connection $database, CategoryStorageControllerInterface $category_storage) {
    $this->database = $database;
    $this->categoryStorage = $category_storage;
  }

  /**
   * {inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('aggregator.category.storage')
    );
  }

  /**
   * Presents the aggregator feed creation form.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function feedAdd() {
    $entity_manager = $this->entityManager();
    $feed = $entity_manager->getStorageController('aggregator_feed')
      ->create(array(
        'refresh' => 3600,
        'block' => 5,
      ));
    return $entity_manager->getForm($feed);
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
    $feed_source = $entity_manager->getRenderController('aggregator_feed')
      ->view($aggregator_feed, 'default');
    // Load aggregator feed item for the particular feed id.
    $items = $entity_manager->getStorageController('aggregator_item')->loadByFeed($aggregator_feed->id());
    // Print the feed items.
    $build = $this->buildPageList($items, $feed_source);
    $build['#title'] = $aggregator_feed->label();
    return $build;
  }

  /**
   * Displays feed items aggregated in a category.
   *
   * @param int $cid
   *   The category id for which to list all of the aggregated items.
   *
   * @return array
   *   The render array with list of items for the feed.
   */
  public function viewCategory($cid) {
    $category = $this->categoryStorage->load($cid);
    $items = $this->entityManager()->getStorageController('aggregator_item')->loadByCategory($cid);
    $build = $this->buildPageList($items);
    $build['#title'] = $category->title;
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
      $build['items'] = $this->entityManager()->getRenderController('aggregator_item')
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
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirection to the admin overview page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the query token is missing or invalid.
   */
  public function feedRefresh(FeedInterface $aggregator_feed, Request $request) {
    // @todo CSRF tokens are validated in page callbacks rather than access
    //   callbacks, because access callbacks are also invoked during menu link
    //   generation. Add token support to routing: http://drupal.org/node/755584.
    $token = $request->query->get('token');
    if (!isset($token) || !drupal_valid_token($token, 'aggregator/update/' . $aggregator_feed->id())) {
      throw new AccessDeniedHttpException();
    }

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
    $result = $this->database->query('SELECT f.fid, f.title, f.url, f.refresh, f.checked, f.link, f.description, f.hash, f.etag, f.modified, f.image, f.block, COUNT(i.iid) AS items FROM {aggregator_feed} f LEFT JOIN {aggregator_item} i ON f.fid = i.fid GROUP BY f.fid, f.title, f.url, f.refresh, f.checked, f.link, f.description, f.hash, f.etag, f.modified, f.image, f.block ORDER BY f.title');

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
        'href' => "admin/config/services/aggregator/edit/feed/$feed->fid",
      );
      $links['delete'] = array(
        'title' => $this->t('Delete'),
        'href' => "admin/config/services/aggregator/delete/feed/$feed->fid",
      );
      $links['remove'] = array(
        'title' => $this->t('Remove items'),
        'href' => "admin/config/services/aggregator/remove/$feed->fid",
      );
      $links['update'] = array(
        'title' => $this->t('Update items'),
        'href' => "admin/config/services/aggregator/update/$feed->fid",
        'query' => array('token' => drupal_get_token("aggregator/update/$feed->fid")),
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
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No feeds available. <a href="@link">Add feed</a>.', array('@link' => $this->urlGenerator()->generateFromPath('admin/config/services/aggregator/add/feed'))),
    );

    $result = $this->database->query('SELECT c.cid, c.title, COUNT(ci.iid) as items FROM {aggregator_category} c LEFT JOIN {aggregator_category_item} ci ON c.cid = ci.cid GROUP BY c.cid, c.title ORDER BY title');

    $header = array($this->t('Title'), $this->t('Items'), $this->t('Operations'));
    $rows = array();
    foreach ($result as $category) {
      $row = array();
      $row[] = l($category->title, "aggregator/categories/$category->cid");
      $row[] = format_plural($category->items, '1 item', '@count items');
      $links = array();
      $links['edit'] = array(
        'title' => $this->t('Edit'),
        'href' => "admin/config/services/aggregator/edit/category/$category->cid",
      );
      $links['delete'] = array(
        'title' => $this->t('Delete'),
        'href' => "admin/config/services/aggregator/delete/category/$category->cid",
      );
      $row[] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $links,
        ),
      );
      $rows[] = $row;
    }
    $build['categories'] = array(
      '#prefix' => '<h3>' . $this->t('Category overview') . '</h3>',
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No categories available. <a href="@link">Add category</a>.', array('@link' => $this->urlGenerator()->generateFromPath('admin/config/services/aggregator/add/category'))),
    );

    return $build;
  }

  /**
   * Displays all the categories used by the Aggregator module.
   *
   * @return array
   *   A render array.
   */
  public function categories() {
    $entity_manager = $this->entityManager();
    $result = $this->database->query('SELECT c.cid, c.title, c.description FROM {aggregator_category} c LEFT JOIN {aggregator_category_item} ci ON c.cid = ci.cid LEFT JOIN {aggregator_item} i ON ci.iid = i.iid GROUP BY c.cid, c.title, c.description');

    $build = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('aggregator-wrapper')),
      '#sorted' => TRUE,
    );
    $aggregator_summary_items = $this->config('aggregator.settings')->get('source.list_max');
    foreach ($result as $category) {
      $summary_items = array();
      if ($aggregator_summary_items) {
        $items = $entity_manager->getStorageController('aggregator_item')->loadByCategory($category->cid);
        if ($items) {
          $summary_items = $entity_manager->getRenderController('aggregator_item')->viewMultiple($items, 'summary');
        }
      }
      $category->url = $this->urlGenerator()->generateFromPath('aggregator/categories/' . $category->cid);
      $build[$category->cid] = array(
        '#theme' => 'aggregator_summary_items',
        '#summary_items' => $summary_items,
        '#source' => $category,
      );
    }
    return $build;
  }

  /**
   * Displays the most recent items gathered from any feed.
   *
   * @return string
   *   The rendered list of items for the feed.
   */
  public function pageLast() {
    drupal_add_feed('aggregator/rss', $this->config('system.site')->get('name') . ' ' . $this->t('aggregator'));

    $items = $this->entityManager()->getStorageController('aggregator_item')->loadAll();
    return $this->buildPageList($items);
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
          $summary_items = $entity_manager->getRenderController('aggregator_item')
            ->viewMultiple($items, 'summary');
        }
      }
      $feed->url = $this->urlGenerator()->generateFromRoute('aggregator.feed_view', array('aggregator_feed' => $feed->id()));
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
   * @todo Remove aggregator_opml().
   */
  public function opmlPage($cid = NULL) {
    module_load_include('pages.inc', 'aggregator');
    return aggregator_page_opml($cid);
  }

}
