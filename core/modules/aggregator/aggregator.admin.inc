<?php

/**
 * @file
 * Administration page callbacks for the Aggregator module.
 */

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\aggregator\Plugin\Core\Entity\Feed;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\Exception\BadResponseException;

/**
 * Page callback: Displays the aggregator administration page.
 *
 * @return string
 *   A HTML-formatted string with administration page content.
 *
 * @see aggregator_menu()
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

  $header = array(t('Title'), t('Items'), t('Last update'), t('Next update'), t('Operations'));
  $rows = array();
  foreach ($result as $feed) {
    $row = array();
    $row[] = l($feed->title, "aggregator/sources/$feed->fid");
    $row[] = format_plural($feed->items, '1 item', '@count items');
    $row[] = ($feed->checked ? t('@time ago', array('@time' => format_interval(REQUEST_TIME - $feed->checked))) : t('never'));
    $row[] = ($feed->checked && $feed->refresh ? t('%time left', array('%time' => format_interval($feed->checked + $feed->refresh - REQUEST_TIME))) : t('never'));
    $links = array();
    $links['edit'] = array(
      'title' => t('Edit'),
      'href' => "admin/config/services/aggregator/edit/feed/$feed->fid",
    );
    $links['delete'] = array(
      'title' => t('Delete'),
      'href' => "admin/config/services/aggregator/delete/feed/$feed->fid",
    );
    $links['remove'] = array(
      'title' => t('Remove items'),
      'href' => "admin/config/services/aggregator/remove/$feed->fid",
    );
    $links['update'] = array(
      'title' => t('Update items'),
      'href' => "admin/config/services/aggregator/update/$feed->fid",
      'query' => array('token' => drupal_get_token("aggregator/update/$feed->fid")),
    );
    $row[] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $links,
      ),
    );
    $rows[] = $row;
  }
  $output .= theme('table', array('header' => $header, 'rows' => $rows, 'empty' => t('No feeds available. <a href="@link">Add feed</a>.', array('@link' => url('admin/config/services/aggregator/add/feed')))));

  $result = db_query('SELECT c.cid, c.title, COUNT(ci.iid) as items FROM {aggregator_category} c LEFT JOIN {aggregator_category_item} ci ON c.cid = ci.cid GROUP BY c.cid, c.title ORDER BY title');

  $output .= '<h3>' . t('Category overview') . '</h3>';

  $header = array(t('Title'), t('Items'), t('Operations'));
  $rows = array();
  foreach ($result as $category) {
    $row = array();
    $row[] = l($category->title, "aggregator/categories/$category->cid");
    $row[] = format_plural($category->items, '1 item', '@count items');
    $links = array();
    $links['edit'] = array(
      'title' => t('Edit'),
      'href' => "admin/config/services/aggregator/edit/category/$category->cid",
    );
    $row[] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $links,
      ),
    );
    $rows[] = $row;
  }
  $output .= theme('table', array('header' => $header, 'rows' => $rows, 'empty' => t('No categories available. <a href="@link">Add category</a>.', array('@link' => url('admin/config/services/aggregator/add/category')))));

  return $output;
}

/**
 * Form constructor for importing feeds from OPML.
 *
 * @ingroup forms
 * @see aggregator_menu()
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
    '#type' => 'url',
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
 * Form validation handler for aggregator_form_opml().
 *
 * @see aggregator_form_opml_submit()
 */
function aggregator_form_opml_validate($form, &$form_state) {
  // If both fields are empty or filled, cancel.
  if (empty($form_state['values']['remote']) == empty($_FILES['files']['name']['upload'])) {
    form_set_error('remote', t('You must <em>either</em> upload a file or enter a URL.'));
  }
}

/**
 * Form submission handler for aggregator_form_opml().
 *
 * @see aggregator_form_opml_validate()
 */
function aggregator_form_opml_submit($form, &$form_state) {
  $data = '';
  $validators = array('file_validate_extensions' => array('opml xml'));
  if ($file = file_save_upload('upload', $validators, FALSE, 0)) {
    $data = file_get_contents($file->uri);
  }
  else {
    try {
      $response = Drupal::httpClient()
        ->get($form_state['values']['remote'])
        ->send();
      $data = $response->getBody(TRUE);
    }
    catch (BadResponseException $e) {
      $response = $e->getResponse();
      watchdog('aggregator', 'Failed to download OPML file due to "%error".', array('%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase()), WATCHDOG_WARNING);
      drupal_set_message(t('Failed to download OPML file due to "%error".', array('%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase())));
      return;
    }
    catch (RequestException $e) {
      watchdog('aggregator', 'Failed to download OPML file due to "%error".', array('%error' => $e->getMessage()), WATCHDOG_WARNING);
      drupal_set_message(t('Failed to download OPML file due to "%error".', array('%error' => $e->getMessage())));
      return;
    }
  }

  $feeds = _aggregator_parse_opml($data);
  if (empty($feeds)) {
    drupal_set_message(t('No new feed has been added.'));
    return;
  }

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

    $new_feed = entity_create('aggregator_feed', array(
      'title' => $feed['title'],
      'url' => $feed['url'],
      'refresh' => $form_state['values']['refresh'],
      'block' => $form_state['values']['block'],
    ));
    $new_feed->categories = $form_state['values']['category'];
    $new_feed->save();
  }

  $form_state['redirect'] = 'admin/config/services/aggregator';
}

/**
 * Parses an OPML file.
 *
 * Feeds are recognized as <outline> elements with the attributes "text" and
 * "xmlurl" set.
 *
 * @param $opml
 *   The complete contents of an OPML document.
 *
 * @return
 *   An array of feeds, each an associative array with a "title" and a "url"
 *   element, or NULL if the OPML document failed to be parsed. An empty array
 *   will be returned if the document is valid but contains no feeds, as some
 *   OPML documents do.
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
 * Page callback: Refreshes a feed, then redirects to the overview page.
 *
 * @param \Drupal\aggregator\Plugin\Core\Entity\Feed $feed
 *   An object describing the feed to be refreshed.
 *
 * @see aggregator_menu()
 */
function aggregator_admin_refresh_feed(Feed $feed) {
  // @todo CSRF tokens are validated in page callbacks rather than access
  //   callbacks, because access callbacks are also invoked during menu link
  //   generation. Add token support to routing: http://drupal.org/node/755584.
  $token = drupal_container()->get('request')->query->get('token');
  if (!isset($token) || !drupal_valid_token($token, 'aggregator/update/' . $feed->id())) {
    throw new AccessDeniedHttpException();
  }

  aggregator_refresh($feed);
  drupal_goto('admin/config/services/aggregator');
}

/**
 * Form constructor to add/edit/delete aggregator categories.
 *
 * @param $edit
 *   An object containing:
 *   - title: A string to use for the category title.
 *   - description: A string to use for the category description.
 *   - cid: The category ID.
 *
 * @ingroup forms
 * @see aggregator_menu()
 * @see aggregator_form_category_validate()
 * @see aggregator_form_category_submit()
 */
function aggregator_form_category($form, &$form_state, $edit = NULL) {
  $form['title'] = array('#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => isset($edit->title) ? $edit->title : '',
    '#maxlength' => 64,
    '#required' => TRUE,
  );
  $form['description'] = array('#type' => 'textarea',
    '#title' => t('Description'),
    '#default_value' => isset($edit->description) ? $edit->description : '',
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save'));
  if (!empty($edit->cid)) {
    $form['actions']['delete'] = array('#type' => 'submit', '#value' => t('Delete'));
    $form['cid'] = array('#type' => 'hidden', '#value' => $edit->cid);
  }

  return $form;
}

/**
 * Form validation handler for aggregator_form_category().
 *
 * @see aggregator_form_category_submit()
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
 * Form submission handler for aggregator_form_category().
 *
 * @see aggregator_form_category_validate()
 *
 * @todo Add delete confirmation dialog.
 */
function aggregator_form_category_submit($form, &$form_state) {
  // @todo Replicate this cache invalidation when these ops are separated.
  // Invalidate the block cache to update aggregator category-based derivatives.
  if (module_exists('block')) {
    drupal_container()->get('plugin.manager.block')->clearCachedDefinitions();
  }
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
