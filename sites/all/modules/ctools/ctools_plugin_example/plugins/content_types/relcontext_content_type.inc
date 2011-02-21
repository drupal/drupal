<?php
// $Id: relcontext_content_type.inc,v 1.3 2010/01/29 19:53:48 merlinofchaos Exp $


/**
 * @file
 * Content type that displays the relcontext context type.
 *
 * This example is for use with the relcontext relationship to show
 * how we can get a relationship-context into a data type.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  // Used in add content dialogs.
  'title' => t('CTools example relcontext content type'),
  'admin info' => 'ctools_plugin_example_relcontext_content_type_admin_info',
  'content_types' => 'relcontext_content_type',
  'single' => TRUE,
  'render callback' => 'relcontext_content_type_render',
  // Icon goes in the directory with the content type. Here, in plugins/content_types.
  'icon' => 'icon_example.png',
  'description' => t('Relcontext content type - works with relcontext context.'),
  'required context' => new ctools_context_required(t('Relcontext'), 'relcontext'),
  'category' => array(t('CTools Examples'), -9),
  'edit form' => 'relcontext_edit_form',

  // this example does not provide 'admin info', which would populate the
  // panels builder page preview.

);

/**
 * Run-time rendering of the body of the blcok
 *
 * @param $subtype
 * @param $conf
 *   Configuration as done at admin time
 * @param $args
 * @param $context
 *   Context - in this case we don't have any
 *
 * @return
 *   An object with at least title and content members
 */
function relcontext_content_type_render($subtype, $conf, $args, $context) {
  $data = $context->data;
  $block = new stdClass();

  // Don't forget to check this data if it's untrusted.
  // The title actually used in rendering.
  $block->title = "Relcontext content type";
  $block->content = t("
    This is a block of data created by the Relcontent content type.
    Data in the block may be assembled from static text (like this) or from the
    content type settings form (\$conf) for the content type, or from the context
    that is passed in. <br />
    In our case, the configuration form (\$conf) has just one field, 'config_item_1;
    and it's configured with:
    ");
    if (!empty($conf)) {
    $block->content .= '<div style="border: 1px solid red;">' . var_export($conf['config_item_1'], TRUE) . '</div>';
  }
  if (!empty($context)) {
    $block->content .= '<br />The args ($args) were <div style="border: 1px solid yellow;" >' .
      var_export($args, TRUE) . '</div>';
  }
  $block->content .= '<br />And the relcontext context  ($context->data->description)
    (which was created from a
    simplecontext context) was <div style="border: 1px solid green;" >' .
    print_r($context->data->description, TRUE) . '</div>';
  return $block;
}

/**
 * 'Edit' callback for the content type.
 * This example just returns a form.
 *
 */
function relcontext_edit_form(&$form, &$form_state) {
  $conf = $form_state['conf'];

  $form['config_item_1'] = array(
    '#type' => 'textfield',
    '#title' => t('Config Item 1 (relcontext)'),
    '#size' => 50,
    '#description' => t('Setting for relcontext.'),
    '#default_value' => !empty($conf['config_item_1']) ? $conf['config_item_1'] : '',
    '#prefix' => '<div class="clear-block no-float">',
    '#suffix' => '</div>',
  );
  return $form;
}

function relcontext_edit_form_submit(&$form, &$form_state) {
  foreach (element_children($form) as $key) {
    if (!empty($form_state['values'][$key])) {
      $form_state['conf'][$key] = $form_state['values'][$key];
    }
  }
}
