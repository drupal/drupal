<?php

/**
 * @file
 * User page callbacks for tracker.module.
 */

use Drupal\Component\Utility\String;


/**
 * Page callback: Generates a page of tracked nodes for the site.
 *
 * Queries the database for info, adds RDFa info if applicable, and generates
 * the render array that will be used to render the page.
 *
 * @return array
 *   A renderable array.
 *
 * @see tracker_menu()
 */
function tracker_page($account = NULL) {
  if ($account) {
    $query = db_select('tracker_user', 't')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->addMetaData('base_table', 'tracker_user')
      ->condition('t.uid', $account->id());
  }
  else {
    $query = db_select('tracker_node', 't', array('target' => 'replica'))
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->addMetaData('base_table', 'tracker_node');
  }

  // This array acts as a placeholder for the data selected later
  // while keeping the correct order.
  $tracker_data = $query
    ->addTag('node_access')
    ->fields('t', array('nid', 'changed'))
    ->condition('t.published', 1)
    ->orderBy('t.changed', 'DESC')
    ->limit(25)
    ->execute()
    ->fetchAllAssoc('nid');

  $rows = array();
  if (!empty($tracker_data)) {
    $nids = array_keys($tracker_data);
    $nodes = node_load_multiple($nids);
    // @todo This should be actually filtering on the desired language and just
    //   fall back to the default language.
    $result = db_query("
    SELECT
      n.nid,
      SUM(l.comment_count) AS comment_count
    FROM {node_field_data} n
      INNER JOIN {comment_entity_statistics} l
        ON n.nid = l.entity_id AND l.entity_type = 'node'
      INNER JOIN {users} u
        ON n.uid = u.uid
    WHERE n.nid IN (:nids)
      AND n.default_langcode = 1
    GROUP BY n.nid
    ORDER BY n.changed DESC", array(
      ':nids' => array_keys($nodes)
    ), array('target' => 'replica'))->fetchAllKeyed();
    foreach ($result as $nid => $comment_count) {
      $nodes[$nid]->last_activity = $tracker_data[$nid]->changed;
      $nodes[$nid]->comment_count = $comment_count;
    }

    // Display the data.
    foreach ($nodes as $node) {
      // Determine the number of comments.
      $comments = 0;
      if ($node->comment_count) {
        $comments = $node->comment_count;

        if ($new = \Drupal::service('comment.manager')->getCountNewComments($node)) {
          $comments .= '<br />';
          $comments .= l(format_plural($new, '1 new', '@count new'), 'node/' . $node->id(), array('fragment' => 'new'));
        }
      }

      $mark_build = array(
        '#theme' => 'mark',
        '#status' => node_mark($node->id(), $node->getChangedTime()),
      );

      $row = array(
        'type' => String::checkPlain(node_get_type_label($node)),
        'title' => array('data' => l($node->getTitle(), 'node/' . $node->id()) . ' ' . drupal_render($mark_build)),
        'author' => array('data' => array('#theme' => 'username', '#account' => $node->getOwner())),
        'replies' => array('class' => array('replies'), 'data' => $comments),
        'last updated' => array('data' => t('!time ago', array('!time' => \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - $node->last_activity)))),
      );

      $rows[] = $row;
    }
  }

  $page['tracker'] = array(
    '#rows' => $rows,
    '#header' => array(t('Type'), t('Title'), t('Author'), t('Replies'), t('Last updated')),
    '#type' => 'table',
    '#empty' => t('No content available.'),
  );
  $page['pager'] = array(
    '#theme' => 'pager',
    '#weight' => 10,
  );
  $page['#sorted'] = TRUE;

  return $page;
}
