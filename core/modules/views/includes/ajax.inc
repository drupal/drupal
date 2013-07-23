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
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\String;

/**
 * @defgroup views_ajax Views AJAX library
 * @{
 * Handles the server side AJAX interactions of Views.
 */

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
    $status_messages = array('#theme' => 'status_messages');
    if ($messages = drupal_render($status_messages)) {
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
  // The user enters a comma-separated list of tags. We only autocomplete the
  // last tag.
  $tags_typed = Tags::explode(Drupal::request()->query->get('q'));
  $tag_last = Unicode::strtolower(array_pop($tags_typed));
  $term_matches = array();

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

    $prefix = count($tags_typed) ? Tags::implode($tags_typed) . ', ' : '';

    foreach ($tags_return as $tid => $name) {
      $n = $name;
      // Term names containing commas or quotes must be wrapped in quotes.
      if (strpos($name, ',') !== FALSE || strpos($name, '"') !== FALSE) {
        $n = '"' . str_replace('"', '""', $name) . '"';
      }
      // Add term name to list of matches.
      $term_matches[$prefix . $n] = String::checkPlain($name);
    }
  }

  return new JsonResponse($term_matches);
}

/**
 * @}
 */
