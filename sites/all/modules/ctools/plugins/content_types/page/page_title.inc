<?php
// $Id: page_title.inc,v 1.4 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Plugin to handle the 'page' content type which allows the standard page
 * template variables to be embedded into a panel.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'single' => TRUE,
  'title' => t('Page title'),
  'icon' => 'icon_page.png',
  'description' => t('Add the page title as content.'),
  'category' => t('Page elements'),
  'defaults' => array(
    'markup' => 'h1',
    'class' => '',
    'id' => '',
  ),
);

/**
 * Output function for the 'page_title' content type.
 *
 * Outputs the page title of the current page.
 */
function ctools_page_title_content_type_render($subtype, $conf, $panel_args) {
  // TODO: This should have a setting or something for the markup.
  if (empty($conf['markup'])) {
    $conf['markup'] = 'h1';
  }

  if (empty($conf['class'])) {
    $conf['class'] = '';
  }

  if (empty($conf['id'])) {
    $conf['id'] = '';
  }

  $token = ctools_set_callback_token('title', array('ctools_page_title_content_type_token', $conf['markup'], $conf['id'], $conf['class']));

  $block = new stdClass();
  if ($token) {
    $block->content = $token;
  }

  return $block;
}

function ctools_page_title_content_type_edit_form($form, &$form_state) {
  $conf = $form_state['conf'];

  $form['markup'] = array(
    '#title' => t('Title tag'),
    '#type' => 'select',
    '#options' => array(
      'none' => t('- No tag -'),
      'h1' => t('h1'),
      'h2' => t('h2'),
      'h3' => t('h3'),
      'h4' => t('h4'),
      'h5' => t('h5'),
      'h6' => t('h6'),
      'div' => t('div'),
    ),
    '#default_value' => empty($conf['markup']) ? 'h1' : $conf['markup'],
  );

  $form['id'] = array(
    '#title' => t('CSS id to use'),
    '#type' => 'textfield',
    '#default_value' => empty($conf['id']) ? '' : $conf['id'],
  );

  $form['class'] = array(
    '#title' => t('CSS class to use'),
    '#type' => 'textfield',
    '#default_value' => empty($conf['class']) ? '' : $conf['class'],
  );
  return $form;
}

/**
 * The submit form stores the data in $conf.
 */
function ctools_page_title_content_type_edit_form_submit($form, &$form_state) {
  foreach (array_keys($form_state['plugin']['defaults']) as $key) {
    if (isset($form_state['values'][$key])) {
      $form_state['conf'][$key] = $form_state['values'][$key];
    }
  }
}

/**
 * Variable token callback to properly render the page title, with markup.
 */
function ctools_page_title_content_type_token(&$variables, $tag, $id, $class) {
  if ($tag == 'none') {
    return drupal_get_title();
  }

  $output = '<' . $tag;
  if ($id) {
    $output .= ' id="' . $id . '"';
  }

  if ($class) {
    $output .= ' class="' . $class . '"';
  }

  $output .= '>' . drupal_get_title() . '</' . $tag . '>' . "\n";
  return $output;
}
