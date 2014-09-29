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
    // Load nodes into an array with the same order as $tracker_data.
    $nodes = entity_load_multiple('node', array_keys($tracker_data));

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

      $nodes[$nid]->last_activity = $tracker_data[$nid]->changed;
    }

    // Display the data.
    foreach ($nodes as $node) {
      // Determine the number of comments.
      $comments = 0;
      if ($node->comment_count) {
        $comments = $node->comment_count;

        if ($new = \Drupal::service('comment.manager')->getCountNewComments($node)) {
          $comments .= '<br />';
          $comments .= \Drupal::l(format_plural($new, '1 new', '@count new'), $node->urlInfo()->setOptions(array('fragment' => 'new')));
        }
      }

      $mark_build = array(
        '#theme' => 'mark',
        '#status' => node_mark($node->id(), $node->getChangedTime()),
      );

      $row = array(
        'type' => String::checkPlain(node_get_type_label($node)),
        'title' => array('data' => \Drupal::l($node->getTitle(), $node->urlInfo()) . ' ' . drupal_render($mark_build)),
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
