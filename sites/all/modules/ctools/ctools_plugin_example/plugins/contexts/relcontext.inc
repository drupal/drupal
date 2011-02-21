<?php
// $Id: relcontext.inc,v 1.2 2010/01/29 19:53:48 merlinofchaos Exp $

/**
 * @file
 * Sample ctools context type plugin that
 * is used in this demo to create a relcontext from an existing simplecontext.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Relcontext"),
  'description' => t('A relcontext object.'),
  // Function to create the relcontext.
  'context' => 'ctools_plugin_example_context_create_relcontext',
  // Function that does the settings.
  'settings form' => 'relcontext_settings_form',
  'keyword' => 'relcontext',
  'context name' => 'relcontext',
);

/**
 * Create a context, either from manual configuration (form) or from an argument on the URL.
 *
 * @param $empty
 *   If true, just return an empty context.
 * @param $data
 *   If from settings form, an array as from a form. If from argument, a string.
 * @param $conf
 *   TRUE if the $data is coming from admin configuration, FALSE if it's from a URL arg.
 *
 * @return
 *   a Context object.
 */
function ctools_plugin_example_context_create_relcontext($empty, $data = NULL, $conf = FALSE) {
  $context = new ctools_context('relcontext');
  $context->plugin = 'relcontext';
  if ($empty) {
    return $context;
  }
  if ($conf) {
    if (!empty($data)) {
      $context->data = new stdClass();
      // For this simple item we'll just create our data by stripping non-alpha and
      // adding 'sample_relcontext_setting' to it.
      $context->data->description = 'relcontext_from__' . preg_replace('/[^a-z]/i', '', $data['sample_relcontext_setting']);
      $context->data->description .= '_from_configuration_sample_simplecontext_setting';
      $context->title = t("Relcontext context from simplecontext");
      return $context;
    }
  }
  else {
    // $data is coming from an arg - it's just a string.
    // This is used for keyword.
    $context->title = "relcontext_" . $data->data->description;
    $context->argument = $data->argument;
    // Make up a bogus context.
    $context->data = new stdClass();
    // For this simple item we'll just create our data by stripping non-alpha and
    // prepend 'relcontext_' and adding '_created_from_from_simplecontext' to it.
    $context->data->description = 'relcontext_' . preg_replace('/[^a-z]/i', '', $data->data->description);
    $context->data->description .= '_created_from_simplecontext';
    return $context;
  }
}

function relcontext_settings_form($conf, $external = FALSE) {
  $form = array();

  $form['sample_relcontext_setting'] = array(
    '#type' => 'textfield',
    '#title' => t('Relcontext setting'),
    '#size' => 50,
    '#description' => t('Just an example setting.'),
    '#default_value' => !empty($conf['sample_relcontext_setting']) ? $conf['sample_relcontext_setting'] : '',
    '#prefix' => '<div class="clear-block no-float">',
    '#suffix' => '</div>',
  );
  return $form;
}

