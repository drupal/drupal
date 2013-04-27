<?php

/**
 * @file
 * Administration page callbacks for the Aggregator module.
 */

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\aggregator\Plugin\Core\Entity\Feed;

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
