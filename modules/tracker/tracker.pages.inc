<?php
// $Id: tracker.pages.inc,v 1.31 2010/01/14 06:23:40 webchick Exp $

/**
 * @file
 * User page callbacks for the tracker module.
 */


/**
 * Menu callback; prints a listing of active nodes on the site.
 */
function tracker_page($account = NULL, $set_title = FALSE) {
  if ($account) {
    $query = db_select('tracker_user', 't')->extend('PagerDefault');
    $query->condition('t.uid', $account->uid);

    if ($set_title) {
      // When viewed from user/%user/track, display the name of the user
      // as page title -- the tab title remains Track so this needs to be done
      // here and not in the menu definition.
      drupal_set_title(format_username($account));
    }
  }
  else {
    $query = db_select('tracker_node', 't', array('target' => 'slave'))->extend('PagerDefault');
  }

  // This array acts as a placeholder for the data selected later
  // while keeping the correct order.
  $nodes = $query
    ->addTag('node_access')
    ->fields('t', array('nid', 'changed'))
    ->condition('t.published', 1)
    ->orderBy('t.changed', 'DESC')
    ->limit(25)
    ->execute()
    ->fetchAllAssoc('nid');

  $rows = array();
  if (!empty($nodes)) {
    // Now, get the data and put into the placeholder array
    $result = db_query('SELECT n.nid, n.title, n.type, n.changed, n.uid, u.name, l.comment_count FROM {node} n INNER JOIN {node_comment_statistics} l ON n.nid = l.nid INNER JOIN {users} u ON n.uid = u.uid WHERE n.nid IN (:nids)', array(':nids' => array_keys($nodes)), array('target' => 'slave'));
    foreach ($result as $node) {
      $node->last_activity = $nodes[$node->nid]->changed;
      $nodes[$node->nid] = $node;
    }

    // Finally display the data
    foreach ($nodes as $node) {
      // Determine the number of comments:
      $comments = 0;
      if ($node->comment_count) {
        $comments = $node->comment_count;

        if ($new = comment_num_new($node->nid)) {
          $comments .= '<br />';
          $comments .= l(format_plural($new, '1 new', '@count new'), 'node/'. $node->nid, array('fragment' => 'new'));
        }
      }

      $row = array(
        'type' => check_plain(node_type_get_name($node->type)),
        'title' => array('data' => l($node->title, 'node/' . $node->nid) . ' ' . theme('mark', array('type' => node_mark($node->nid, $node->changed)))),
        'author' => array('data' => theme('username', array('account' => $node))),
        'replies' => array('class' => array('replies'), 'data' => $comments),
        'last updated' => array('data' => t('!time ago', array('!time' => format_interval(REQUEST_TIME - $node->last_activity)))),
      );

      // Adds extra RDFa markup to the $row array if the RDF module is enabled.
      if (function_exists('rdf_mapping_load')) {
        // Each node is not loaded for performance reasons, as a result we need
        // to retrieve the RDF mapping for each node type.
        $mapping = rdf_mapping_load('node', $node->type);
        // Adds RDFa markup to the title of the node. Because the RDFa markup is
        // added to the td tag which might contain HTML code, we specify an
        // empty datatype to ensure the value of the title read by the RDFa
        // parsers is a plain literal.
        $row['title'] += rdf_rdfa_attributes($mapping['title']) + array('datatype' => '');
        // Annotates the td tag containing the author of the node.
        $row['author'] += rdf_rdfa_attributes($mapping['uid']);
        // Annotates the td tag containing the number of replies. We add the
        // content attribute to ensure that only the comment count is used as
        // the value for 'num_replies'. Otherwise, other text such as a link
        // to the number of new comments could be included in the 'num_replies'
        // value.
        $row['replies'] += rdf_rdfa_attributes($mapping['comment_count']);
        $row['replies'] += array('content' => $node->comment_count);
        // If the node has no comments, we assume the node itself was modified
        // and apply 'changed' in addition to 'last_activity'.  If there are
        // comments present, we cannot infer whether the node itself was
        // modified or a comment was posted, so we use only 'last_activity'.
        $mapping_last_activity = rdf_rdfa_attributes($mapping['last_activity'], $node->last_activity);
        if ($node->comment_count == 0) {
          $mapping_changed = rdf_rdfa_attributes($mapping['changed'], $node->last_activity);
          $mapping_last_activity['property'] = array_merge($mapping_last_activity['property'], $mapping_changed['property']);
        }
        $row['last updated'] += $mapping_last_activity;

        // We need to add the about attribute on the tr tag to specify which
        // node the RDFa annoatations above apply to. We move the content of
        // $row to a 'data' sub array so we can specify attributes for the row.
        $row = array('data' => $row);
        $row['about'] = url('node/' . $node->nid);
      }
      $rows[] = $row;
    }
  }

  $page['tracker'] = array(
    '#rows' => $rows,
    '#header' => array(t('Type'), t('Title'), t('Author'), t('Replies'), t('Last updated')),
    '#theme' => 'table',
    '#empty' => t('No content available.'),
    '#attached' => array(
      'css' => array(drupal_get_path('module', 'tracker') . '/tracker.css' => array('preprocess' => FALSE)),
    ),
  );
  $page['pager'] = array(
    '#theme' => 'pager',
    '#quantity' => 25,
    '#weight' => 10,
  );
  $page['#sorted'] = TRUE;

  return $page;
}
