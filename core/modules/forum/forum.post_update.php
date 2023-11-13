<?php

/**
 * @file
 * Contains post update functions.
 */

use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;

/**
 * Repopulate the forum index table.
 */
function forum_post_update_recreate_forum_index_rows(&$sandbox = NULL): TranslatableMarkup {
  $entityStorage = \Drupal::entityTypeManager()->getStorage('node');
  if (!isset($sandbox['ids'])) {
    // This must be the first run. Initialize the sandbox.
    $sandbox['ids'] = \Drupal::state()->get('forum_update_10101_nids', []);
    $sandbox['max'] = count($sandbox['ids']);
  }

  $ids = array_splice($sandbox['ids'], 0, (int) Settings::get('entity_update_batch_size', 50));
  $insert = \Drupal::database()->insert('forum_index')->fields([
    'nid',
    'title',
    'tid',
    'sticky',
    'created',
    'last_comment_timestamp',
    'comment_count',
  ]);
  $do_insert = FALSE;
  foreach ($entityStorage->loadMultiple($ids) as $entity) {
    $do_insert = TRUE;
    assert($entity instanceof NodeInterface);
    $insert->values([
      $entity->id(),
      $entity->label(),
      $entity->taxonomy_forums->target_id,
      (int) $entity->isSticky(),
      $entity->getCreatedTime(),
      $entity->comment_forum->last_comment_timestamp,
      $entity->comment_forum->comment_count,
    ]);
  }
  if ($do_insert) {
    $insert->execute();
  }
  $sandbox['#finished'] = empty($sandbox['max']) || empty($sandbox['ids']) ? 1 : ($sandbox['max'] - count($sandbox['ids'])) / $sandbox['max'];
  if ($sandbox['#finished'] === 1) {
    \Drupal::state()->delete('forum_update_10101_nids');
    return new TranslatableMarkup('Finished updating forum index rows.');
  }
  return new PluralTranslatableMarkup($sandbox['max'] - count($sandbox['ids']),
    'Processed @count entry of @total.',
    'Processed @count entries of @total.',
    ['@total' => $sandbox['max']],
  );
}
