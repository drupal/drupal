<?php

/**
 * @file
 * User page callbacks for tracker.module.
 */

use Drupal\Core\Cache\Cache;
use Drupal\node\Entity\Node;

/**
 * Page callback: Generates a page of tracked nodes for the site.
 *
 * Queries the database for info, adds RDFa info if applicable, and generates
 * the render array that will be used to render the page.
 *
 * @param \Drupal\user\UserInterface $account
 *   (optional) The user account to track.
 *
 * @return array
 *   A renderable array.
 */
function tracker_page($account = NULL) {
  if ($account) {
    $query = db_select('tracker_user', 't')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->addMetaData('base_table', 'tracker_user')
      ->condition('t.uid', $account->id());
  }
  else {
    $query = db_select('tracker_node', 't', ['target' => 'replica'])
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->addMetaData('base_table', 'tracker_node');
  }

  // This array acts as a placeholder for the data selected later
  // while keeping the correct order.
  $tracker_data = $query
    ->addTag('node_access')
    ->fields('t', ['nid', 'changed'])
    ->condition('t.published', 1)
    ->orderBy('t.changed', 'DESC')
    ->limit(25)
    ->execute()
    ->fetchAllAssoc('nid');

  $cache_tags = [];
  $rows = [];
  if (!empty($tracker_data)) {
    // Load nodes into an array with the same order as $tracker_data.
    $nodes = Node::loadMultiple(array_keys($tracker_data));

    // Enrich the node data.
    $result = \Drupal::service('comment.statistics')->read($nodes, 'node', FALSE);
    foreach ($result as $statistics) {
      // The node ID may not be unique; there can be multiple comment fields.
      // Make comment_count the total of all comments.
      $nid = $statistics->entity_id;
      if (empty($nodes[$nid]->comment_count)
          || !is_numeric($nodes[$nid]->comment_count)) {
        $nodes[$nid]->comment_count = $statistics->comment_count;
      }
      else {
        $nodes[$nid]->comment_count += $statistics->comment_count;
      }
      // Make the last comment timestamp reflect the latest comment.
      if (!isset($nodes[$nid]->last_comment_timestamp)) {
        $nodes[$nid]->last_comment_timestamp = $statistics->last_comment_timestamp;
      }
      else {
        $nodes[$nid]->last_comment_timestamp = max($nodes[$nid]->last_comment_timestamp, $statistics->last_comment_timestamp);
      }
    }

    // Display the data.
    foreach ($nodes as $node) {
      // Set the last activity time from tracker data. This also takes into
      // account comment activity, so getChangedTime() is not used.
      $node->last_activity = $tracker_data[$node->id()]->changed;

      // Determine the number of comments.
      $comments = 0;
      if ($node->comment_count) {
        $comments = $node->comment_count;
      }

      $row = [
        'type' => node_get_type_label($node),
        'title' => [
          'data' => [
            '#type' => 'link',
            '#url' => $node->urlInfo(),
            '#title' => $node->getTitle(),
          ],
          'data-history-node-id' => $node->id(),
          'data-history-node-timestamp' => $node->getChangedTime(),
        ],
        'author' => [
          'data' => [
            '#theme' => 'username',
            '#account' => $node->getOwner(),
          ],
        ],
        'comments' => [
          'class' => ['comments'],
          'data' => $comments,
          'data-history-node-last-comment-timestamp' => $node->last_comment_timestamp,
        ],
        'last updated' => [
          'data' => t('@time ago', [
            '@time' => \Drupal::service('date.formatter')->formatTimeDiffSince($node->last_activity),
          ]),
        ],
      ];

      $rows[] = $row;

      // Add node and node owner to cache tags.
      $cache_tags = Cache::mergeTags($cache_tags, $node->getCacheTags());
      if ($node->getOwner()) {
        $cache_tags = Cache::mergeTags($cache_tags, $node->getOwner()->getCacheTags());
      }
    }
  }

  // Add the list cache tag for nodes.
  $cache_tags = Cache::mergeTags($cache_tags, \Drupal::entityManager()->getDefinition('node')->getListCacheTags());

  $page['tracker'] = [
    '#rows' => $rows,
    '#header' => [t('Type'), t('Title'), t('Author'), t('Comments'), t('Last updated')],
    '#type' => 'table',
    '#empty' => t('No content available.'),
  ];
  $page['pager'] = [
    '#type' => 'pager',
    '#weight' => 10,
  ];
  $page['#sorted'] = TRUE;
  $page['#cache']['tags'] = $cache_tags;
  $page['#cache']['contexts'][] = 'user.node_grants:view';

  // Display the reading history if that module is enabled.
  if (\Drupal::moduleHandler()->moduleExists('history')) {
    // Reading history is tracked for authenticated users only.
    if (\Drupal::currentUser()->isAuthenticated()) {
      $page['#attached']['library'][] = 'tracker/history';
    }
    $page['#cache']['contexts'][] = 'user.roles:authenticated';
  }

  return $page;
}
