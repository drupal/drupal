<?php

/**
 * @file
 * Contains \Drupal\aggregator\Routing\AggregatorController.
 */

namespace Drupal\aggregator\Routing;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\ControllerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManager;
use Drupal\aggregator\FeedInterface;

/**
 * Returns responses for aggregator module routes.
 */
class AggregatorController implements ControllerInterface {

  /**
   * Stores the Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection;
   */
  protected $database;

  /**
   * Constructs a \Drupal\aggregator\Routing\AggregatorController object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The Entity manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(EntityManager $entity_manager, Connection $database) {
    $this->entityManager = $entity_manager;
    $this->database = $database;
  }

  /**
   * {inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity'),
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
    $feed = $this->entityManager
      ->getStorageController('aggregator_feed')
      ->create(array(
        'refresh' => 3600,
        'block' => 5,
      ));
    return entity_get_form($feed);
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
    return new RedirectResponse(url('admin/config/services/aggregator', array('absolute' => TRUE)));
  }

  /**
   * Displays the aggregator administration page.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function adminOverview() {
    $result = $this->database->query('SELECT f.fid, f.title, f.url, f.refresh, f.checked, f.link, f.description, f.hash, f.etag, f.modified, f.image, f.block, COUNT(i.iid) AS items FROM {aggregator_feed} f LEFT JOIN {aggregator_item} i ON f.fid = i.fid GROUP BY f.fid, f.title, f.url, f.refresh, f.checked, f.link, f.description, f.hash, f.etag, f.modified, f.image, f.block ORDER BY f.title');

    $header = array(t('Title'), t('Items'), t('Last update'), t('Next update'), t('Operations'));
    $rows = array();
    foreach ($result as $feed) {
      $row = array();
      $row[] = l($feed->title, "aggregator/sources/$feed->fid");
      $row[] = format_plural($feed->items, '1 item', '@count items');
      $row[] = ($feed->checked ? t('@time ago', array('@time' => format_interval(REQUEST_TIME - $feed->checked))) : t('never'));
      $row[] = ($feed->checked && $feed->refresh ? t('%time left', array('%time' => format_interval($feed->checked + $feed->refresh - REQUEST_TIME))) : t('never'));
      $links = array();
      $links['edit'] = array(
        'title' => t('Edit'),
        'href' => "admin/config/services/aggregator/edit/feed/$feed->fid",
      );
      $links['delete'] = array(
        'title' => t('Delete'),
        'href' => "admin/config/services/aggregator/delete/feed/$feed->fid",
      );
      $links['remove'] = array(
        'title' => t('Remove items'),
        'href' => "admin/config/services/aggregator/remove/$feed->fid",
      );
      $links['update'] = array(
        'title' => t('Update items'),
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
      '#prefix' => '<h3>' . t('Feed overview') . '</h3>',
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' =>  t('No feeds available. <a href="@link">Add feed</a>.', array('@link' => url('admin/config/services/aggregator/add/feed'))),
    );

    $result = $this->database->query('SELECT c.cid, c.title, COUNT(ci.iid) as items FROM {aggregator_category} c LEFT JOIN {aggregator_category_item} ci ON c.cid = ci.cid GROUP BY c.cid, c.title ORDER BY title');

    $header = array(t('Title'), t('Items'), t('Operations'));
    $rows = array();
    foreach ($result as $category) {
      $row = array();
      $row[] = l($category->title, "aggregator/categories/$category->cid");
      $row[] = format_plural($category->items, '1 item', '@count items');
      $links = array();
      $links['edit'] = array(
        'title' => t('Edit'),
        'href' => "admin/config/services/aggregator/edit/category/$category->cid",
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
      '#prefix' => '<h3>' . t('Category overview') . '</h3>',
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' =>  t('No categories available. <a href="@link">Add category</a>.', array('@link' => url('admin/config/services/aggregator/add/category'))),
    );

    return $build;
  }

}
