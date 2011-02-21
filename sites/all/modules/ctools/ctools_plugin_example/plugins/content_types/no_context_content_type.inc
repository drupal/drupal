<?php
// $Id: no_context_content_type.inc,v 1.2 2010/01/29 19:53:48 merlinofchaos Exp $

/**
 * @file
 * "No context" sample content type. It operates with no context at all. It would
 * be basically the same as a 'custom content' block, but it's not even that
 * sophisticated.
 *
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('CTools example no context content type'),
  'description' => t('No context content type - requires and uses no context.'),

  // 'single' => TRUE means has no subtypes.
  'single' => TRUE,
  // Constructor.
  'content_types' => array('no_context_content_type'),
  // Name of a function which will render the block.
  'render callback' => 'no_context_content_type_render',
  // The default context.
  'defaults' => array(),

  // This explicitly declares the config form. Without this line, the func would be
  // ctools_plugin_example_no_context_content_type_edit_form.
  'edit form' => 'no_context_content_type_edit_form',

  // Icon goes in the directory with the content type.
  'icon' => 'icon_example.png',
  'category' => array(t('CTools Examples'), -9),

  // this example does not provide 'admin info', which would populate the
  // panels builder page preview.
);

/**
 * Run-time rendering of the body of the block.
 *
 * @param $subtype
 * @param $conf
 *   Configuration as done at admin time.
 * @param $args
 * @param $context
 *   Context - in this case we don't have any.
 *
 * @return
 *   An object with at least title and content members.
 */
function no_context_content_type_render($subtype, $conf, $args, $context) {
  $block = new stdClass();

  $ctools_help = theme('advanced_help_topic', 'ctools', 'plugins', 'title');
  $ctools_plugin_example_help = theme('advanced_help_topic', 'ctools_plugin_example', 'Chaos-Tools--CTools--Plugin-Examples', 'title');

  // The title actually used in rendering
  $block->title = check_plain("No-context content type");
  $block->content = t("
  <div>Welcome to the CTools Plugin Example demonstration content type.

  This block is a content type which requires no context at all. It's like a custom block,
  but not even that sophisticated.

  For more information on the example plugins, please see the advanced help for

  {$ctools_help} and {$ctools_plugin_example_help}
  </div>
  ");
  if (!empty($conf)) {
    $block->content .= '<div>The only information that can be displayed in this block comes from the code and its settings form: </div>';
    $block->content .= '<div style="border: 1px solid red;">' . var_export($conf, TRUE) . '</div>';
  }

  return $block;

}

/**
 * 'Edit form' callback for the content type.
 * This example just returns a form; validation and submission are standard drupal
 * Note that if we had not provided an entry for this in hook_content_types,
 * this could have had the default name
 * ctools_plugin_example_no_context_content_type_edit_form.
 *
 */
function no_context_content_type_edit_form(&$form, &$form_state) {
  $conf = $form_state['conf'];
  $form['item1'] = array(
    '#type' => 'textfield',
    '#title' => t('Item1'),
    '#size' => 50,
    '#description' => t('The setting for item 1.'),
    '#default_value' => !empty($conf['item1']) ? $conf['item1'] : '',
    '#prefix' => '<div class="clear-block no-float">',
    '#suffix' => '</div>',
  );
  $form['item2'] = array(
    '#type' => 'textfield',
    '#title' => t('Item2'),
    '#size' => 50,
    '#description' => t('The setting for item 2'),
    '#default_value' => !empty($conf['item2']) ? $conf['item2'] : '',
    '#prefix' => '<div class="clear-block no-float">',
    '#suffix' => '</div>',
  );
  return $form;
}

function no_context_content_type_edit_form_submit(&$form, &$form_state) {
  foreach (array('item1', 'item2') as $key) {
    $form_state['conf'][$key] = $form_state['values'][$key];
  }
}