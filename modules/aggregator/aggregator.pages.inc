<?php
// $Id: aggregator.pages.inc,v 1.4 2007/10/02 16:03:17 dries Exp $

/**
 * @file
 * User page callbacks for the aggregator module.
 */

/**
 * Menu callback; displays the most recent items gathered from any feed.
 */
function aggregator_page_last() {
  drupal_add_feed(url('aggregator/rss'), variable_get('site_name', 'Drupal') .' '. t('aggregator'));

  $items = aggregator_feed_items_load('SELECT i.*, f.title AS ftitle, f.link AS flink FROM {aggregator_item} i INNER JOIN {aggregator_feed} f ON i.fid = f.fid ORDER BY i.timestamp DESC, i.iid DESC');

  return _aggregator_page_list($items, arg(1));
}

/**
 * Menu callback; displays all the items captured from a particular feed.
 */
function aggregator_page_source($arg1, $arg2 = NULL) {
  // If there are two arguments then this function is the categorize form, and
  // $arg1 is $form_state and $arg2 is $feed. Otherwise, $arg1 is $feed.
  $feed = is_array($arg2) ? $arg2 : $arg1;
  $feed = (object)$feed;
  drupal_set_title(check_plain($feed->title));
  $feed_source = theme('aggregator_feed_source', $feed);

  // It is safe to include the fid in the query because it's loaded from the
  // database by aggregator_feed_load.
  $items = aggregator_feed_items_load('SELECT * FROM {aggregator_item} WHERE fid = '. $feed->fid .' ORDER BY timestamp DESC, iid DESC');

  return _aggregator_page_list($items, arg(3), $feed_source);
}

/**
 * Menu callback; displays all the items aggregated in a particular category.
 */
function aggregator_page_category($arg1, $arg2 = NULL) {
  // If there are two arguments then we are called as a form, $arg1 is
  // $form_state and $arg2 is $category. Otherwise, $arg1 is $category.
  $category = is_array($arg2) ? $arg2 : $arg1;

  drupal_add_feed(url('aggregator/rss/'. $category['cid']), variable_get('site_name', 'Drupal') .' '. t('aggregator - @title', array('@title' => $category['title'])));

  // It is safe to include the cid in the query because it's loaded from the
  // database by aggregator_category_load.
  $items = aggregator_feed_items_load('SELECT i.*, f.title AS ftitle, f.link AS flink FROM {aggregator_category_item} c LEFT JOIN {aggregator_item} i ON c.iid = i.iid LEFT JOIN {aggregator_feed} f ON i.fid = f.fid WHERE cid = '. $category['cid'] .' ORDER BY timestamp DESC, i.iid DESC');

  return _aggregator_page_list($items, arg(3));
}

/**
 * Load feed items by passing a sql query.
 */
function aggregator_feed_items_load($sql) {
  $items = array();
  if (isset($sql)) {
    $result = pager_query($sql, 20);
    while ($item = db_fetch_object($result)) {
      $result_category = db_query('SELECT c.title, c.cid FROM {aggregator_category_item} ci LEFT JOIN {aggregator_category} c ON ci.cid = c.cid WHERE ci.iid = %d ORDER BY c.title', $item->iid);
      $item->categories = array();
      while ($item_categories = db_fetch_object($result_category)) {
        $item->categories[] = $item_categories;
      }
      $items[$item->iid] = $item;
    }
  }
  return $items;
}

/**
 * Prints an aggregator page listing a number of feed items. Various
 * menu callbacks use this function to print their feeds.
 */
function _aggregator_page_list($items, $op, $feed_source = '') {
  if (user_access('administer news feeds') && ($op == 'categorize')) {
    // Get form data.
    $output = aggregator_categorize_items($items, $feed_source);
  }
  else {
    // Assemble themed output.
    $output = $feed_source;
    foreach ($items as $item) {
      $output .= theme('aggregator_item', $item);
    }
    $output = theme('aggregator_wrapper', $output);
  }
  return $output;
}

/**
 * Form builder; build the page list form.
 *
 * @ingroup forms
 * @see aggregator_categorize_items_validate().
 * @see aggregator_categorize_items_submit().
 */
function aggregator_categorize_items($items, $feed_source = '') {
  $form['#submit'][] = 'aggregator_categorize_items_submit';
  $form['#validate'][] = 'aggregator_categorize_items_validate';
  $form['#theme'] = 'aggregator_categorize_items';
  $form['feed_source'] = array('#value' => $feed_source);
  $categories = array();
  $done = FALSE;
  $form['items'] = array();
  $form['categories'] = array('#tree' => TRUE);
  foreach ($items as $item) {
    $form['items'][$item->iid] = array('#value' => theme('aggregator_item', $item));
    $form['categories'][$item->iid] = array();
    $categories_result = db_query('SELECT c.cid, c.title, ci.iid FROM {aggregator_category} c LEFT JOIN {aggregator_category_item} ci ON c.cid = ci.cid AND ci.iid = %d', $item->iid);
    $selected = array();
    while ($category = db_fetch_object($categories_result)) {
      if (!$done) {
        $categories[$category->cid] = check_plain($category->title);
      }
      if ($category->iid) {
        $selected[] = $category->cid;
      }
    }
    $done = TRUE;
    $form['categories'][$item->iid] = array(
      '#type' => variable_get('aggregator_category_selector', 'checkboxes'),
      '#default_value' => $selected,
      '#options' => $categories,
      '#size' => 10,
      '#multiple' => TRUE
    );
  }
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save categories'));

  return $form;
}

function aggregator_categorize_items_validate($form_id, &$form) {
  if (!user_access('administer news feeds')) {
    form_error($form, t('You are not allowed to categorize this feed item.'));
  }
}

function aggregator_categorize_items_submit($form, &$form_state) {
  foreach ($form_state['values']['categories'] as $iid => $selection) {
    db_query('DELETE FROM {aggregator_category_item} WHERE iid = %d', $iid);
    foreach ($selection as $cid) {
      if ($cid) {
        db_query('INSERT INTO {aggregator_category_item} (cid, iid) VALUES (%d, %d)', $cid, $iid);
      }
    }
  }
  drupal_set_message(t('The categories have been saved.'));
}

/**
 * Theme the page list form for assigning categories.
 *
 * @ingroup themeable
 */
function theme_aggregator_categorize_items($form) {
  $output = drupal_render($form['feed_source']);
  $rows = array();
  if ($form['items']) {
    foreach (element_children($form['items']) as $key) {
      if (is_array($form['items'][$key])) {
        $rows[] = array(
          drupal_render($form['items'][$key]),
          array('data' => drupal_render($form['categories'][$key]), 'class' => 'categorize-item'),
        );
      }
    }
  }
  $output .= theme('table', array('', t('Categorize')), $rows);
  $output .= drupal_render($form['submit']);
  $output .= drupal_render($form);
  return theme('aggregator_wrapper', $output);
}

/**
 * Process variables for aggregator-wrapper.tpl.php.
 *
 * The $variables array contains the following arguments:
 * - $content
 *
 * @see aggregator-wrapper.tpl.php
 */
function template_preprocess_aggregator_wrapper(&$variables) {
  $variables['pager'] = theme('pager', NULL, 20, 0);
}

/**
 * Process variables for aggregator-item.tpl.php.
 *
 * The $variables array contains the following arguments:
 * - $item
 *
 * @see aggregator-item.tpl.php
 */
function template_preprocess_aggregator_item(&$variables) {
  $item = $variables['item'];

  $variables['feed_url'] = check_url($item->link);
  $variables['feed_title'] = check_plain($item->title);
  $variables['content'] = aggregator_filter_xss($item->description);

  $variables['source_url'] = '';
  $variables['source_title'] = '';
  if (isset($item->ftitle) && isset($item->fid)) {
    $variables['source_url'] = url("aggregator/sources/$item->fid");
    $variables['source_title'] = check_plain($item->ftitle);
  }
  if (date('Ymd', $item->timestamp) == date('Ymd')) {
    $variables['source_date'] = t('%ago ago', array('%ago' => format_interval(time() - $item->timestamp)));
  }
  else {
    $variables['source_date'] = format_date($item->timestamp, 'custom', variable_get('date_format_medium', 'D, m/d/Y - H:i'));
  }

  $variables['categories'] = array();
  foreach ($item->categories as $category) {
    $variables['categories'][$category->cid] = l($category->title, 'aggregator/categories/'. $category->cid);
  }
}

/**
 * Menu callback; displays all the feeds used by the aggregator.
 */
function aggregator_page_sources() {
  $result = db_query('SELECT f.fid, f.title, f.description, f.image, MAX(i.timestamp) AS last FROM {aggregator_feed} f LEFT JOIN {aggregator_item} i ON f.fid = i.fid GROUP BY f.fid, f.title, f.description, f.image ORDER BY last DESC, f.title');

  $output = '';
  while ($feed = db_fetch_object($result)) {
    // Most recent items:
    $summary_items = array();
    if (variable_get('aggregator_summary_items', 3)) {
      $items = db_query_range('SELECT i.title, i.timestamp, i.link FROM {aggregator_item} i WHERE i.fid = %d ORDER BY i.timestamp DESC', $feed->fid, 0, variable_get('aggregator_summary_items', 3));
      while ($item = db_fetch_object($items)) {
        $summary_items[] = theme('aggregator_summary_item', $item);
      }
    }
    $feed->url = url('aggregator/sources/'. $feed->fid);
    $output .= theme('aggregator_summary_items', $summary_items, $feed);
  }
  $output .= theme('feed_icon', url('aggregator/opml'), t('OPML feed'));

  return theme('aggregator_wrapper', $output);
}

/**
 * Menu callback; displays all the categories used by the aggregator.
 */
function aggregator_page_categories() {
  $result = db_query('SELECT c.cid, c.title, c.description FROM {aggregator_category} c LEFT JOIN {aggregator_category_item} ci ON c.cid = ci.cid LEFT JOIN {aggregator_item} i ON ci.iid = i.iid GROUP BY c.cid, c.title, c.description');

  $output = '';
  while ($category = db_fetch_object($result)) {
    if (variable_get('aggregator_summary_items', 3)) {
      $summary_items = array();
      $items = db_query_range('SELECT i.title, i.timestamp, i.link, f.title as feed_title, f.link as feed_link FROM {aggregator_category_item} ci LEFT JOIN {aggregator_item} i ON i.iid = ci.iid LEFT JOIN {aggregator_feed} f ON i.fid = f.fid WHERE ci.cid = %d ORDER BY i.timestamp DESC', $category->cid, 0, variable_get('aggregator_summary_items', 3));
      while ($item = db_fetch_object($items)) {
        $summary_items[] = theme('aggregator_summary_item', $item);
      }
    }
    $category->url = url('aggregator/categories/'. $category->cid);
    $output .= theme('aggregator_summary_items', $summary_items, $category);
  }

  return theme('aggregator_wrapper', $output);
}

/**
 * Menu callback; generate an RSS 0.92 feed of aggregator items or categories.
 */
function aggregator_page_rss() {
  $result = NULL;
  // arg(2) is the passed cid, only select for that category
  if (arg(2)) {
    $category = db_fetch_object(db_query('SELECT cid, title FROM {aggregator_category} WHERE cid = %d', arg(2)));
    $sql = 'SELECT i.*, f.title AS ftitle, f.link AS flink FROM {aggregator_category_item} c LEFT JOIN {aggregator_item} i ON c.iid = i.iid LEFT JOIN {aggregator_feed} f ON i.fid = f.fid WHERE cid = %d ORDER BY timestamp DESC, i.iid DESC';
    $result = db_query_range($sql, $category->cid, 0, variable_get('feed_default_items', 10));
  }
  // or, get the default aggregator items
  else {
    $category = NULL;
    $sql = 'SELECT i.*, f.title AS ftitle, f.link AS flink FROM {aggregator_item} i INNER JOIN {aggregator_feed} f ON i.fid = f.fid ORDER BY i.timestamp DESC, i.iid DESC';
    $result = db_query_range($sql, 0, variable_get('feed_default_items', 10));
  }

  while ($item = db_fetch_object($result)) {
    $feeds[] = $item;
  }
  return theme('aggregator_page_rss', $feeds, $category);
}

/**
 * Theme the rss output.
 *
 * @ingroup themeable
 */
function theme_aggregator_page_rss($feeds, $category = NULL) {

  drupal_set_header('Content-Type: application/rss+xml; charset=utf-8');

  $title = '';
  $url = '';
  if (isset($category)) {
    $title = ' '. t('in category') .' '. check_plain($category->title);
    $url = '/categories/'. $category->cid;
  }

  $items = '';
  $feed_length = variable_get('feed_item_length', 'teaser');
  foreach ($feeds as $feed) {
    switch ($feed_length) {
      case 'teaser':
        $teaser = node_teaser($feed->description);
        if ($teaser != $feed->description) {
          $teaser .= '<p><a href="'. check_url($feed->link) .'">'. t('read more') ."</a></p>\n";
        }
        $feed->description = $teaser;
        break;
      case 'title':
        $feed->description = '';
        break;
    }
    $items .= format_rss_item($feed->ftitle .': '. $feed->title, $feed->link, $feed->description, array('pubDate' => date('r', $feed->timestamp)));
  }

  $site_name = variable_get('site_name', 'Drupal');
  $url = url('aggregator'. $url, array('absolute' => TRUE));

  $output = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
  $output .= "<rss version=\"2.0\">\n";
  $output .= format_rss_channel($site_name .' '. t('aggregator'), $url, $site_name .' - '. t('aggregated feeds') . $title, $items);
  $output .= "</rss>\n";

  print $output;
}

/**
 * Menu callback; generates an OPML representation of all feeds.
 */
function aggregator_page_opml($cid = NULL) {
  if ($cid) {
    $result = db_query('SELECT f.title, f.url FROM {aggregator_feed} f LEFT JOIN {aggregator_category_feed} c on f.fid = c.fid WHERE c.cid = %d ORDER BY title', $cid);
  }
  else {
    $result = db_query('SELECT * FROM {aggregator_feed} ORDER BY title');
  }

  while ($item = db_fetch_object($result)) {
    $feeds[] = $item;
  }

  return theme('aggregator_page_opml', $feeds);
}

/**
 * Theme the opml feed output.
 *
 * @ingroup themeable
 */
function theme_aggregator_page_opml($feeds) {

  drupal_set_header('Content-Type: text/xml; charset=utf-8');

  $output = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
  $output .= "<opml version=\"1.1\">\n";
  $output .= "<head>\n";
  $output .= '<title>'. check_plain(variable_get('site_name', 'Drupal')) ."</title>\n";
  $output .= '<dateModified>'. gmdate('r') ."</dateModified>\n";
  $output .= "</head>\n";
  $output .= "<body>\n";
  foreach ($feeds as $feed) {
    $output .= '<outline text="'. check_plain($feed->title) .'" xmlUrl="'. check_url($feed->url) ."\" />\n";
  }
  $output .= implode('', $content);
  $output .= "</body>\n";
  $output .= "</opml>\n";

  print $output;
}

/**
 * Process variables for aggregator-summary-items.tpl.php.
 *
 * The $variables array contains the following arguments:
 * - $summary_items
 * - $source
 *
 * @see aggregator-summary-item.tpl.php
 */
function template_preprocess_aggregator_summary_items(&$variables) {
  $variables['title'] = check_plain($variables['source']->title);
  $variables['summary_list'] = theme('item_list', $variables['summary_items']);
  $variables['source_url'] = $variables['source']->url;
}

/**
 * Process variables for aggregator-summary-item.tpl.php.
 *
 * The $variables array contains the following arguments:
 * - $item
 *
 * @see aggregator-summary-item.tpl.php
 */
function template_preprocess_aggregator_summary_item(&$variables) {
  $item = $variables['item'];

  $variables['feed_url'] = check_url($item->link);
  $variables['feed_title'] = check_plain($item->title);
  $variables['source_age'] = t('%age old', array('%age' => format_interval(time() - $item->timestamp)));

  $variables['source_url'] = '';
  $variables['source_title'] = '';
  if (!empty($item->feed_link)) {
    $variables['source_url'] = check_url($item->feed_link);
    $variables['source_title'] = check_plain($item->feed_title);
  }
}

/**
 * Process variables for aggregator-feed-source.tpl.php.
 *
 * The $variables array contains the following arguments:
 * - $feed
 *
 * @see aggregator-feed-source.tpl.php
 */
function template_preprocess_aggregator_feed_source(&$variables) {
  $feed = $variables['feed'];

  $variables['source_icon'] = theme('feed_icon', $feed->url, t('!title feed', array('!title' => $feed->title)));
  $variables['source_image'] = $feed->image;
  $variables['source_description'] = aggregator_filter_xss($feed->description);
  $variables['source_url'] = check_url(url($feed->link, array('absolute' => TRUE)));

  if ($feed->checked) {
    $variables['last_checked'] = t('@time ago', array('@time' => format_interval(time() - $feed->checked)));
  }
  else {
    $variables['last_checked'] = t('never');
  }

  if (user_access('administer news feeds')) {
    $variables['last_checked'] = l($variables['last_checked'], 'admin/content/aggregator');
  }
}
