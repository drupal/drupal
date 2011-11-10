<?php

/**
 * @file
 * Processor functions for the aggregator module.
 */

/**
 * Implements hook_aggregator_process_info().
 */
function aggregator_aggregator_process_info() {
  return array(
    'title' => t('Default processor'),
    'description' => t('Creates lightweight records from feed items.'),
  );
}

/**
 * Implements hook_aggregator_process().
 */
function aggregator_aggregator_process($feed) {
  if (is_object($feed)) {
    if (is_array($feed->items)) {
      foreach ($feed->items as $item) {
        // Save this item. Try to avoid duplicate entries as much as possible. If
        // we find a duplicate entry, we resolve it and pass along its ID is such
        // that we can update it if needed.
        if (!empty($item['guid'])) {
          $entry = db_query("SELECT iid, timestamp FROM {aggregator_item} WHERE fid = :fid AND guid = :guid", array(':fid' => $feed->fid, ':guid' => $item['guid']))->fetchObject();
        }
        elseif ($item['link'] && $item['link'] != $feed->link && $item['link'] != $feed->url) {
          $entry = db_query("SELECT iid, timestamp FROM {aggregator_item} WHERE fid = :fid AND link = :link", array(':fid' => $feed->fid, ':link' => $item['link']))->fetchObject();
        }
        else {
          $entry = db_query("SELECT iid, timestamp FROM {aggregator_item} WHERE fid = :fid AND title = :title", array(':fid' => $feed->fid, ':title' => $item['title']))->fetchObject();
        }
        if (!$item['timestamp']) {
          $item['timestamp'] = isset($entry->timestamp) ? $entry->timestamp : REQUEST_TIME;
        }

        // Make sure the item title fits in 255 varchar column.
        $item['title'] = truncate_utf8($item['title'], 255, TRUE, TRUE);
        aggregator_save_item(array('iid' => (isset($entry->iid) ? $entry->iid : ''), 'fid' => $feed->fid, 'timestamp' => $item['timestamp'], 'title' => $item['title'], 'link' => $item['link'], 'author' => $item['author'], 'description' => $item['description'], 'guid' => $item['guid']));
      }
    }
  }
}

/**
 * Implements hook_aggregator_remove().
 */
function aggregator_aggregator_remove($feed) {
  $iids = db_query('SELECT iid FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $feed->fid))->fetchCol();
  if ($iids) {
    db_delete('aggregator_category_item')
      ->condition('iid', $iids, 'IN')
      ->execute();
  }
  db_delete('aggregator_item')
    ->condition('fid', $feed->fid)
    ->execute();

  drupal_set_message(t('The news items from %site have been removed.', array('%site' => $feed->title)));
}

/**
 * Implements hook_form_aggregator_admin_form_alter().
 *
 * Form alter aggregator module's own form to keep processor functionality
 * separate from aggregator API functionality.
 */
function aggregator_form_aggregator_admin_form_alter(&$form, $form_state) {
  if (in_array('aggregator', variable_get('aggregator_processors', array('aggregator')))) {
    $info = module_invoke('aggregator', 'aggregator_process', 'info');
    $items = drupal_map_assoc(array(3, 5, 10, 15, 20, 25), '_aggregator_items');
    $period = drupal_map_assoc(array(3600, 10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800, 1209600, 2419200, 4838400, 9676800), 'format_interval');
    $period[AGGREGATOR_CLEAR_NEVER] = t('Never');

    // Only wrap into a collapsible fieldset if there is a basic configuration.
    if (isset($form['basic_conf'])) {
      $form['modules']['aggregator'] = array(
        '#type' => 'fieldset',
        '#title' => t('Default processor settings'),
        '#description' => $info['description'],
        '#collapsible' => TRUE,
        '#collapsed' => !in_array('aggregator', variable_get('aggregator_processors', array('aggregator'))),
      );
    }
    else {
      $form['modules']['aggregator'] = array();
    }

    $form['modules']['aggregator']['aggregator_summary_items'] = array(
      '#type' => 'select',
      '#title' => t('Number of items shown in listing pages'),
      '#default_value' => variable_get('aggregator_summary_items', 3),
      '#empty_value' => 0,
      '#options' => $items,
    );

    $form['modules']['aggregator']['aggregator_clear'] = array(
      '#type' => 'select',
      '#title' => t('Discard items older than'),
      '#default_value' => variable_get('aggregator_clear', 9676800),
      '#options' => $period,
      '#description' => t('Requires a correctly configured <a href="@cron">cron maintenance task</a>.', array('@cron' => url('admin/reports/status'))),
    );

    $form['modules']['aggregator']['aggregator_category_selector'] = array(
      '#type' => 'radios',
      '#title' => t('Select categories using'),
      '#default_value' => variable_get('aggregator_category_selector', 'checkboxes'),
      '#options' => array('checkboxes' => t('checkboxes'),
      'select' => t('multiple selector')),
      '#description' => t('For a small number of categories, checkboxes are easier to use, while a multiple selector works well with large numbers of categories.'),
    );
    $form['modules']['aggregator']['aggregator_teaser_length'] = array(
      '#type' => 'select',
      '#title' => t('Length of trimmed description'),
      '#default_value' => variable_get('aggregator_teaser_length', 600),
      '#options' => drupal_map_assoc(array(0, 200, 400, 600, 800, 1000, 1200, 1400, 1600, 1800, 2000), '_aggregator_characters'),
      '#description' => t("The maximum number of characters used in the trimmed version of content.")
    );

  }
}

/**
 * Creates display text for teaser length option values.
 *
 * Callback for drupal_map_assoc() within
 * aggregator_form_aggregator_admin_form_alter().
 */
function _aggregator_characters($length) {
  return ($length == 0) ? t('Unlimited') : format_plural($length, '1 character', '@count characters');
}

/**
 * Adds/edits/deletes an aggregator item.
 *
 * @param $edit
 *   An associative array describing the item to be added/edited/deleted.
 */
function aggregator_save_item($edit) {
  if ($edit['title'] && empty($edit['iid'])) {
    $edit['iid'] = db_insert('aggregator_item')
      ->fields(array(
        'title' => $edit['title'],
        'link' => $edit['link'],
        'author' => $edit['author'],
        'description' => $edit['description'],
        'guid' => $edit['guid'],
        'timestamp' => $edit['timestamp'],
        'fid' => $edit['fid'],
      ))
      ->execute();
  }
  if ($edit['iid'] && !$edit['title']) {
    db_delete('aggregator_item')
      ->condition('iid', $edit['iid'])
      ->execute();
    db_delete('aggregator_category_item')
      ->condition('iid', $edit['iid'])
      ->execute();
  }
  elseif ($edit['title'] && $edit['link']) {
    // file the items in the categories indicated by the feed
    $result = db_query('SELECT cid FROM {aggregator_category_feed} WHERE fid = :fid', array(':fid' => $edit['fid']));
    foreach ($result as $category) {
      db_merge('aggregator_category_item')
        ->key(array(
          'iid' => $edit['iid'],
          'cid' => $category->cid,
        ))
        ->execute();
    }
  }
}

/**
 * Expires items from a feed depending on expiration settings.
 *
 * @param $feed
 *   Object describing feed.
 */
function aggregator_expire($feed) {
  $aggregator_clear = variable_get('aggregator_clear', 9676800);

  if ($aggregator_clear != AGGREGATOR_CLEAR_NEVER) {
    // Remove all items that are older than flush item timer.
    $age = REQUEST_TIME - $aggregator_clear;
    $iids = db_query('SELECT iid FROM {aggregator_item} WHERE fid = :fid AND timestamp < :timestamp', array(
      ':fid' => $feed->fid,
      ':timestamp' => $age,
    ))
    ->fetchCol();
    if ($iids) {
      db_delete('aggregator_category_item')
        ->condition('iid', $iids, 'IN')
        ->execute();
      db_delete('aggregator_item')
        ->condition('iid', $iids, 'IN')
        ->execute();
    }
  }
}
