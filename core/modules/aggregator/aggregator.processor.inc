<?php

/**
 * @file
 * Processor functions for the aggregator module.
 */

use Drupal\aggregator\Plugin\Core\Entity\Feed;

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
        // @todo: The default entity render controller always returns an empty
        //   array, which is ignored in aggregator_save_item() currently. Should
        //   probably be fixed.
        if (empty($item['title'])) {
          continue;
        }

        // Save this item. Try to avoid duplicate entries as much as possible. If
        // we find a duplicate entry, we resolve it and pass along its ID is such
        // that we can update it if needed.
        if (!empty($item['guid'])) {
          $values = array('fid' => $feed->id(), 'guid' => $item['guid']);
        }
        elseif ($item['link'] && $item['link'] != $feed->link && $item['link'] != $feed->url) {
          $values = array('fid' => $feed->id(), 'link' => $item['link']);
        }
        else {
          $values = array('fid' => $feed->id(), 'title' => $item['title']);
        }

        // Try to load an existing entry.
        if ($entry = entity_load_multiple_by_properties('aggregator_item', $values)) {
          $entry = reset($entry);
        }
        else {
          $entry = entity_create('aggregator_item', array('langcode' => $feed->language()->langcode));
        }
        if ($item['timestamp']) {
          $entry->timestamp->value = $item['timestamp'];
        }

        // Make sure the item title and author fit in the 255 varchar column.
        $entry->title->value = truncate_utf8($item['title'], 255, TRUE, TRUE);
        $entry->author->value = truncate_utf8($item['author'], 255, TRUE, TRUE);

        $entry->fid->value = $feed->id();
        $entry->link->value = $item['link'];
        $entry->description->value = $item['description'];
        $entry->guid->value = $item['guid'];
        $entry->save();
      }
    }
  }
}

/**
 * Implements hook_aggregator_remove().
 */
function aggregator_aggregator_remove($feed) {
  $iids = db_query('SELECT iid FROM {aggregator_item} WHERE fid = :fid', array(':fid' => $feed->id()))->fetchCol();
  entity_delete_multiple('aggregator_item', $iids);

  drupal_set_message(t('The news items from %site have been removed.', array('%site' => $feed->label())));
}

/**
 * Implements hook_form_aggregator_admin_form_alter().
 *
 * Form alter aggregator module's own form to keep processor functionality
 * separate from aggregator API functionality.
 */
function aggregator_form_aggregator_admin_form_alter(&$form, $form_state) {
  $config = config('aggregator.settings');
  $aggregator_processors = $config->get('processors');
  if (in_array('aggregator', $aggregator_processors)) {
    $info = module_invoke('aggregator', 'aggregator_process', 'info');
    $items = drupal_map_assoc(array(3, 5, 10, 15, 20, 25), '_aggregator_items');
    $period = drupal_map_assoc(array(3600, 10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800, 1209600, 2419200, 4838400, 9676800), 'format_interval');
    $period[AGGREGATOR_CLEAR_NEVER] = t('Never');

    // Only wrap into details if there is a basic configuration.
    if (isset($form['basic_conf'])) {
      $form['modules']['aggregator'] = array(
        '#type' => 'details',
        '#title' => t('Default processor settings'),
        '#description' => $info['description'],
        '#collapsed' => !in_array('aggregator', $aggregator_processors),
      );
    }
    else {
      $form['modules']['aggregator'] = array();
    }

    $form['modules']['aggregator']['aggregator_summary_items'] = array(
      '#type' => 'select',
      '#title' => t('Number of items shown in listing pages'),
      '#default_value' => config('aggregator.settings')->get('source.list_max'),
      '#empty_value' => 0,
      '#options' => $items,
    );

    $form['modules']['aggregator']['aggregator_clear'] = array(
      '#type' => 'select',
      '#title' => t('Discard items older than'),
      '#default_value' => config('aggregator.settings')->get('items.expire'),
      '#options' => $period,
      '#description' => t('Requires a correctly configured <a href="@cron">cron maintenance task</a>.', array('@cron' => url('admin/reports/status'))),
    );

    $form['modules']['aggregator']['aggregator_category_selector'] = array(
      '#type' => 'radios',
      '#title' => t('Select categories using'),
      '#default_value' => config('aggregator.settings')->get('source.category_selector'),
      '#options' => array('checkboxes' => t('checkboxes'),
      'select' => t('multiple selector')),
      '#description' => t('For a small number of categories, checkboxes are easier to use, while a multiple selector works well with large numbers of categories.'),
    );
    $form['modules']['aggregator']['aggregator_teaser_length'] = array(
      '#type' => 'select',
      '#title' => t('Length of trimmed description'),
      '#default_value' => config('aggregator.settings')->get('items.teaser_length'),
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
 *
 * @param int $length
 *   The desired length of teaser text, in bytes.
 *
 * @return string
 *   A translated string explaining the teaser string length.
 */
function _aggregator_characters($length) {
  return ($length == 0) ? t('Unlimited') : format_plural($length, '1 character', '@count characters');
}

/**
 * Expires items from a feed depending on expiration settings.
 *
 * @param \Drupal\aggregator\Plugin\Core\Entity\Feed $feed
 *   Object describing feed.
 */
function aggregator_expire(Feed $feed) {
  $aggregator_clear = config('aggregator.settings')->get('items.expire');

  if ($aggregator_clear != AGGREGATOR_CLEAR_NEVER) {
    // Remove all items that are older than flush item timer.
    $age = REQUEST_TIME - $aggregator_clear;
    $iids = db_query('SELECT iid FROM {aggregator_item} WHERE fid = :fid AND timestamp < :timestamp', array(
      ':fid' => $feed->id(),
      ':timestamp' => $age,
    ))
    ->fetchCol();
    if ($iids) {
      entity_delete_multiple('aggregator_item', $iids);
    }
  }
}
