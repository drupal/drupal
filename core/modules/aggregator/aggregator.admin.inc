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
  $result = db_query('SELECT f.fid, f.title, f.url, f.refresh, f.checked, f.link, f.description, f.hash, f.etag, f.modified, f.image, f.block, COUNT(i.iid) AS items FROM {aggregator_feed} f LEFT JOIN {aggregator_item} i ON f.fid = i.fid GROUP BY f.fid, f.title, f.url, f.refresh, f.checked, f.link, f.description, f.hash, f.etag, f.modified, f.image, f.block ORDER BY f.title');

  $output = '<h3>' . t('Feed overview') . '</h3>';

  $header = array(t('Title'), t('Items'), t('Last update'), t('Next update'), array('data' => t('Operations'), 'colspan' => '3'));
  $rows = array();
  foreach ($result as $feed) {
    $rows[] = array(
      l($feed->title, "aggregator/sources/$feed->fid"),
      format_plural($feed->items, '1 item', '@count items'),
      ($feed->checked ? t('@time ago', array('@time' => format_interval(REQUEST_TIME - $feed->checked))) : t('never')),
      ($feed->checked && $feed->refresh ? t('%time left', array('%time' => format_interval($feed->checked + $feed->refresh - REQUEST_TIME))) : t('never')),
      l(t('edit'), "admin/config/services/aggregator/edit/feed/$feed->fid"),
      l(t('remove items'), "admin/config/services/aggregator/remove/$feed->fid"),
      l(t('update items'), "admin/config/services/aggregator/update/$feed->fid"),
    );
  }
  $output .= theme('table', array('header' => $header, 'rows' => $rows, 'empty' => t('No feeds available. <a href="@link">Add feed</a>.', array('@link' => url('admin/config/services/aggregator/add/feed')))));

  $result = db_query('SELECT c.cid, c.title, COUNT(ci.iid) as items FROM {aggregator_category} c LEFT JOIN {aggregator_category_item} ci ON c.cid = ci.cid GROUP BY c.cid, c.title ORDER BY title');

  $output .= '<h3>' . t('Category overview') . '</h3>';

  $header = array(t('Title'), t('Items'), t('Operations'));
  $rows = array();
  foreach ($result as $category) {
    $rows[] = array(l($category->title, "aggregator/categories/$category->cid"), format_plural($category->items, '1 item', '@count items'), l(t('edit'), "admin/config/services/aggregator/edit/category/$category->cid"));
  }
  $output .= theme('table', array('header' => $header, 'rows' => $rows, 'empty' => t('No categories available. <a href="@link">Add category</a>.', array('@link' => url('admin/config/services/aggregator/add/category')))));

  return $output;
}

/**
 * Form builder; Generate a form to add/edit feed sources.
 *
 * @ingroup forms
 * @see aggregator_form_feed_validate()
 * @see aggregator_form_feed_submit()
 */
function aggregator_form_feed($form, &$form_state, stdClass $feed = NULL) {
  $period = drupal_map_assoc(array(900, 1800, 3600, 7200, 10800, 21600, 32400, 43200, 64800, 86400, 172800, 259200, 604800, 1209600, 2419200), 'format_interval');
  $period[AGGREGATOR_CLEAR_NEVER] = t('Never');

  $form['title'] = array('#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => isset($feed->title) ? $feed->title : '',
    '#maxlength' => 255,
    '#description' => t('The name of the feed (or the name of the website providing the feed).'),
    '#required' => TRUE,
  );
  $form['url'] = array('#type' => 'textfield',
    '#title' => t('URL'),
    '#default_value' => isset($feed->url) ? $feed->url : '',
    '#maxlength' => 255,
    '#description' => t('The fully-qualified URL of the feed.'),
    '#required' => TRUE,
  );
  $form['refresh'] = array('#type' => 'select',
    '#title' => t('Update interval'),
    '#default_value' => isset($feed->refresh) ? $feed->refresh : 3600,
    '#options' => $period,
    '#description' => t('The length of time between feed updates. Requires a correctly configured <a href="@cron">cron maintenance task</a>.', array('@cron' => url('admin/reports/status'))),
  );
  $form['block'] = array('#type' => 'select',
    '#title' => t('News items in block'),
    '#default_value' => isset($feed->block) ? $feed->block : 5,
    '#options' => drupal_map_assoc(array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20)),
    '#description' => t("Drupal can make a block with the most recent news items of this feed. You can <a href=\"@block-admin\">configure blocks</a> to be displayed in the sidebar of your page. This setting lets you configure the number of news items to show in this feed's block. If you choose '0' this feed's block will be disabled.", array('@block-admin' => url('admin/structure/block'))),
  );

  // Handling of categories.
  $options = array();
  $values = array();
  $categories = db_query('SELECT c.cid, c.title, f.fid FROM {aggregator_category} c LEFT JOIN {aggregator_category_feed} f ON c.cid = f.cid AND f.fid = :fid ORDER BY title', array(':fid' => isset($feed->fid) ? $feed->fid : NULL));
  foreach ($categories as $category) {
    $options[$category->cid] = check_plain($category->title);
    if ($category->fid) $values[] = $category->cid;
  }

  if ($options) {
    $form['category'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Categorize news items'),
      '#default_value' => $values,
      '#options' => $options,
      '#description' => t('New feed items are automatically filed in the checked categories.'),
    );
  }

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );
  if (!empty($feed->fid)) {
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
    );
    $form['fid'] = array(
      '#type' => 'hidden',
      '#value' => $feed->fid,
    );
  }

  return $form;
}

/**
 * Validate aggregator_form_feed() form submissions.
 */
function aggregator_form_feed_validate($form, &$form_state) {
  if ($form_state['values']['op'] == t('Save')) {
    // Ensure URL is valid.
    if (!valid_url($form_state['values']['url'], TRUE)) {
      form_set_error('url', t('The URL %url is invalid. Enter a fully-qualified URL, such as http://www.example.com/feed.xml.', array('%url' => $form_state['values']['url'])));
    }
    // Check for duplicate titles.
    if (isset($form_state['values']['fid'])) {
      $result = db_query("SELECT title, url FROM {aggregator_feed} WHERE (title = :title OR url = :url) AND fid <> :fid", array(':title' => $form_state['values']['title'], ':url' => $form_state['values']['url'], ':fid' => $form_state['values']['fid']));
    }
    else {
      $result = db_query("SELECT title, url FROM {aggregator_feed} WHERE title = :title OR url = :url", array(':title' => $form_state['values']['title'], ':url' => $form_state['values']['url']));
    }
    foreach ($result as $feed) {
      if (strcasecmp($feed->title, $form_state['values']['title']) == 0) {
        form_set_error('title', t('A feed named %feed already exists. Enter a unique title.', array('%feed' => $form_state['values']['title'])));
      }
      if (strcasecmp($feed->url, $form_state['values']['url']) == 0) {
        form_set_error('url', t('A feed with this URL %url already exists. Enter a unique URL.', array('%url' => $form_state['values']['url'])));
      }
    }
  }
}

/**
 * Process aggregator_form_feed() form submissions.
 *
 * @todo Add delete confirmation dialog.
 */
function aggregator_form_feed_submit($form, &$form_state) {
  if ($form_state['values']['op'] == t('Delete')) {
    $title = $form_state['values']['title'];
    // Unset the title.
    unset($form_state['values']['title']);
  }
  aggregator_save_feed($form_state['values']);
  if (isset($form_state['values']['fid'])) {
    if (isset($form_state['values']['title'])) {
      drupal_set_message(t('The feed %feed has been updated.', array('%feed' => $form_state['values']['title'])));
      if (arg(0) == 'admin') {
        $form_state['redirect'] = 'admin/config/services/aggregator/';
        return;
      }
      else {
        $form_state['redirect'] = 'aggregator/sources/' . $form_state['values']['fid'];
        return;
      }
    }
    else {
      watchdog('aggregator', 'Feed %feed deleted.', array('%feed' => $title));
      drupal_set_message(t('The feed %feed has been deleted.', array('%feed' => $title)));
      if (arg(0) == 'admin') {
        $form_state['redirect'] = 'admin/config/services/aggregator/';
        return;
      }
      else {
        $form_state['redirect'] = 'aggregator/sources/';
        return;
      }
    }
  }
  else {
    watchdog('aggregator', 'Feed %feed added.', array('%feed' => $form_state['values']['title']), WATCHDOG_NOTICE, l(t('view'), 'admin/config/services/aggregator'));
    drupal_set_message(t('The feed %feed has been added.', array('%feed' => $form_state['values']['title'])));
  }
}

function aggregator_admin_remove_feed($form, $form_state, $feed) {
  return confirm_form(
    array(
      'feed' => array(
        '#type' => 'value',
        '#value' => $feed,
      ),
    ),
    t('Are you sure you want to remove all items from the feed %feed?', array('%feed' => $feed->title)),
    'admin/config/services/aggregator',
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
  $form_state['redirect'] = 'admin/config/services/aggregator';
}

/**
 * Form builder; Generate a form to import feeds from OPML.
 *
 * @ingroup forms
 * @see aggregator_form_opml_validate()
 * @see aggregator_form_opml_submit()
 */
function aggregator_form_opml($form, &$form_state) {
  $period = drupal_map_assoc(array(900, 1800, 3600, 7200, 10800, 21600, 32400, 43200, 64800, 86400, 172800, 259200, 604800, 1209600, 2419200), 'format_interval');

  $form['upload'] = array(
    '#type' => 'file',
    '#title' => t('OPML File'),
    '#description' => t('Upload an OPML file containing a list of feeds to be imported.'),
  );
  $form['remote'] = array(
    '#type' => 'textfield',
    '#title' => t('OPML Remote URL'),
    '#maxlength' => 1024,
    '#description' => t('Enter the URL of an OPML file. This file will be downloaded and processed only once on submission of the form.'),
  );
  $form['refresh'] = array(
    '#type' => 'select',
    '#title' => t('Update interval'),
    '#default_value' => 3600,
    '#options' => $period,
    '#description' => t('The length of time between feed updates. Requires a correctly configured <a href="@cron">cron maintenance task</a>.', array('@cron' => url('admin/reports/status'))),
  );
  $form['block'] = array('#type' => 'select',
    '#title' => t('News items in block'),
    '#default_value' => 5,
    '#options' => drupal_map_assoc(array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20)),
    '#description' => t("Drupal can make a block with the most recent news items of a feed. You can <a href=\"@block-admin\">configure blocks</a> to be displayed in the sidebar of your page. This setting lets you configure the number of news items to show in a feed's block. If you choose '0' these feeds' blocks will be disabled.", array('@block-admin' => url('admin/structure/block'))),
  );

  // Handling of categories.
  $options = array_map('check_plain', db_query("SELECT cid, title FROM {aggregator_category} ORDER BY title")->fetchAllKeyed());
  if ($options) {
    $form['category'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Categorize news items'),
      '#options' => $options,
      '#description' => t('New feed items are automatically filed in the checked categories.'),
    );
  }
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Import')
  );

  return $form;
}

/**
 * Validate aggregator_form_opml form submissions.
 */
function aggregator_form_opml_validate($form, &$form_state) {
  // If both fields are empty or filled, cancel.
  if (empty($form_state['values']['remote']) == empty($_FILES['files']['name']['upload'])) {
    form_set_error('remote', t('You must <em>either</em> upload a file or enter a URL.'));
  }

  // Validate the URL, if one was entered.
  if (!empty($form_state['values']['remote']) && !valid_url($form_state['values']['remote'], TRUE)) {
    form_set_error('remote', t('This URL is not valid.'));
  }
}

/**
 * Process aggregator_form_opml form submissions.
 */
function aggregator_form_opml_submit($form, &$form_state) {
  $data = '';
  $validators = array('file_validate_extensions' => array('opml xml'));
  if ($file = file_save_upload('upload', $validators)) {
    $data = file_get_contents($file->uri);
  }
  else {
    $response = drupal_http_request($form_state['values']['remote']);
    if (!isset($response->error)) {
      $data = $response->data;
    }
  }

  $feeds = _aggregator_parse_opml($data);
  if (empty($feeds)) {
    drupal_set_message(t('No new feed has been added.'));
    return;
  }

  $form_state['values']['op'] = t('Save');

  foreach ($feeds as $feed) {
    // Ensure URL is valid.
    if (!valid_url($feed['url'], TRUE)) {
      drupal_set_message(t('The URL %url is invalid.', array('%url' => $feed['url'])), 'warning');
      continue;
    }

    // Check for duplicate titles or URLs.
    $result = db_query("SELECT title, url FROM {aggregator_feed} WHERE title = :title OR url = :url", array(':title' => $feed['title'], ':url' => $feed['url']));
    foreach ($result as $old) {
      if (strcasecmp($old->title, $feed['title']) == 0) {
        drupal_set_message(t('A feed named %title already exists.', array('%title' => $old->title)), 'warning');
        continue 2;
      }
      if (strcasecmp($old->url, $feed['url']) == 0) {
        drupal_set_message(t('A feed with the URL %url already exists.', array('%url' => $old->url)), 'warning');
        continue 2;
      }
    }

    $form_state['values']['title'] = $feed['title'];
    $form_state['values']['url'] = $feed['url'];
    drupal_form_submit('aggregator_form_feed', $form_state);
  }

  $form_state['redirect'] = 'admin/config/services/aggregator';
}

/**
 * Parse an OPML file.
 *
 * Feeds are recognized as <outline> elements with the attributes "text" and
 * "xmlurl" set.
 *
 * @param $opml
 *   The complete contents of an OPML document.
 *
 * @return
 *   An array of feeds, each an associative array with a "title" and a "url"
 *   element, or NULL if the OPML document failed to be parsed. An empty
 *   array will be returned if the document is valid but contains no feeds, as
 *   some OPML documents do.
 */
function _aggregator_parse_opml($opml) {
  $feeds = array();
  $xml_parser = drupal_xml_parser_create($opml);
  if (xml_parse_into_struct($xml_parser, $opml, $values)) {
    foreach ($values as $entry) {
      if ($entry['tag'] == 'OUTLINE' && isset($entry['attributes'])) {
        $item = $entry['attributes'];
        if (!empty($item['XMLURL']) && !empty($item['TEXT'])) {
          $feeds[] = array('title' => $item['TEXT'], 'url' => $item['XMLURL']);
        }
      }
    }
  }
  xml_parser_free($xml_parser);

  return $feeds;
}

/**
 * Menu callback; refreshes a feed, then redirects to the overview page.
 *
 * @param $feed
 *   An object describing the feed to be refreshed.
 */
function aggregator_admin_refresh_feed($feed) {
  aggregator_refresh($feed);
  drupal_goto('admin/config/services/aggregator');
}

/**
 * Form builder; Configure the aggregator system.
 *
 * @ingroup forms
 */
function aggregator_admin_form($form, $form_state) {
  // Global aggregator settings.
  $form['aggregator_allowed_html_tags'] = array(
    '#type' => 'textfield',
    '#title' => t('Allowed HTML tags'),
    '#size' => 80,
    '#maxlength' => 255,
    '#default_value' => variable_get('aggregator_allowed_html_tags', '<a> <b> <br> <dd> <dl> <dt> <em> <i> <li> <ol> <p> <strong> <u> <ul>'),
    '#description' => t('A space-separated list of HTML tags allowed in the content of feed items. Disallowed tags are stripped from the content.'),
  );

  // Make sure configuration is sane.
  aggregator_sanitize_configuration();

  // Get all available fetchers.
  $fetchers = module_implements('aggregator_fetch');
  foreach ($fetchers as $k => $module) {
    if ($info = module_invoke($module, 'aggregator_fetch_info')) {
      $label = $info['title'] . ' <span class="description">' . $info['description'] . '</span>';
    }
    else {
      $label = $module;
    }
    unset($fetchers[$k]);
    $fetchers[$module] = $label;
  }

  // Get all available parsers.
  $parsers = module_implements('aggregator_parse');
  foreach ($parsers as $k => $module) {
    if ($info = module_invoke($module, 'aggregator_parse_info')) {
      $label = $info['title'] . ' <span class="description">' . $info['description'] . '</span>';
    }
    else {
      $label = $module;
    }
    unset($parsers[$k]);
    $parsers[$module] = $label;
  }

  // Get all available processors.
  $processors = module_implements('aggregator_process');
  foreach ($processors as $k => $module) {
    if ($info = module_invoke($module, 'aggregator_process_info')) {
      $label = $info['title'] . ' <span class="description">' . $info['description'] . '</span>';
    }
    else {
      $label = $module;
    }
    unset($processors[$k]);
    $processors[$module] = $label;
  }

  // Only show basic configuration if there are actually options.
  $basic_conf = array();
  if (count($fetchers) > 1) {
    $basic_conf['aggregator_fetcher'] = array(
      '#type' => 'radios',
      '#title' => t('Fetcher'),
      '#description' => t('Fetchers download data from an external source. Choose a fetcher suitable for the external source you would like to download from.'),
      '#options' => $fetchers,
      '#default_value' => variable_get('aggregator_fetcher', 'aggregator'),
    );
  }
  if (count($parsers) > 1) {
    $basic_conf['aggregator_parser'] = array(
      '#type' => 'radios',
      '#title' => t('Parser'),
      '#description' => t('Parsers transform downloaded data into standard structures. Choose a parser suitable for the type of feeds you would like to aggregate.'),
      '#options' => $parsers,
      '#default_value' => variable_get('aggregator_parser', 'aggregator'),
    );
  }
  if (count($processors) > 1) {
    $basic_conf['aggregator_processors'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Processors'),
      '#description' => t('Processors act on parsed feed data, for example they store feed items. Choose the processors suitable for your task.'),
      '#options' => $processors,
      '#default_value' => variable_get('aggregator_processors', array('aggregator')),
    );
  }
  if (count($basic_conf)) {
    $form['basic_conf'] = array(
      '#type' => 'fieldset',
      '#title' => t('Basic configuration'),
      '#description' => t('For most aggregation tasks, the default settings are fine.'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      );
    $form['basic_conf'] += $basic_conf;
  }

  // Implementing modules will expect an array at $form['modules'].
  $form['modules'] = array();

  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );

  return $form;
}

function aggregator_admin_form_submit($form, &$form_state) {
  if (isset($form_state['values']['aggregator_processors'])) {
    $form_state['values']['aggregator_processors'] = array_filter($form_state['values']['aggregator_processors']);
  }
  system_settings_form_submit($form, $form_state);
}

/**
 * Form builder; Generate a form to add/edit/delete aggregator categories.
 *
 * @ingroup forms
 * @see aggregator_form_category_validate()
 * @see aggregator_form_category_submit()
 */
function aggregator_form_category($form, &$form_state, $edit = array('title' => '', 'description' => '', 'cid' => NULL)) {
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
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save'));
  if ($edit['cid']) {
    $form['actions']['delete'] = array('#type' => 'submit', '#value' => t('Delete'));
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
      $category = db_query("SELECT cid FROM {aggregator_category} WHERE title = :title AND cid <> :cid", array(':title' => $form_state['values']['title'], ':cid' => $form_state['values']['cid']))->fetchObject();
    }
    else {
      $category = db_query("SELECT cid FROM {aggregator_category} WHERE title = :title", array(':title' => $form_state['values']['title']))->fetchObject();
    }
    if ($category) {
      form_set_error('title', t('A category named %category already exists. Enter a unique title.', array('%category' => $form_state['values']['title'])));
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
    // Unset the title.
    unset($form_state['values']['title']);
  }
  aggregator_save_category($form_state['values']);
  if (isset($form_state['values']['cid'])) {
    if (isset($form_state['values']['title'])) {
      drupal_set_message(t('The category %category has been updated.', array('%category' => $form_state['values']['title'])));
      if (arg(0) == 'admin') {
        $form_state['redirect'] = 'admin/config/services/aggregator/';
        return;
      }
      else {
        $form_state['redirect'] = 'aggregator/categories/' . $form_state['values']['cid'];
        return;
      }
    }
    else {
      watchdog('aggregator', 'Category %category deleted.', array('%category' => $title));
      drupal_set_message(t('The category %category has been deleted.', array('%category' => $title)));
      if (arg(0) == 'admin') {
        $form_state['redirect'] = 'admin/config/services/aggregator/';
        return;
      }
      else {
        $form_state['redirect'] = 'aggregator/categories/';
        return;
      }
    }
  }
  else {
    watchdog('aggregator', 'Category %category added.', array('%category' => $form_state['values']['title']), WATCHDOG_NOTICE, l(t('view'), 'admin/config/services/aggregator'));
    drupal_set_message(t('The category %category has been added.', array('%category' => $form_state['values']['title'])));
  }
}
