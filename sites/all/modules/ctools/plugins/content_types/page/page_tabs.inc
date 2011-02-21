<?php
// $Id: page_tabs.inc,v 1.4 2010/10/11 22:18:22 sdboyer Exp $

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
  'title' => t('Tabs'),
  'single' => TRUE,
  'icon' => 'icon_page.png',
  'description' => t('Add the tabs (local tasks) as content.'),
  'category' => t('Page elements'),
  'render last' => TRUE,
  'defaults' => array(
    'type' => 'both',
    'id' => 'tabs',
  ),
);

/**
 * Output function for the 'page_tabs' content type.
 *
 * Outputs the tabs (local tasks) of the current page.
 */
function ctools_page_tabs_content_type_render($subtype, $conf, $panel_args) {
  $block = new stdClass();
  $block->content = theme('menu_local_tasks');

  return $block;
}


function ctools_page_tabs_content_type_edit_form($form, &$form_state) {
  $conf = $form_state['conf'];

  $form['type'] = array(
    '#title' => t('Tabs type'),
    '#type' => 'select',
    '#options' => array(
      'both' => t('Primary and secondary'),
      'primary' => t('Primary'),
      'secondary' => t('Secondary'),
    ),
    '#default_value' => $conf['type'],
  );

  $form['id'] = array(
    '#title' => t('CSS id to use'),
    '#type' => 'textfield',
    '#default_value' => $conf['id'],
  );
  return $form;
}

/**
 * The submit form stores the data in $conf.
 */
function ctools_page_tabs_content_type_edit_form_submit($form, &$form_state) {
  foreach (array_keys($form_state['plugin']['defaults']) as $key) {
    if (isset($form_state['values'][$key])) {
      $form_state['conf'][$key] = $form_state['values'][$key];
    }
  }
}
