<?php

/**
 * @file
 * Handles the server side AJAX interactions of Views.
 */

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\views\Ajax\HighlightCommand;
use Drupal\views\Ajax\SetFormCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\views\Ajax\ScrollTopCommand;
use Drupal\views\Ajax\ViewAjaxResponse;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * @defgroup views_ajax Views AJAX library
 * @{
 * Handles the server side AJAX interactions of Views.
 */

/**
 * Menu callback to load a view via AJAX.
 */
function views_ajax() {
  $request = drupal_container()->get('request');
  $name = $request->request->get('view_name');
  $display_id = $request->request->get('view_display_id');
  if (isset($name) && isset($display_id)) {
    $args = $request->request->get('view_args');
    $args = isset($args) && $args !== '' ? explode('/', $args) : array();
    $path = $request->request->get('view_path');
    $dom_id = $request->request->get('view_dom_id');
    $dom_id = isset($dom_id) ? preg_replace('/[^a-zA-Z0-9_-]+/', '-', $dom_id) : NULL;
    $pager_element = $request->request->get('pager_element');
    $pager_element = isset($pager_element) ? intval($pager_element) : NULL;

    $response = new ViewAjaxResponse();

    // Remove all of this stuff from the query of the request so it doesn't end
    // up in pagers and tablesort URLs.
    foreach (array('view_name', 'view_display_id', 'view_args', 'view_path', 'view_dom_id', 'pager_element', 'view_base_path', 'ajax_html_ids', 'ajax_page_state') as $key) {
      if ($request->query->has($key)) {
        $request->query->remove($key);
      }
      if ($request->request->has($key)) {
        $request->request->remove($key);
      }
    }

    // Load the view.
    $view = views_get_view($name);
    if ($view && $view->access($display_id)) {
      // Fix the current path for paging.
      if (!empty($path)) {
        _current_path($path);
      }

      // Add all $_POST data, because AJAX is always a post and many things,
      // such as tablesorts, exposed filters and paging assume $_GET.
      $request_all = $request->request->all();
      $query_all = $request->query->all();
      $request->query->replace($request_all + $query_all);

      // Overwrite the destination.
      // @see drupal_get_destination()
      $origin_destination = $path;
      $query = drupal_http_build_query($request->query->all());
      if ($query != '') {
        $origin_destination .= '?' . $query;
      }
      $destination = &drupal_static('drupal_get_destination');
      $destination = array('destination' => $origin_destination);

      // Override the display's pager_element with the one actually used.
      if (isset($pager_element)) {
        $response->addCommand(new ScrollTopCommand(".view-dom-id-$dom_id"));
        $view->displayHandlers->get($display_id)->setOption('pager_element', $pager_element);
      }
      // Reuse the same DOM id so it matches that in Drupal.settings.
      $view->dom_id = $dom_id;

      $preview = $view->preview($display_id, $args);
      $response->addCommand(new ReplaceCommand(".view-dom-id-$dom_id", drupal_render($preview)));
    }
    return $response;
  }
}

/**
 * Wrapper around drupal_build_form to handle some AJAX stuff automatically.
 * This makes some assumptions about the client.
 */
function views_ajax_form_wrapper($form_id, &$form_state) {
  // This won't override settings already in.
  $form_state += array(
    'rerender' => FALSE,
    'no_redirect' => !empty($form_state['ajax']),
    'no_cache' => TRUE,
    'build_info' => array(
      'args' => array(),
    ),
  );

  $form = drupal_build_form($form_id, $form_state);
  $output = drupal_render($form);

  // These forms have the title built in, so set the title here:
  if (empty($form_state['ajax']) && !empty($form_state['title'])) {
    drupal_set_title($form_state['title']);
    drupal_add_css(drupal_get_path('module', 'views_ui') . '/css/views_ui.admin.css');
  }

  if (!empty($form_state['ajax']) && (empty($form_state['executed']) || !empty($form_state['rerender']))) {
    // If the form didn't execute and we're using ajax, build up a
    // Ajax command list to execute.
    $response = new AjaxResponse();

    $display = '';
    if ($messages = theme('status_messages')) {
      $display = '<div class="views-messages">' . $messages . '</div>';
    }
    $display .= $output;

    $title = empty($form_state['title']) ? '' : $form_state['title'];

    $url = empty($form_state['url']) ? url(current_path(), array('absolute' => TRUE)) : $form_state['url'];

    $response->addCommand(new SetFormCommand($display, $title, $url));

    if (!empty($form_state['#section'])) {
      $response->addCommand(new HighlightCommand('.' . drupal_clean_css_identifier($form_state['#section'])));
    }

    return $response;
  }

  // These forms have the title built in, so set the title here:
  if (empty($form_state['ajax']) && !empty($form_state['title'])) {
    drupal_set_title($form_state['title']);
  }

  return $output;
}

/**
 * Page callback for views taxonomy autocomplete.
 *
 * @param $vid
 *   The vocabulary id of the tags which should be returned.
 *
 * @see taxonomy_autocomplete()
 */
function views_ajax_autocomplete_taxonomy($vid) {
  // The user enters a comma-separated list of tags. We only autocomplete the last tag.
  $tags_typed = drupal_explode_tags(drupal_container()->get('request')->query->get('q'));
  $tag_last = drupal_strtolower(array_pop($tags_typed));

  $matches = array();
  if ($tag_last != '') {

    $query = db_select('taxonomy_term_data', 't');
    $query->addTag('term_access');

    // Do not select already entered terms.
    if (!empty($tags_typed)) {
      $query->condition('t.name', $tags_typed, 'NOT IN');
    }
    // Select rows that match by term name.
    $tags_return = $query
      ->fields('t', array('tid', 'name'))
      ->condition('t.vid', $vid)
      ->condition('t.name', '%' . db_like($tag_last) . '%', 'LIKE')
      ->range(0, 10)
      ->execute()
      ->fetchAllKeyed();

    $prefix = count($tags_typed) ? drupal_implode_tags($tags_typed) . ', ' : '';

    $term_matches = array();
    foreach ($tags_return as $tid => $name) {
      $n = $name;
      // Term names containing commas or quotes must be wrapped in quotes.
      if (strpos($name, ',') !== FALSE || strpos($name, '"') !== FALSE) {
        $n = '"' . str_replace('"', '""', $name) . '"';
      }
      // Add term name to list of matches.
      $term_matches[$prefix . $n] = check_plain($name);
    }
  }

  return new JsonResponse($term_matches);
}

/**
 * @}
 */
