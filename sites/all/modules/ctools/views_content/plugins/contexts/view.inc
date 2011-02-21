<?php
// $Id: view.inc,v 1.2 2010/10/11 22:18:24 sdboyer Exp $

/**
 * @file
 *
 * Plugin to provide a node context. A node context is a node wrapped in a
 * context object that can be utilized by anything that accepts contexts.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("View"),
  'description' => t('Loads a view result into a context that can then be displayed across a panel or turned into other contexts.'),
  'context' => 'views_content_context_view_create',

  'settings form' => 'views_content_context_view_settings_form',
  'settings form validate' => 'views_content_context_view_settings_form_validate',
  'settings form submit' => 'views_content_context_view_settings_form_submit',

  'defaults' => array('view' => ''),

  'keyword' => 'view',
  'context name' => 'view',

  /*
  'convert list' => 'views_content_context_view_convert_list',
  'convert' => 'views_content_context_view_convert',
  */

  /*
  'placeholder form' => array(
    '#type' => 'textfield',
    '#description' => t('Enter the node ID of a node for this context.'),
  ),
  */
);

function views_content_context_view_create($empty, $data = NULL, $conf = FALSE) {
  $context = new ctools_context('view');
  $context->plugin = 'view';

  if ($empty) {
    return $context;
  }

  if ($conf) {
    if (is_array($data) && !empty($data['view'])) {
      list($name, $display_id) = explode(':', $data['view'], 2);
      $data = views_get_view($name);
      if ($data) {
        $data->set_display($display_id);
      }
    }
  }

  if (is_object($data)) {
    // We don't store the loaded view as we don't want the view object
    // cached.
    $context->data     = array(
      'name' => $data->name,
      'display' => $data->current_display,
    );

    // At runtime, this can get populated. Once it is populated this
    // object should not be cached.
    $context->view     = NULL;
    $context->title    = $data->get_title();
    $context->argument = $data->name . ':' . $data->current_display;

    $context->restrictions['base'] = array($data->base_table);

    return $context;
  }
}

function views_content_context_view_settings_form($form, &$form_state) {
  $conf = $form_state['conf'];
  $views = views_get_applicable_views('returns context');
  foreach ($views as $data) {
    list($view, $id) = $data;
    $title = $view->display_handler->get_option('admin_title');
    if (!$title) {
      $title = $view->name;
    }
    $options[$view->name . ':' . $id] = $title;
  }

  if (!empty($options)) {
    natcasesort($options);
    $form['view'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('View'),
    );
  }
  else {
    $form['view'] = array(
      '#value' => '<p>' . t('There are currently no views with Context displays enabled. You should go to the view administration and add a Context display to use a view as a context.') . '</p>',
    );
  }

  return $form;
}

/**
 * Validate a node.
 */
function views_content_context_view_settings_form_validate($form, &$form_state) {
  if (empty($form_state['values']['view'])) {
    form_error($form['view'], t('You must select a view.'));
  }
}

/**
 * Provide a list of ways that this context can be converted to a string.
 */
function views_content_context_view_convert_list() {
  $list = array(
  );

  return $list;
}

/**
 * Convert a context into a string.
 */
function views_content_context_view_convert($context, $type) {
  switch ($type) {
  }
}

