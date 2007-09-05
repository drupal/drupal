<?php
// $Id: aggregator.pages.inc,v 1.2 2007/09/05 18:24:25 dries Exp $

/**
 * @file
 * User page callbacks for the aggregator module.
 */

/**
 * Menu callback; displays the most recent items gathered from any feed.
 */
function aggregator_page_last() {
  drupal_add_feed(url('aggregator/rss'), variable_get('site_name', 'Drupal') .' '. t('aggregator'));

  return _aggregator_page_list('SELECT i.*, f.title AS ftitle, f.link AS flink FROM {aggregator_item} i INNER JOIN {aggregator_feed} f ON i.fid = f.fid ORDER BY i.timestamp DESC, i.iid DESC', arg(1));
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
  $info = theme('aggregator_feed', $feed);

  return _aggregator_page_list('SELECT * FROM {aggregator_item} WHERE fid = '. $feed->fid .' ORDER BY timestamp DESC, iid DESC', arg(3), $info);
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
  return _aggregator_page_list('SELECT i.*, f.title AS ftitle, f.link AS flink FROM {aggregator_category_item} c LEFT JOIN {aggregator_item} i ON c.iid = i.iid LEFT JOIN {aggregator_feed} f ON i.fid = f.fid WHERE cid = '. $category['cid'] .' ORDER BY timestamp DESC, i.iid DESC', arg(3));
}

/**
 * Prints an aggregator page listing a number of feed items. Various
 * menu callbacks use this function to print their feeds.
 *
 */
function _aggregator_page_list($sql, $op, $header = '') {
  $categorize = (user_access('administer news feeds') && ($op == 'categorize'));
  $form = aggregator_page_list($sql, $header, $categorize);
  if ($categorize) {
    return $form;
  }
  else {
    $output = '<div id="aggregator">';
    $output .= $header;
    foreach ($form['items'] as $item) {
      $output .= $item['#value'];
    }
    $output .= '</div>';
    $output .= $form['pager']['#value'];
    if (isset($form['feed_icon']['#value'])) {
      $output .= $form['feed_icon']['#value'];
    }
    return $output;
  }
}

/**
 * Form builder; build the page list form.
 *
 * @ingroup forms
 * @see aggregator_page_list_validate().
 * @see aggregator_page_list_submit().
 */
function aggregator_page_list($sql, $header, $categorize) {
  $form['#submit'][] = 'aggregator_page_list_submit';
  $form['#validate'][] = 'aggregator_page_list_validate';
  $form['#theme'] = 'aggregator_page_list';
  $form['header'] = array('#value' => $header);
  $result = pager_query($sql, 20);
  $categories = array();
  $done = FALSE;
  $form['items'] = array();
  $form['categories'] = array('#tree' => TRUE);
  while ($item = db_fetch_object($result)) {
    $form['items'][$item->iid] = array('#value' => theme('aggregator_page_item', $item));
    $form['categories'][$item->iid] = array();
    if ($categorize) {
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
        '#default_value' => $selected, '#options' => $categories,
        '#size' => 10, '#multiple' => TRUE
      );
    }
  }
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save categories'));
  $form['pager'] = array('#value' => theme('pager', NULL, 20, 0));

  return $form;
}

function aggregator_page_list_validate($form_id, &$form) {
  if (!user_access('administer news feeds')) {
    form_error($form, t('You are not allowed to categorize this feed item.'));
  }
}

function aggregator_page_list_submit($form, &$form_state) {
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
 * Theme the page list form.
 *
 * @ingroup themeable
 */
function theme_aggregator_page_list($form) {
  $output = '<div id="aggregator">';
  $output .= drupal_render($form['header']);
  $rows = array();
  if ($form['items']) {
    foreach (element_children($form['items']) as $key) {
      if (is_array($form['items'][$key])) {
        $rows[] = array(drupal_render($form['items'][$key]), array('data' => drupal_render($form['categories'][$key]), 'class' => 'categorize-item'));
      }
    }
  }
  $output .= theme('table', array('', t('Categorize')), $rows);
  $output .= drupal_render($form['submit']);
  $output .= '</div>';
  $output .= drupal_render($form);
  return $output;
}


/**
 * Format an individual feed item for display on the aggregator page.
 *
 * @ingroup themeable
 */
function theme_aggregator_page_item($item) {

  $source = '';
  if (isset($item->ftitle) && isset($item->fid)) {
    $source = l($item->ftitle, "aggregator/sources/$item->fid", array('attributes' => array('class' => 'feed-item-source'))) .' -';
  }

  if (date('Ymd', $item->timestamp) == date('Ymd')) {
    $source_date = t('%ago ago', array('%ago' => format_interval(time() - $item->timestamp)));
  }
  else {
    $source_date = format_date($item->timestamp, 'custom', variable_get('date_format_medium', 'D, m/d/Y - H:i'));
  }

  $output = "<div class=\"feed-item\">\n";
  $output .= '<h3 class="feed-item-title"><a href="'. check_url($item->link) .'">'. check_plain($item->title) ."</a></h3>\n";
  $output .= "<div class=\"feed-item-meta\">$source <span class=\"feed-item-date\">$source_date</span></div>\n";

  if ($item->description) {
    $output .= '<div class="feed-item-body">'. aggregator_filter_xss($item->description) ."</div>\n";
  }

  $result = db_query('SELECT c.title, c.cid FROM {aggregator_category_item} ci LEFT JOIN {aggregator_category} c ON ci.cid = c.cid WHERE ci.iid = %d ORDER BY c.title', $item->iid);
  $categories = array();
  while ($category = db_fetch_object($result)) {
    $categories[] = l($category->title, 'aggregator/categories/'. $category->cid);
  }
  if ($categories) {
    $output .= '<div class="feed-item-categories">'. t('Categories') .': '. implode(', ', $categories) ."</div>\n";
  }

  $output .= "</div>\n";

  return $output;
}

/**
 * Menu callback; displays all the feeds used by the aggregator.
 */
function aggregator_page_sources() {
  $result = db_query('SELECT f.fid, f.title, f.description, f.image, MAX(i.timestamp) AS last FROM {aggregator_feed} f LEFT JOIN {aggregator_item} i ON f.fid = i.fid GROUP BY f.fid, f.title, f.description, f.image ORDER BY last DESC, f.title');
  $output = "<div id=\"aggregator\">\n";
  while ($feed = db_fetch_object($result)) {
    $output .= '<h2>'. check_plain($feed->title) ."</h2>\n";

    // Most recent items:
    $list = array();
    if (variable_get('aggregator_summary_items', 3)) {
      $items = db_query_range('SELECT i.title, i.timestamp, i.link FROM {aggregator_item} i WHERE i.fid = %d ORDER BY i.timestamp DESC', $feed->fid, 0, variable_get('aggregator_summary_items', 3));
      while ($item = db_fetch_object($items)) {
        $list[] = theme('aggregator_summary_item', $item);
      }
    }
    $output .= theme('item_list', $list);

    $link['sources'] = array(
      'title' => t('More'),
      'href' => 'aggregator/sources/'. $feed->fid
    );

    $output .= '<div class="links">'. theme('links', $link) ."</div>\n";
  }
  $output .= theme('feed_icon', url('aggregator/opml'), t('OPML feed'));
  $output .= '</div>';
  return $output;
}

/**
 * Menu callback; displays all the categories used by the aggregator.
 */
function aggregator_page_categories() {
  $result = db_query('SELECT c.cid, c.title, c.description FROM {aggregator_category} c LEFT JOIN {aggregator_category_item} ci ON c.cid = ci.cid LEFT JOIN {aggregator_item} i ON ci.iid = i.iid GROUP BY c.cid, c.title, c.description');
  $output = "<div id=\"aggregator\">\n";

  while ($category = db_fetch_object($result)) {
    $output .= '<h2>'. check_plain($category->title) ."</h2>\n";
    if (variable_get('aggregator_summary_items', 3)) {
      $list = array();
      $items = db_query_range('SELECT i.title, i.timestamp, i.link, f.title as feed_title, f.link as feed_link FROM {aggregator_category_item} ci LEFT JOIN {aggregator_item} i ON i.iid = ci.iid LEFT JOIN {aggregator_feed} f ON i.fid = f.fid WHERE ci.cid = %d ORDER BY i.timestamp DESC', $category->cid, 0, variable_get('aggregator_summary_items', 3));
      while ($item = db_fetch_object($items)) {
        $list[] = theme('aggregator_summary_item', $item);
      }
      $output .= theme('item_list', $list);
    }

    $link['categories'] = array(
      'title' => t('More'),
      'href' => 'aggregator/categories/'. $category->cid
    );

    $output .= '<div class="links">'. theme('links', $link) ."</div>\n";
  }

  $output .= '</div>';

  return $output;
}

/**
 * Menu callback; generate an RSS 0.92 feed of aggregator items or categories.
 */
function aggregator_page_rss() {
  // arg(2) is the passed cid, only select for that category
  $result = NULL;
  if (arg(2)) {
    $category = db_fetch_object(db_query('SELECT cid, title FROM {aggregator_category} WHERE cid = %d', arg(2)));
    $url = '/categories/'. $category->cid;
    $title = ' '. t('in category') .' '. $category->title;
    $sql = 'SELECT i.*, f.title AS ftitle, f.link AS flink FROM {aggregator_category_item} c LEFT JOIN {aggregator_item} i ON c.iid = i.iid LEFT JOIN {aggregator_feed} f ON i.fid = f.fid WHERE cid = %d ORDER BY timestamp DESC, i.iid DESC';
    $result = db_query_range($sql, $category->cid, 0, variable_get('feed_default_items', 10));
  }
  // or, get the default aggregator items
  else {
    $sql = 'SELECT i.*, f.title AS ftitle, f.link AS flink FROM {aggregator_item} i INNER JOIN {aggregator_feed} f ON i.fid = f.fid ORDER BY i.timestamp DESC, i.iid DESC';
    $result = db_query_range($sql, 0, variable_get('feed_default_items', 10));
  }

  while ($item = db_fetch_object($result)) {
    switch (variable_get('feed_item_length', 'teaser')) {
      case 'teaser':
        $teaser = node_teaser($item->description);
        if ($teaser != $item->description) {
          $teaser .= '<p><a href="'. check_url($item->link) .'">'. t('read more') ."</a></p>\n";
        }
        $item->description = $teaser;
        break;
      case 'title':
        $item->description = '';
        break;
    }
    $items .= format_rss_item($item->ftitle .': '. $item->title, $item->link, $item->description, array('pubDate' => date('r', $item->timestamp)));
  }

  $output .= "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
  $output .= "<rss version=\"2.0\">\n";
  $output .= format_rss_channel(variable_get('site_name', 'Drupal') .' '. t('aggregator'), url('aggregator'. $url, array('absolute' => TRUE)), variable_get('site_name', 'Drupal') .' - '. t('aggregated feeds') . $title, $items);
  $output .= "</rss>\n";

  drupal_set_header('Content-Type: application/rss+xml; charset=utf-8');
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

  $output = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
  $output .= "<opml version=\"1.1\">\n";
  $output .= "<head>\n";
  $output .= '<title>'. check_plain(variable_get('site_name', 'Drupal')) ."</title>\n";
  $output .= '<dateModified>'. gmdate('r') ."</dateModified>\n";
  $output .= "</head>\n";
  $output .= "<body>\n";

  while ($feed = db_fetch_object($result)) {
    $output .= '<outline text="'. check_plain($feed->title) .'" xmlUrl="'. check_url($feed->url) ."\" />\n";
  }

  $output .= "</body>\n";
  $output .= "</opml>\n";

  drupal_set_header('Content-Type: text/xml; charset=utf-8');
  print $output;
}

/**
 * Return a themed item heading for summary pages located at "aggregator/sources"
 * and "aggregator/categories".
 *
 * @param $item The item object from the aggregator module.
 * @return A string containing the output.
 *
 * @ingroup themeable
 */
function theme_aggregator_summary_item($item) {
  $output = '<a href="'. check_url($item->link) .'">'. check_plain($item->title) .'</a> <span class="age">'. t('%age old', array('%age' => format_interval(time() - $item->timestamp))) .'</span>';
  if (!empty($item->feed_link)) {
    $output .= ', <span class="source"><a href="'. check_url($item->feed_link) .'">'. check_plain($item->feed_title) .'</a></span>';
  }
  return $output ."\n";
}

/**
 * Format a news feed.
 *
 * @ingroup themeable
 */
function theme_aggregator_feed($feed) {
  $output  = '<div class="feed-source">';
  $output .= theme('feed_icon', $feed->url, t('!title feed', array('!title' => $feed->title))) ."\n";
  $output .= $feed->image;
  $output .= '<div class="feed-description">'. aggregator_filter_xss($feed->description) ."</div>\n";
  $output .= '<div class="feed-url"><em>'. t('URL:') .'</em> '. l($feed->link, $feed->link, array('absolute' => TRUE)) ."</div>\n";

  if ($feed->checked) {
    $updated = t('@time ago', array('@time' => format_interval(time() - $feed->checked)));
  }
  else {
    $updated = t('never');
  }

  if (user_access('administer news feeds')) {
    $updated = l($updated, 'admin/content/aggregator');
  }

  $output .= '<div class="feed-updated"><em>'. t('Updated:') ."</em> $updated</div>";
  $output .= "</div>\n";

  return $output;
}
