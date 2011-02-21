<?php
// $Id: user_from_view.inc,v 1.2 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Plugin to provide an relationship handler for user from term.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('User from view'),
  'keyword' => 'user',
  'description' => t('Extract a user context from a view context of the base type user.'),
  'required context' => new ctools_context_required(t('View'), 'view', array('base' => 'users')),
  'context' => 'views_content_user_from_view_context',
  'settings form' => 'views_content_user_from_view_settings_form',
  'settings form validate' => 'views_content_user_from_view_settings_form_validate',
  'defaults' => array('row' => 1),
);

/**
 * Return a new context based on an existing context.
 */
function views_content_user_from_view_context($context, $conf, $placeholder = FALSE) {
  // If unset it wants a generic, unfilled context, which is just NULL.
  if (empty($context->data) || $placeholder) {
    return ctools_context_create_empty('user', NULL);
  }
  $view = views_content_context_get_view($context);
  // Ensure the view executes, but we don't need its output.
  views_content_context_get_output($context);

  $row = intval($conf['row']) - 1;
  if (isset($view->result[$row])) {
    $uid = $view->result[$row]->{$view->base_field};
    if ($uid) {
      $user = user_load($uid);
      return ctools_context_create('user', $user);
    }
  }
  return ctools_context_create_empty('user', NULL);
}

/**
 * Settings form for the relationship.
 */
function views_content_user_from_view_settings_form($form, &$form_state) {
  $conf = $form_state['conf'];
  $form['row'] = array(
    '#title' => t('Row number'),
    '#type' => 'textfield',
    '#default_value' => $conf['row'],
  );

  return $form;
}

function views_content_user_from_view_settings_form_validate($form, &$form_state) {
  if (intval($form_state['values']['row']) <= 0) {
    form_error($form['row'], t('Row number must be a positive integer value.'));
  }
}
