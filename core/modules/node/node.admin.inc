<?php

/**
 * @file
 * Content administration and module settings user interface.
 */

use Drupal\Core\Language\Language;
use Drupal\node\NodeInterface;

/**
 * Updates all nodes in the passed-in array with the passed-in field values.
 *
 * IMPORTANT NOTE: This function is intended to work when called from a form
 * submission handler. Calling it outside of the form submission process may not
 * work correctly.
 *
 * @param array $nodes
 *   Array of node nids or nodes to update.
 * @param array $updates
 *   Array of key/value pairs with node field names and the value to update that
 *   field to.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 * @param bool $load
 *   (optional) TRUE if $nodes contains an array of node IDs to be loaded, FALSE
 *   if it contains fully loaded nodes. Defaults to FALSE.
 */
function node_mass_update(array $nodes, array $updates, $langcode = NULL, $load = FALSE) {
  // We use batch processing to prevent timeout when updating a large number
  // of nodes.
  if (count($nodes) > 10) {
    $batch = array(
      'operations' => array(
        array('_node_mass_update_batch_process', array($nodes, $updates, $langcode, $load))
      ),
      'finished' => '_node_mass_update_batch_finished',
      'title' => t('Processing'),
      // We use a single multi-pass operation, so the default
      // 'Remaining x of y operations' message will be confusing here.
      'progress_message' => '',
      'error_message' => t('The update has encountered an error.'),
      // The operations do not live in the .module file, so we need to
      // tell the batch engine which file to load before calling them.
      'file' => drupal_get_path('module', 'node') . '/node.admin.inc',
    );
    batch_set($batch);
  }
  else {
    if ($load) {
      $nodes = entity_load_multiple('node', $nodes);
    }
    foreach ($nodes as $node) {
      _node_mass_update_helper($node, $updates, $langcode);
    }
    drupal_set_message(t('The update has been performed.'));
  }
}

/**
 * Updates individual nodes when fewer than 10 are queued.
 *
 * @param \Drupal\node\NodeInterface $node
 *   A node to update.
 * @param array $updates
 *   Associative array of updates.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 *
 * @return \Drupal\node\NodeInterface
 *   An updated node object.
 *
 * @see node_mass_update()
 */
function _node_mass_update_helper(NodeInterface $node, array $updates, $langcode = NULL) {
  $langcodes = isset($langcode) ? array($langcode) : array_keys($node->getTranslationLanguages());
  // For efficiency manually save the original node before applying any changes.
  $node->original = clone $node;
  foreach ($langcodes as $langcode) {
    foreach ($updates as $name => $value) {
      $node->getTranslation($langcode)->$name = $value;
    }
  }
  $node->save();
  return $node;
}

/**
 * Executes a batch operation for node_mass_update().
 *
 * @param array $nodes
 *   An array of node IDs.
 * @param array $updates
 *   Associative array of updates.
 * @param bool $load
 *   TRUE if $nodes contains an array of node IDs to be loaded, FALSE if it
 *   contains fully loaded nodes.
 * @param array $context
 *   An array of contextual key/values.
 */
function _node_mass_update_batch_process(array $nodes, array $updates, $load, array &$context) {
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($nodes);
    $context['sandbox']['nodes'] = $nodes;
  }

  // Process nodes by groups of 5.
  $count = min(5, count($context['sandbox']['nodes']));
  for ($i = 1; $i <= $count; $i++) {
    // For each nid, load the node, reset the values, and save it.
    $node = array_shift($context['sandbox']['nodes']);
    if ($load) {
      $node = entity_load('node', $node);
    }
    $node = _node_mass_update_helper($node, $updates);

    // Store result for post-processing in the finished callback.
    $context['results'][] = l($node->label(), 'node/' . $node->id());

    // Update our progress information.
    $context['sandbox']['progress']++;
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Reports the 'finished' status of batch operation for node_mass_update().
 *
 * @param bool $success
 *   A boolean indicating whether the batch mass update operation successfully
 *   concluded.
 * @param int $results
 *   The number of nodes updated via the batch mode process.
 * @param array $operations
 *   An array of function calls (not used in this function).
 */
function _node_mass_update_batch_finished($success, $results, $operations) {
  if ($success) {
    drupal_set_message(t('The update has been performed.'));
  }
  else {
    drupal_set_message(t('An error occurred and processing did not complete.'), 'error');
    $message = format_plural(count($results), '1 item successfully processed:', '@count items successfully processed:');
    $item_list = array(
      '#theme' => 'item_list',
      '#items' => $results,
    );
    $message .= drupal_render($item_list);
    drupal_set_message($message);
  }
}

/**
 * Returns the admin form object to node_admin_content().
 *
 * @ingroup forms
 */
function node_admin_nodes() {
  // Enable language column and filter if multiple languages are enabled.
  $multilingual = language_multilingual();

  // Build the sortable table header.
  $header = array(
    'title' => array(
      'data' => t('Title'),
      'field' => 'n.title',
    ),
    'type' => array(
      'data' => t('Content type'),
      'field' => 'n.type',
      'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
    ),
    'author' => array(
      'data' => t('Author'),
      'class' => array(RESPONSIVE_PRIORITY_LOW),
    ),
    'status' => array(
      'data' => t('Status'),
      'field' => 'n.status',
    ),
    'changed' => array(
      'data' => t('Updated'),
      'field' => 'n.changed',
      'sort' => 'desc',
      'class' => array(RESPONSIVE_PRIORITY_LOW)
    ,)
  );
  if ($multilingual) {
    $header['language_name'] = array('data' => t('Language'), 'field' => 'n.langcode', 'class' => array(RESPONSIVE_PRIORITY_LOW));
  }
  $header['operations'] = array('data' => t('Operations'));

  $query = db_select('node_field_data', 'n')
    ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
    ->extend('Drupal\Core\Database\Query\TableSortExtender');

  if (!user_access('bypass node access')) {
    // If the user is able to view their own unpublished nodes, allow them
    // to see these in addition to published nodes. Check that they actually
    // have some unpublished nodes to view before adding the condition.
    if (user_access('view own unpublished content') && $own_unpublished = db_query('SELECT DISTINCT nid FROM {node_field_data} WHERE uid = :uid AND status = :status', array(':uid' => $GLOBALS['user']->id(), ':status' => 0))->fetchCol()) {
      $query->condition(db_or()
        ->condition('n.status', 1)
        ->condition('n.nid', $own_unpublished, 'IN')
      );
    }
    else {
      // If not, restrict the query to published nodes.
      $query->condition('n.status', 1);
    }
  }
  $nids = $query
    ->distinct()
    ->fields('n', array('nid'))
    ->limit(50)
    ->orderByHeader($header)
    ->addTag('node_access')
    ->execute()
    ->fetchCol();
  $nodes = node_load_multiple($nids);

  // Prepare the list of nodes.
  $languages = language_list(Language::STATE_ALL);
  $destination = drupal_get_destination();
  $form['nodes'] = array(
    '#type' => 'table',
    '#header' => $header,
    '#empty' => t('No content available.'),
  );
  foreach ($nodes as $node) {
    $l_options = $node->language()->id != Language::LANGCODE_NOT_SPECIFIED && isset($languages[$node->language()->id]) ? array('language' => $languages[$node->language()->id]) : array();
    $mark = array(
      '#theme' => 'mark',
      '#status' => node_mark($node->id(), $node->getChangedTime()),
    );
    $form['nodes'][$node->id()]['title'] = array(
      '#type' => 'link',
      '#title' => $node->label(),
      '#href' => 'node/' . $node->id(),
      '#options' => $l_options,
      '#suffix' => ' ' . drupal_render($mark),
    );
    $form['nodes'][$node->id()]['type'] = array(
      '#markup' => check_plain(node_get_type_label($node)),
    );
    $form['nodes'][$node->id()]['author'] = array(
      '#theme' => 'username',
      '#account' => $node->getAuthor(),
    );
    $form['nodes'][$node->id()]['status'] = array(
      '#markup' => $node->isPublished() ? t('published') : t('not published'),
    );
    $form['nodes'][$node->id()]['changed'] = array(
      '#markup' => format_date($node->getChangedTime(), 'short'),
    );
    if ($multilingual) {
      $form['nodes'][$node->id()]['language_name'] = array(
        '#markup' => $node->language()->name,
      );
    }

    // Build a list of all the accessible operations for the current node.
    $operations = array();
    if (node_access('update', $node)) {
      $operations['edit'] = array(
        'title' => t('Edit'),
        'href' => 'node/' . $node->id() . '/edit',
        'query' => $destination,
      );
    }
    if (node_access('delete', $node)) {
      $operations['delete'] = array(
        'title' => t('Delete'),
        'href' => 'node/' . $node->id() . '/delete',
        'query' => $destination,
      );
    }
    if ($node->isTranslatable()) {
      $operations['translate'] = array(
        'title' => t('Translate'),
        'href' => 'node/' . $node->id() . '/translations',
        'query' => $destination,
      );
    }
    $form['nodes'][$node->id()]['operations'] = array();
    if (count($operations) > 1) {
      // Render an unordered list of operations links.
      $form['nodes'][$node->id()]['operations'] = array(
        '#type' => 'operations',
        '#subtype' => 'node',
        '#links' => $operations,
      );
    }
    elseif (!empty($operations)) {
      // Render the first and only operation as a link.
      $link = reset($operations);
      $form['nodes'][$node->id()]['operations'] = array(
        '#type' => 'link',
        '#title' => $link['title'],
        '#href' => $link['href'],
        '#options' => array('query' => $link['query']),
      );
    }
  }

  $form['pager'] = array('#theme' => 'pager');
  return $form;
}
