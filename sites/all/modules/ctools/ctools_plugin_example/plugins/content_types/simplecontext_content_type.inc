<?php
// $Id: simplecontext_content_type.inc,v 1.3 2010/01/29 19:53:48 merlinofchaos Exp $


/**
 * @file
 * Sample ctools content type that takes advantage of context.
 *
 * This example uses the context it gets (simplecontext) to demo how a
 * ctools content type can access and use context. Note that the simplecontext
 * can be either configured manually into a panel or it can be retrieved via
 * an argument.
 *
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('Simplecontext content type'),
  'content_types' => 'simplecontext_content_type',
  // 'single' means not to be subtyped.
  'single' => TRUE,
  // Name of a function which will render the block.
  'render callback' => 'simplecontext_content_type_render',

  // Icon goes in the directory with the content type.
  'icon' => 'icon_example.png',
  'description' => t('Simplecontext content type - works with a simplecontext context.'),
  'required context' => new ctools_context_required(t('Simplecontext'), 'simplecontext'),
  'edit form' => 'simplecontext_content_type_edit_form',
  'admin title' => 'ctools_plugin_example_simplecontext_content_type_admin_title',

  // presents a block which is used in the preview of the data.
  // Pn Panels this is the preview pane shown on the panels building page.
  'admin info' => 'ctools_plugin_example_simplecontext_content_type_admin_info',
  'category' => array(t('CTools Examples'), -9),
);

function ctools_plugin_example_simplecontext_content_type_admin_title($subtype, $conf, $context = NULL) {
  $output = t('Simplecontext');
  if ($conf['override_title'] && !empty($conf['override_title_text'])) {
    $output = filter_xss_admin($conf['override_title_text']);
  }
  return $output;
}

/**
 * Callback to provide administrative info (the preview in panels when building
 * a panel).
 *
 * In this case we'll render the content with a dummy argument and
 * a dummy context.
 */
function ctools_plugin_example_simplecontext_content_type_admin_info($subtype, $conf, $context = NULL) {
  $context = new stdClass();
  $context->data = new stdClass();
  $context->data->description = t("no real context");
  $block = simplecontext_content_type_render($subtype, $conf, array("Example"), $context);
  return $block;
}

/**
 * Run-time rendering of the body of the block (content type)
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
function simplecontext_content_type_render($subtype, $conf, $args, $context) {
  $data = $context->data;
  $block = new stdClass();

  // Don't forget to check this data if it's untrusted.
  // The title actually used in rendering.
  $block->title = "Simplecontext content type";
  $block->content = t("
    This is a block of data created by the Simplecontext content type.
    Data in the block may be assembled from static text (like this) or from the
    content type settings form (\$conf) for the content type, or from the context
    that is passed in. <br />
    In our case, the configuration form (\$conf) has just one field, 'config_item_1;
    and it's configured with:
    ");
  if (!empty($conf)) {
    $block->content .= '<div style="border: 1px solid red;">' . print_r(filter_xss_admin($conf['config_item_1']), TRUE) . '</div>';
  }
  if (!empty($context)) {
    $block->content .= '<br />The args ($args) were <div style="border: 1px solid yellow;" >' .
      var_export($args, TRUE) . '</div>';
  }
  $block->content .= '<br />And the simplecontext context ($context->data->description) was <div style="border: 1px solid green;" >' .
    print_r($context->data->description, TRUE) . '</div>';
  return $block;
}

/**
 * 'Edit' callback for the content type.
 * This example just returns a form.
 *
 */
function simplecontext_content_type_edit_form(&$form, &$form_state) {
  $conf = $form_state['conf'];
  $form['config_item_1'] = array(
    '#type' => 'textfield',
    '#title' => t('Config Item 1 for simplecontext content type'),
    '#size' => 50,
    '#description' => t('The stuff for item 1.'),
    '#default_value' => !empty($conf['config_item_1']) ? $conf['config_item_1'] : '',
    '#prefix' => '<div class="clear-block no-float">',
    '#suffix' => '</div>',
  );

  return $form;
}

function simplecontext_content_type_edit_form_submit(&$form, &$form_state) {
  foreach (element_children($form) as $key) {
    if (!empty($form_state['values'][$key])) {
      $form_state['conf'][$key] = $form_state['values'][$key];
    }
  }
}