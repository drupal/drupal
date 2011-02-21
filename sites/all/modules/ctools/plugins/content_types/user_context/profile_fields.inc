<?php
// $Id: profile_fields.inc,v 1.9 2010/10/18 18:00:48 merlinofchaos Exp $

if (module_exists('profile') && !is_null(profile_user_categories())) {
  /**
   * Plugins are described by creating a $plugin array which will be used
   * by the system that includes this file.
   */
  $plugin = array(
    'single' => TRUE,
    'title' => t('Profile category'),
    'icon' => 'icon_user.png',
    'description' => t('Contents of a single profile category.'),
    'required context' => new ctools_context_required(t('User'), 'user'),
    'category' => t('User'),
    'defaults' => array('category' => '', 'empty' => ''),
    'hook theme' => 'ctools_profile_fields_content_type_theme',
  );
}

/**
 * 'Render' callback for the 'profile fields' content type.
 */
function ctools_profile_fields_content_type_render($subtype, $conf, $panel_args, $context) {
  $account = isset($context->data) ? clone($context->data) : NULL;
  $block = new stdClass();
  $block->module = 'profile fields';

  if ($account) {
    // Get the category from the options
    $category = str_replace("_", " ", $conf['category']);

    // Set the subject to the name of the category
    $block->subject = $category;

    // Put all the fields in the category into an array
    profile_view_profile($account);

    if (is_array($account->content[$category])) {
      foreach ($account->content[$category] as $field) {
        if (is_array($field['#attributes'])) {
          // @todo 'class' is *always* an array now. 04/10/2009 sun
          $vars[$field['#attributes']['class']]['title'] = $field['#title'];
          $vars[$field['#attributes']['class']]['value'] = $field['#value'];
        }
      }
    }

    if (count($vars) == 0) {
      // Output the given empty text
      $output = $conf['empty'];
    }
    else {
      // Call the theme function with the field vars
      $output = theme('profile_fields_pane', $category, $vars);
    }

    $block->content = $output;
    $block->delta = $account->uid;
  }
  else {
    $block->subject = $conf['category'];
    $block->content = t('Profile content goes here.');
    $block->delta   = 'unknown';
  }

  return $block;
}
/**
 * Helper function : build the list of categories for the 'edit' form.
 */
function _ctools_profile_fields_options() {
  $cat_list = array();

  $categories = profile_categories();
  foreach ($categories as $key => $value) {
    $cat_list[str_replace(" ", "_", $value['name'])] = $value['title'];
  }

  return $cat_list;
}

/**
 * 'Edit' callback for the 'profile fields' content type.
 */
function ctools_profile_fields_content_type_edit_form($form, &$form_state) {
  $conf = $form_state['conf'];
  $form['category'] = array(
    '#type' => 'radios',
    '#title' => t('Which category'),
    '#options' => _ctools_profile_fields_options(),
    '#default_value' => $conf['category'],
    '#prefix' => '<div class="clearfix no-float">',
    '#suffix' => '</div>',
  );

  $form['empty'] = array(
    '#type' => 'textarea',
    '#title' => 'Empty text',
    '#description' => t('Text to display if category has no data. Note that title will not display unless overridden.'),
    '#rows' => 5,
    '#default_value' => $conf['empty'],
    '#prefix' => '<div class="clearfix no-float">',
    '#suffix' => '</div>',
  );

  return $form;
}

function ctools_profile_fields_content_type_edit_form_submit($form, &$form_state) {
  // Copy everything from our defaults.
  foreach (array_keys($form_state['plugin']['defaults']) as $key) {
    $form_state['conf'][$key] = $form_state['values'][$key];
  }
}

/**
 * 'Title' callback for the 'profile fields' content type.
 */
function ctools_profile_fields_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" profile fields', array('@s' => $conf['category']));
}

function ctools_profile_fields_content_type_theme(&$theme, $plugin) {
  $theme['profile_fields_pane'] = array(
    'variables' => array('category' => NULL, 'vars' => NULL),
    'path' => $plugin['path'],
    'template' => 'profile_fields_pane',
  );
}
