<?php
// $Id: ajax.inc,v 1.20.4.17 2011/01/03 22:56:39 merlinofchaos Exp $

/**
 * @file ajax.inc
 *
 * Handles the server side AJAX interactions of Views.
 *
 * @defgroup ajax Views ajax library
 * @{
 */

/**
 * Menu callback to load a view via AJAX.
 */
function views_ajax() {
  if (isset($_REQUEST['view_name']) && isset($_REQUEST['view_display_id'])) {
    $name = $_REQUEST['view_name'];
    $display_id = $_REQUEST['view_display_id'];
    $args = isset($_REQUEST['view_args']) && $_REQUEST['view_args'] !== '' ? explode('/', $_REQUEST['view_args']) : array();
    $path = isset($_REQUEST['view_path']) ? $_REQUEST['view_path'] : NULL;
    $dom_id = isset($_REQUEST['view_dom_id']) ? intval($_REQUEST['view_dom_id']) : NULL;
    $pager_element = isset($_REQUEST['pager_element']) ? intval($_REQUEST['pager_element']) : NULL;

    $commands = array();

    $arg = explode('/', $_REQUEST['view_path']);

    // Load the view.
    $view = views_get_view($name);
    if ($view && $view->access($display_id)) {
      // Fix 'q' for paging.
      if (!empty($path)) {
        $_GET['q'] = $path;
      }

      // Add all $_POST data, because AJAX is always a post and many things,
      // such as tablesorts, exposed filters and paging assume $_GET.
      $_GET += $_POST;

      // Override the display's pager_element with the one actually used.
      if (isset($pager_element)) {
        $view->display[$display_id]->handler->set_option('pager_element', $pager_element);
      }
      // Reuse the same DOM id so it matches that in Drupal.settings.
      $view->dom_id = $dom_id;

      $errors = $view->validate();
      if ($errors === TRUE) {
        $commands[] = ajax_command_replace('.view-dom-id-' . $dom_id, $view->preview($display_id, $args));
      }
      else {
        foreach ($errors as $error) {
          drupal_set_message($error, 'error');
        }
      }
    }
    return array('#type' => 'ajax', '#commands' => $commands);
  }
}

/**
 * Creates a Drupal AJAX 'viewsSetForm' command.
 *
 * @param $output
 *   The form to display in the modal.
 * @param $title
 *   The title.
 * @param $url
 *   An optional URL.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 */
function views_ajax_command_set_form($output, $title, $url = NULL) {
  $command = array(
    'command' => 'viewsSetForm',
    'output' => $output,
    'title' => $title,
  );
  if (isset($url)) {
    $command['url'] = $url;
  }
  return $command;
}

/**
 * Creates a Drupal AJAX 'viewsDismissForm' command.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 */
function views_ajax_command_dismiss_form() {
  $command = array(
    'command' => 'viewsDismissForm',
  );
  return $command;
}

/**
 * Creates a Drupal AJAX 'viewsHilite' command.
 *
 * @param $selector
 *   The selector to highlight
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 */
function views_ajax_command_hilite($selector) {
  return array(
    'command' => 'viewsHilite',
    'selector' => $selector,
  );
}

/**
 * Creates a Drupal AJAX 'addTab' command.
 *
 * @param $id
 *   The DOM ID.
 * @param $title
 *   The title.
 * @param $body
 *   The body.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 */
function views_ajax_command_add_tab($id, $title, $body) {
  $command = array(
    'command' => 'viewsAddTab',
    'id' => $id,
    'title' => $title,
    'body' => $body,
  );
  return $command;
}

/**
 * Disables Save, Cancel, and Delete buttons.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 */
function views_ajax_command_disable_buttons() {
  $command = array(
    'command' => 'viewsDisableButtons',
  );
  return $command;
}

/**
 * Enables Save, Cancel, and Delete buttons.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 */
function views_ajax_command_enable_buttons() {
  $command = array(
    'command' => 'viewsEnableButtons',
  );
  return $command;
}

/**
 * Trigger the Views live preview.
 *
 * @return
 *   An array suitable for use with the ajax_render() function.
 */
function views_ajax_command_trigger_preview() {
  $command = array(
    'command' => 'viewsTriggerPreview',
  );
  return $command;
}

/**
 * Return an AJAX error.
 */
function views_ajax_error($message) {
  $commands = array();
  $commands[] = views_ajax_command_set_form($message, t('Error'));
  return $commands;
}

/**
 * Wrapper around drupal_build_form to handle some AJAX stuff automatically.
 * This makes some assumptions about the client.
 */
function views_ajax_form_wrapper($form_id, &$form_state) {
  ctools_include('dependent');

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
  }

  if (!empty($form_state['ajax']) && (empty($form_state['executed']) || !empty($form_state['rerender']))) {
    // If the form didn't execute and we're using ajax, build up a
    // Ajax command list to execute.
    $commands = array();

    $display = '';
    if ($messages = theme('status_messages')) {
      $display = '<div class="views-messages">' . $messages . '</div>';
    }
    $display .= $output;

    $title = empty($form_state['title']) ? '' : $form_state['title'];
    if (!empty($form_state['help_topic'])) {
      $module = !empty($form_state['help_module']) ? $form_state['help_module'] : 'views';
      if (module_exists('advanced_help')) {
        $title = theme('advanced_help_topic', array('module' => $module, 'topic' => $form_state['help_topic'])) . $title;
      }
    }

    $url = empty($form_state['url']) ? url($_GET['q'], array('absolute' => TRUE)) : $form_state['url'];

    $commands[] = views_ajax_command_set_form($display, $title, $url);
    if (!empty($form_state['js settings'])) {
      $commands[] = ajax_command_settings($form_state['js settings']);
    }

    if (!empty($form_state['#section'])) {
      $commands[] = views_ajax_command_hilite('.' . drupal_clean_css_identifier($form_state['#section']));
    }

    return $commands;
  }

  // These forms have the title built in, so set the title here:
  if (empty($form_state['ajax']) && !empty($form_state['title'])) {
    drupal_set_title($form_state['title']);
  }

  return $output;
}


/**
 * Page callback for views user autocomplete
 */
function views_ajax_autocomplete_user($string = '') {
  // The user enters a comma-separated list of tags. We only autocomplete the last tag.
  $array = drupal_explode_tags($string);

  // Fetch last tag
  $last_string = trim(array_pop($array));
  $matches = array();
  if ($last_string != '') {
    $prefix = count($array) ? implode(', ', $array) . ', ' : '';

    if (strpos('anonymous', strtolower($last_string)) !== FALSE) {
      $matches[$prefix . 'Anonymous'] = 'Anonymous';
    }
    $result = db_query_range("SELECT name FROM {users} WHERE name = :string",
      0, 1,
      array(
        ':string' => $last_string
      ));

    foreach ($result as $account) {
      $n = $account->name;
      // Commas and quotes in terms are special cases, so encode 'em.
      if (strpos($account->name, ',') !== FALSE || strpos($account->name, '"') !== FALSE) {
        $n = '"' . str_replace('"', '""', $account->name) . '"';
      }
      $matches[$prefix . $n] = check_plain($account->name);
    }
  }

  drupal_json_output($matches);
}

/**
 * Page callback for views taxonomy autocomplete.
 *
 * @param $vid
 *   The vocabulary id of the tags which should be returned.
 *
 * @param $tags_typed
 *   The typed string of the user.
 *
 * @see taxonomy_autocomplete()
 */
function views_ajax_autocomplete_taxonomy($vid, $tags_typed = '') {
  // The user enters a comma-separated list of tags. We only autocomplete the last tag.
  $tags_typed = drupal_explode_tags($tags_typed);
  $tag_last = drupal_strtolower(array_pop($tags_typed));

  $matches = array();
  if ($tag_last != '') {

    $query = db_select('taxonomy_term_data', 't');
    $query->addTag('translatable');
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

    $prefix = count($tags_typed) ? implode(', ', $tags_typed) . ', ' : '';

    $term_matches = array();
    foreach ($tags_return as $tid => $name) {
      $n = $name;
      // Term names containing commas or quotes must be wrapped in quotes.
      if (strpos($name, ',') !== FALSE || strpos($name, '"') !== FALSE) {
        $n = '"' . str_replace('"', '""', $name) . '"';
      }
      else {
        $term_matches[$prefix . $n] = check_plain($name);
      }
    }
  }

  drupal_json_output($term_matches);
}

/**
 * @}
 */
