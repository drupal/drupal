<?php

/**
 * @file
 * Content administration and module settings user interface.
 */

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Link;
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
 * @param bool $revisions
 *   (optional) TRUE if $nodes contains an array of revision IDs instead of
 *   node IDs. Defaults to FALSE; will be ignored if $load is FALSE.
 */
function node_mass_update(array $nodes, array $updates, $langcode = NULL, $load = FALSE, $revisions = FALSE) {
  // We use batch processing to prevent timeout when updating a large number
  // of nodes.
  if (count($nodes) > 10) {
    $batch_builder = (new BatchBuilder())
      // The operations do not live in the .module file, so we need to
      // tell the batch engine which file to load before calling them.
      ->setFile(\Drupal::service('extension.list.module')->getPath('node') . '/node.admin.inc')
      ->addOperation('_node_mass_update_batch_process', [$nodes, $updates, $langcode, $load, $revisions])
      ->setFinishCallback('_node_mass_update_batch_finished')
      ->setTitle(t('Processing'))
      ->setErrorMessage(t('The update has encountered an error.'))
      // We use a single multi-pass operation, so the default
      // 'Remaining x of y operations' message will be confusing here.
      ->setProgressMessage('');
    batch_set($batch_builder->toArray());
  }
  else {
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    if ($load && !$revisions) {
      $nodes = $storage->loadMultiple($nodes);
    }
    foreach ($nodes as $node) {
      if ($load && $revisions) {
        $node = $storage->loadRevision($node);
      }
      _node_mass_update_helper($node, $updates, $langcode);
    }
    \Drupal::messenger()->addStatus(t('The update has been performed.'));
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
  $langcodes = isset($langcode) ? [$langcode] : array_keys($node->getTranslationLanguages());
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
 * Implements callback_batch_operation().
 *
 * Executes a batch operation for node_mass_update().
 *
 * @param array $nodes
 *   An array of node IDs.
 * @param array $updates
 *   Associative array of updates.
 * @param string $langcode
 *   The language updates should be applied to. If none is specified all
 *   available languages are processed.
 * @param bool $load
 *   TRUE if $nodes contains an array of node IDs to be loaded, FALSE if it
 *   contains fully loaded nodes.
 * @param bool $revisions
 *   (optional) TRUE if $nodes contains an array of revision IDs instead of
 *   node IDs. Defaults to FALSE; will be ignored if $load is FALSE.
 * @param array|\ArrayAccess $context
 *   An array of contextual key/values.
 */
function _node_mass_update_batch_process(array $nodes, array $updates, $langcode, $load, $revisions, &$context) {
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($nodes);
    $context['sandbox']['nodes'] = $nodes;
  }

  // Process nodes by groups of 5.
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $count = min(5, count($context['sandbox']['nodes']));
  for ($i = 1; $i <= $count; $i++) {
    // For each nid, load the node, reset the values, and save it.
    $node = array_shift($context['sandbox']['nodes']);
    if ($load) {
      $node = $revisions ?
        $storage->loadRevision($node) : $storage->load($node);
    }
    $node = _node_mass_update_helper($node, $updates, $langcode);

    // Store result for post-processing in the finished callback.
    $context['results'][] = Link::fromTextAndUrl($node->label(), $node->toUrl())->toString();

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
 * Implements callback_batch_finished().
 *
 * Reports the 'finished' status of batch operation for node_mass_update().
 *
 * @param bool $success
 *   A boolean indicating whether the batch mass update operation successfully
 *   concluded.
 * @param string[] $results
 *   An array of rendered links to nodes updated via the batch mode process.
 * @param array $operations
 *   An array of function calls (not used in this function).
 *
 * @see _node_mass_update_batch_process()
 */
function _node_mass_update_batch_finished($success, $results, $operations) {
  if ($success) {
    \Drupal::messenger()->addStatus(t('The update has been performed.'));
  }
  else {
    \Drupal::messenger()->addError(t('An error occurred and processing did not complete.'));
    $message = \Drupal::translation()->formatPlural(count($results), '1 item successfully processed:', '@count items successfully processed:');
    $item_list = [
      '#theme' => 'item_list',
      '#items' => $results,
    ];
    $message .= \Drupal::service('renderer')->render($item_list);
    \Drupal::messenger()->addStatus($message);
  }
}
