<?php

/**
 * @file
 * Contains \Drupal\aggregator\Controller\AggregatorController.
 */

namespace Drupal\aggregator\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\aggregator\FeedInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for aggregator module routes.
 */
class AggregatorController extends ControllerBase {

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
    $items = $entity_manager->getStorage('aggregator_item')->loadByFeed($aggregator_feed->id(), 20);
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
    foreach ($feeds as $feed) {
      $row = array();
      $row[] = l($feed->label(), "aggregator/sources/" . $feed->id());
      $row[] = format_plural($entity_manager->getStorage('aggregator_item')->getItemCount($feed), '1 item', '@count items');
      $last_checked = $feed->getLastCheckedTime();
      $refresh_rate = $feed->getRefreshRate();
      $row[] = ($last_checked ? $this->t('@time ago', array('@time' => format_interval(REQUEST_TIME - $last_checked))) : $this->t('never'));
      $row[] = ($last_checked && $refresh_rate ? $this->t('%time left', array('%time' => format_interval($last_checked + $refresh_rate - REQUEST_TIME))) : $this->t('never'));
      $links['edit'] = array(
        'title' => $this->t('Edit'),
        'route_name' => 'aggregator.feed_configure',
        'route_parameters' => array('aggregator_feed' => $feed->id()),
      );
      $links['delete'] = array(
        'title' => $this->t('Delete'),
        'route_name' => 'aggregator.feed_delete',
        'route_parameters' => array('aggregator_feed' => $feed->id()),
      );
      $links['delete_items'] = array(
        'title' => $this->t('Delete items'),
        'route_name' => 'aggregator.feed_items_delete',
        'route_parameters' => array('aggregator_feed' => $feed->id()),
      );
      $links['update'] = array(
        'title' => $this->t('Update items'),
        'route_name' => 'aggregator.feed_refresh',
        'route_parameters' => array('aggregator_feed' => $feed->id()),
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
      '#empty' => $this->t('No feeds available. <a href="@link">Add feed</a>.', array('@link' => $this->urlGenerator()->generate('aggregator.feed_add'))),
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
   * Displays all the feeds used by the Aggregator module.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function sources() {
    $entity_manager = $this->entityManager();

    $feeds = $entity_manager->getStorage('aggregator_feed')->loadMultiple();

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
        $items = $entity_manager->getStorage('aggregator_item')
          ->loadByFeed($feed->id(), 20);
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
     $feeds = $this->entityManager()
      ->getStorage('aggregator_feed')
      ->loadMultiple();

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
