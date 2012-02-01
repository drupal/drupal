<?php

/**
 * @file
 * Admin page callbacks for the aggregator module.
 */

/**
 * Menu callback; displays the aggregator administration page.
 */
function aggregator_admin_overview() {
  return aggregator_view();
}

/**
 * Displays the aggregator administration page.
 *
 * @return
 *   The page HTML.
 */
function aggregator_view() {
  $result = db_query('SELECT f.*, COUNT(i.iid) AS items FROM {aggregator_feed} f LEFT JOIN {aggregator_item} i ON f.fid = i.fid GROUP BY f.fid, f.title, f.url, f.refresh, f.checked, f.link, f.description, f.etag, f.modified, f.image, f.block ORDER BY f.title');

  $output = '<h3>'. t('Feed overview') .'</h3>';

  $header = array(t('Title'), t('Items'), t('Last update'), t('Next update'), array('data' => t('Operations'), 'colspan' => '3'));
  $rows = array();
  while ($feed = db_fetch_object($result)) {
    $rows[] = array(
      l($feed->title, "aggregator/sources/$feed->fid"),
      format_plural($feed->items, '1 item', '@count items'),
      ($feed->checked ? t('@time ago', array('@time' => format_interval(time() - $feed->checked))) : t('never')),
      ($feed->checked ? t('%time left', array('%time' => format_interval($feed->checked + $feed->refresh - time()))) : t('never')),
      l(t('edit'), "admin/content/aggregator/edit/feed/$feed->fid"),
      l(t('remove items'), "admin/content/aggregator/remove/$feed->fid"),
      l(t('update items'), "admin/content/aggregator/update/$feed->fid", array('query' => array('token' => drupal_get_token("aggregator/update/$feed->fid")))),
    );
  }
  $output .= theme('table', $header, $rows);

  $result = db_query('SELECT c.cid, c.title, count(ci.iid) as items FROM {aggregator_category} c LEFT JOIN {aggregator_category_item} ci ON c.cid = ci.cid GROUP BY c.cid, c.title ORDER BY title');

  $output .= '<h3>'. t('Category overview') .'</h3>';

  $header = array(t('Title'), t('Items'), t('Operations'));
  $rows = array();
  while ($category = db_fetch_object($result)) {
    $rows[] = array(l($category->title, "aggregator/categories/$category->cid"), format_plural($category->items, '1 item', '@count items'), l(t('edit'), "admin/content/aggregator/edit/category/$category->cid"));
  }
  $output .= theme('table', $header, $rows);

  return $output;
}

/**
 * Form builder; Generate a form to add/edit feed sources.
 *
 * @ingroup forms
 * @see aggregator_form_feed_validate()
 * @see aggregator_form_feed_submit()
 */
function aggregator_form_feed(&$form_state, $edit = array('refresh' => 900, 'title' => '', 'url' => '', 'fid' => NULL)) {
  $period = drupal_map_assoc(array(900, 1800, 3600, 7200, 10800, 21600, 32400, 43200, 64800, 86400, 172800, 259200, 604800, 1209600, 2419200), 'format_interval');

  if ($edit['refresh'] == '') {
    $edit['refresh'] = 3600;
  }

  $form['title'] = array('#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => $edit['title'],
    '#maxlength' => 255,
    '#description' => t('The name of the feed (or the name of the website providing the feed).'),
    '#required' => TRUE,
  );
  $form['url'] = array('#type' => 'textfield',
    '#title' => t('URL'),
    '#default_value' => $edit['url'],
    '#maxlength' => 255,
    '#description' => t('The fully-qualified URL of the feed.'),
    '#required' => TRUE,
  );
  $form['refresh'] = array('#type' => 'select',
    '#title' => t('Update interval'),
    '#default_value' => $edit['refresh'],
    '#options' => $period,
    '#description' => t('The length of time between feed updates. (Requires a correctly configured <a href="@cron">cron maintenance task</a>.)', array('@cron' => url('admin/reports/status'))),
  );

  // Handling of categories:
  $options = array();
  $values = array();
  $categories = db_query('SELECT c.cid, c.title, f.fid FROM {aggregator_category} c LEFT JOIN {aggregator_category_feed} f ON c.cid = f.cid AND f.fid = %d ORDER BY title', $edit['fid']);
  while ($category = db_fetch_object($categories)) {
    $options[$category->cid] = check_plain($category->title);
    if ($category->fid) $values[] = $category->cid;
  }
  if ($options) {
    $form['category'] = array('#type' => 'checkboxes',
      '#title' => t('Categorize news items'),
      '#default_value' => $values,
      '#options' => $options,
      '#description' => t('New feed items are automatically filed in the checked categories.'),
    );
  }
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save'));

  if ($edit['fid']) {
    $form['delete'] = array('#type' => 'submit', '#value' => t('Delete'));
    $form['fid'] = array('#type' => 'hidden', '#value' => $edit['fid']);
  }

  return $form;
}

/**
 * Validate aggregator_form_feed form submissions.
 */
function aggregator_form_feed_validate($form, &$form_state) {
  if ($form_state['values']['op'] == t('Save')) {
    // Ensure URL is valid.
    if (!valid_url($form_state['values']['url'], TRUE)) {
      form_set_error('url', t('The URL %url is invalid. Please enter a fully-qualified URL, such as http://www.example.com/feed.xml.', array('%url' => $form_state['values']['url'])));
    }
    // Check for duplicate titles.
    if (isset($form_state['values']['fid'])) {
      $result = db_query("SELECT title, url FROM {aggregator_feed} WHERE (title = '%s' OR url = '%s') AND fid <> %d", $form_state['values']['title'], $form_state['values']['url'], $form_state['values']['fid']);
    }
    else {
      $result = db_query("SELECT title, url FROM {aggregator_feed} WHERE title = '%s' OR url = '%s'", $form_state['values']['title'], $form_state['values']['url']);
    }
    while ($feed = db_fetch_object($result)) {
      if (strcasecmp($feed->title, $form_state['values']['title']) == 0) {
        form_set_error('title', t('A feed named %feed already exists. Please enter a unique title.', array('%feed' => $form_state['values']['title'])));
      }
      if (strcasecmp($feed->url, $form_state['values']['url']) == 0) {
        form_set_error('url', t('A feed with this URL %url already exists. Please enter a unique URL.', array('%url' => $form_state['values']['url'])));
      }
    }
  }
}

/**
 * Process aggregator_form_feed form submissions.
 *
 * @todo Add delete confirmation dialog.
 */
function aggregator_form_feed_submit($form, &$form_state) {
  if ($form_state['values']['op'] == t('Delete')) {
    $title = $form_state['values']['title'];
    // Unset the title:
    unset($form_state['values']['title']);
  }
  aggregator_save_feed($form_state['values']);
  if (isset($form_state['values']['fid'])) {
    if (isset($form_state['values']['title'])) {
      drupal_set_message(t('The feed %feed has been updated.', array('%feed' => $form_state['values']['title'])));
      if (arg(0) == 'admin') {
        $form_state['redirect'] = 'admin/content/aggregator/';
        return;
      }
      else {
        $form_state['redirect'] = 'aggregator/sources/'. $form_state['values']['fid'];
        return;
      }
    }
    else {
      watchdog('aggregator', 'Feed %feed deleted.', array('%feed' => $title));
      drupal_set_message(t('The feed %feed has been deleted.', array('%feed' => $title)));
      if (arg(0) == 'admin') {
        $form_state['redirect'] = 'admin/content/aggregator/';
        return;
      }
      else {
        $form_state['redirect'] = 'aggregator/sources/';
        return;
      }
    }
  }
  else {
    watchdog('aggregator', 'Feed %feed added.', array('%feed' => $form_state['values']['title']), WATCHDOG_NOTICE, l(t('view'), 'admin/content/aggregator'));
    drupal_set_message(t('The feed %feed has been added.', array('%feed' => $form_state['values']['title'])));
  }
}

function aggregator_admin_remove_feed($form_state, $feed) {
  return confirm_form(
    array(
      'feed' => array(
        '#type' => 'value',
        '#value' => $feed,
      ),
    ),
    t('Are you sure you want to remove all items from the feed %feed?', array('%feed' => $feed['title'])),
    'admin/content/aggregator',
    t('This action cannot be undone.'),
    t('Remove items'),
    t('Cancel')
  );
}

/**
 * Remove all items from a feed and redirect to the overview page.
 *
 * @param $feed
 *   An associative array describing the feed to be cleared.
 */
function aggregator_admin_remove_feed_submit($form, &$form_state) {
  aggregator_remove($form_state['values']['feed']);
  $form_state['redirect'] = 'admin/content/aggregator';
}

/**
 * Menu callback; refreshes a feed, then redirects to the overview page.
 *
 * @param $feed
 *   An associative array describing the feed to be refreshed.
 */
function aggregator_admin_refresh_feed($feed) {
  if (!isset($_GET['token']) || !drupal_valid_token($_GET['token'], 'aggregator/update/' . $feed['fid'])) {
    return drupal_access_denied();
  }
  aggregator_refresh($feed);
  drupal_goto('admin/content/aggregator');
}

/**
 * Form builder; Configure the aggregator system.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function aggregator_admin_settings() {
  $items = array(0 => t('none')) + drupal_map_assoc(array(3, 5, 10, 15, 20, 25), '_aggregator_items');
  $period = drupal_map_assoc(array(3600, 10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800, 1209600, 2419200, 4838400, 9676800), 'format_interval');

  $form['aggregator_allowed_html_tags'] = array(
    '#type' => 'textfield', '#title' => t('Allowed HTML tags'), '#size' => 80, '#maxlength' => 255,
    '#default_value' => variable_get('aggregator_allowed_html_tags', '<a> <b> <br> <dd> <dl> <dt> <em> <i> <li> <ol> <p> <strong> <u> <ul>'),
    '#description' => t('A space-separated list of HTML tags allowed in the content of feed items. (Tags in this list are not removed by Drupal.)')
  );

  $form['aggregator_summary_items'] = array(
    '#type' => 'select', '#title' => t('Items shown in sources and categories pages') ,
    '#default_value' => variable_get('aggregator_summary_items', 3), '#options' => $items,
    '#description' => t('Number of feed items displayed in feed and category summary pages.')
  );

  $form['aggregator_clear'] = array(
    '#type' => 'select', '#title' => t('Discard items older than'),
    '#default_value' => variable_get('aggregator_clear', 9676800), '#options' => $period,
    '#description' => t('The length of time to retain feed items before discarding. (Requires a correctly configured <a href="@cron">cron maintenance task</a>.)', array('@cron' => url('admin/reports/status')))
  );

  $form['aggregator_category_selector'] = array(
    '#type' => 'radios', '#title' => t('Category selection type'), '#default_value' => variable_get('aggregator_category_selector', 'checkboxes'),
    '#options' => array('checkboxes' => t('checkboxes'), 'select' => t('multiple selector')),
    '#description' => t('The type of category selection widget displayed on categorization pages. (For a small number of categories, checkboxes are easier to use, while a multiple selector work well with large numbers of categories.)')
  );

  return system_settings_form($form);
}

/**
 * Form builder; Generate a form to add/edit/delete aggregator categories.
 *
 * @ingroup forms
 * @see aggregator_form_category_validate()
 * @see aggregator_form_category_submit()
 */
function aggregator_form_category(&$form_state, $edit = array('title' => '', 'description' => '', 'cid' => NULL)) {
  $form['title'] = array('#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => $edit['title'],
    '#maxlength' => 64,
    '#required' => TRUE,
  );
  $form['description'] = array('#type' => 'textarea',
    '#title' => t('Description'),
    '#default_value' => $edit['description'],
  );
  $form['submit'] = array('#type' => 'submit', '#value' => t('Save'));

  if ($edit['cid']) {
    $form['delete'] = array('#type' => 'submit', '#value' => t('Delete'));
    $form['cid'] = array('#type' => 'hidden', '#value' => $edit['cid']);
  }

  return $form;
}

/**
 * Validate aggregator_form_feed form submissions.
 */
function aggregator_form_category_validate($form, &$form_state) {
  if ($form_state['values']['op'] == t('Save')) {
    // Check for duplicate titles
    if (isset($form_state['values']['cid'])) {
      $category = db_fetch_object(db_query("SELECT cid FROM {aggregator_category} WHERE title = '%s' AND cid <> %d", $form_state['values']['title'], $form_state['values']['cid']));
    }
    else {
      $category = db_fetch_object(db_query("SELECT cid FROM {aggregator_category} WHERE title = '%s'", $form_state['values']['title']));
    }
    if ($category) {
      form_set_error('title', t('A category named %category already exists. Please enter a unique title.', array('%category' => $form_state['values']['title'])));
    }
  }
}

/**
 * Process aggregator_form_category form submissions.
 *
 * @todo Add delete confirmation dialog.
 */
function aggregator_form_category_submit($form, &$form_state) {
  if ($form_state['values']['op'] == t('Delete')) {
    $title = $form_state['values']['title'];
    // Unset the title:
    unset($form_state['values']['title']);
  }
  aggregator_save_category($form_state['values']);
  if (isset($form_state['values']['cid'])) {
    if (isset($form_state['values']['title'])) {
      drupal_set_message(t('The category %category has been updated.', array('%category' => $form_state['values']['title'])));
      if (arg(0) == 'admin') {
        $form_state['redirect'] = 'admin/content/aggregator/';
        return;
      }
      else {
        $form_state['redirect'] = 'aggregator/categories/'. $form_state['values']['cid'];
        return;
      }
    }
    else {
      watchdog('aggregator', 'Category %category deleted.', array('%category' => $title));
      drupal_set_message(t('The category %category has been deleted.', array('%category' => $title)));
      if (arg(0) == 'admin') {
        $form_state['redirect'] = 'admin/content/aggregator/';
        return;
      }
      else {
        $form_state['redirect'] = 'aggregator/categories/';
        return;
      }
    }
  }
  else {
    watchdog('aggregator', 'Category %category added.', array('%category' => $form_state['values']['title']), WATCHDOG_NOTICE, l(t('view'), 'admin/content/aggregator'));
    drupal_set_message(t('The category %category has been added.', array('%category' => $form_state['values']['title'])));
  }
}
