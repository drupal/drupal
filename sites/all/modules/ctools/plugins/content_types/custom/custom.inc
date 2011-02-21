<?php
// $Id: custom.inc,v 1.17 2011/01/05 22:57:26 merlinofchaos Exp $

/**
 * @file
 * Custom content type.
 *
 * This content type is nothing more than a title and a body that is entered
 * by the user and run through standard filters. The information is stored
 * right in the config, so each custom content is unique.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('Custom content'),
  'no title override' => TRUE,
  'defaults' => array('admin_title' => '', 'title' => '', 'body' => '', 'format' => filter_fallback_format(), 'substitute' => TRUE),
  'js' => array('misc/autocomplete.js', 'misc/textarea.js', 'misc/collapse.js'),
  // Make sure the edit form is only used for some subtypes.
  'edit form' => '',
  'add form' => '',
  'edit text' => t('Edit'),
  'all contexts' => TRUE,
);

/**
 * Return the custom content types with the specified $subtype_id.
 */
function ctools_custom_content_type_content_type($subtype_id) {
  if ($subtype_id == 'custom') {
    return _ctools_default_content_type_content_type();
  }
  else {
    ctools_include('export');
    $content = ctools_export_crud_load('ctools_custom_content', $subtype_id);
    if ($content) {
      return _ctools_custom_content_type_content_type($content);
    }
  }
}

/**
 * Return all custom content types available.
 */
function ctools_custom_content_type_content_types() {
  ctools_include('export');
  $types = array();
  $types['custom'] = _ctools_default_content_type_content_type();

  foreach (ctools_export_crud_load_all('ctools_custom_content') as $name => $content) {
    $types[$name] = _ctools_custom_content_type_content_type($content);
  }
  return $types;
}

/**
 * Settings for the default custom content type.
 *
 * The default is the one that allows the user to actually create a type.
 */
function _ctools_default_content_type_content_type() {
  $info = array(
    'name' => 'custom',
    'title' => t('New custom content'),
    'top level' => TRUE,
    'category' => t('Custom'),
    'description' => t('Create a completely custom piece of HTML content.'),
    'edit form' => 'ctools_custom_content_type_edit_form',
    'all contexts' => TRUE,
    'check editable' => 'ctools_custom_content_type_editable',
  );

  return $info;
}

/**
 * Return an info array for a specific custom content type.
 */
function _ctools_custom_content_type_content_type($content) {
  $info = array(
    'name' => $content->name,
    'title' => check_plain($content->admin_title),
    'description' => check_plain($content->admin_description),
    'category' => $content->category ? check_plain($content->category) : t('Miscellaneous'),
    'all contexts' => TRUE,
    'icon' => 'icon_block_custom.png',
    // Store this here to make it easy to access.
    'content' => $content,
  );

  return $info;
}

/**
 * Given a subtype and a $conf, return the actual settings to use.
 *
 * The actual settings may be stored directly in the pane or this may
 * be a pointer to re-usable content that may be in the database or in
 * an export. We have to determine from the subtype whether or not it
 * is local or shared custom content.
 */
function ctools_custom_content_type_get_conf($subtype, $conf) {
  if ($subtype['name'] != 'custom') {
    $settings = $subtype['content']->settings;
    $settings['custom_type'] = 'fixed';
    $settings['content'] = $subtype['content'];
  }
  else {
    // This means they created it as custom content and then set it as
    // reusable. Since we're not allowed to change the subtype, we're
    // still stored as though we are local, but are pointing off to
    // non-local.
    if (!empty($conf['name'])) {
      ctools_include('export');
      $content = ctools_export_crud_load('ctools_custom_content', $conf['name']);
      if ($content) {
        $settings = $content->settings;
        $settings['custom_type'] = 'fixed';
        $settings['content'] = $content;
        $settings['admin_title'] = $content->admin_title;
      }
      else {
        $content = ctools_export_crud_new('ctools_custom_content');
        $content->name = $conf['name'];
        $settings = array(
          'admin_title' => t('Missing/deleted content'),
          'title' => '',
          'body' => '',
          'format' => filter_fallback_format(),
          'substitute' => TRUE,
          'custom_type' => 'fixed',
          'content' => $content,
        );
      }
    }
    // This means that it is created as custom and has not been set to
    // reusable.
    else {
      $settings = $conf;
      $settings['custom_type'] = 'local';
    }
  }

  return $settings;
}

function ctools_custom_content_type_editable($content_type, $subtype, $conf) {
  if ($subtype['name'] == 'custom' && !empty($conf['name'])) {
    return FALSE;
  }

  return TRUE;
}

/**
 * Output function for the 'custom' content type. Outputs a custom
 * based on the module and delta supplied in the configuration.
 */
function ctools_custom_content_type_render($subtype, $conf, $args, $contexts) {
  $settings = ctools_custom_content_type_get_conf(ctools_custom_content_type_content_type($subtype), $conf);

  static $delta = 0;

  $block          = new stdClass();
  $block->subtype = ++$delta;
  $block->title   = filter_xss_admin($settings['title']);

  // Add keyword substitutions if we were configured to do so.
  $content = $settings['body'];
  if (!empty($contexts) && !empty($settings['substitute'])) {
    $content = ctools_context_keyword_substitute($content, array(), $contexts);
  }

  $block->content = check_markup($content, $settings['format']);
  return $block;
}

/**
 * Callback to provide the administrative title of the custom content.
 */
function ctools_custom_content_type_admin_title($subtype, $conf) {
  $settings = ctools_custom_content_type_get_conf(ctools_custom_content_type_content_type($subtype), $conf);

  $output = t('Custom');
  $title = !empty($settings['admin_title']) ? $settings['admin_title'] : $settings['title'];
  if ($title) {
    if ($settings['custom_type'] != 'fixed') {
      $output = t('Custom: @title', array('@title' => $title));
    }
    else {
      $output = $title;
    }
  }

  return $output;
}

/**
 * Callback to provide administrative info. In this case we'll render the
 * content as long as it's not PHP, which is too risky to render here.
 */
function ctools_custom_content_type_admin_info($subtype, $conf) {
  $settings = ctools_custom_content_type_get_conf(ctools_custom_content_type_content_type($subtype), $conf);

  $block = new stdClass();
  $block->title = filter_xss_admin($settings['title']);
  // We don't want to render php output on preview here, because if something is
  // wrong the whole display will be borked. So we check to see if the php
  // evaluator filter is being used, and make a temporary change to the filter
  // so that we get the printed php, not the eval'ed php.
  $php_filter = FALSE;
  foreach (filter_list_format($settings['format']) as $filter) {
    if ($filter->module == 'php') {
      $php_filter = TRUE;
      break;
    }
  }
  // If a php filter is active, just print the source, but only if the current
  // user has access to the actual filter.
  if ($php_filter) {
    $filter = filter_format_load($settings['format']);
    if (!filter_access($filter)) {
      return NULL;
    }
    $block->content = '<pre>'. check_plain($settings['body']) .'</pre>';
  }
  else {
    // We also need to filter through XSS admin because <script> tags can
    // cause javascript which will interfere with our ajax.
    $block->content = filter_xss_admin(check_markup($settings['body'], $settings['format']));
  }
  return $block;
}

/**
 * Returns an edit form for the custom type.
 */
function ctools_custom_content_type_edit_form($form, &$form_state) {
  $settings = ctools_custom_content_type_get_conf($form_state['subtype'], $form_state['conf']);
  $form_state['settings'] = $settings;

  if ($settings['custom_type'] == 'fixed') {
    return; // no form for this case.
  }

  $form['admin_title'] = array(
    '#type' => 'textfield',
    '#default_value' => isset($settings['admin_title']) ? $settings['admin_title'] : '',
    '#title' => t('Administrative title'),
    '#description' => t('This title will be used administratively to identify this pane. If blank, the regular title will be used.'),
  );

  $form['title'] = array(
    '#type' => 'textfield',
    '#default_value' => $settings['title'],
    '#title' => t('Title'),
  );

    $form['body'] = array(
    '#type' => 'text_format',
    '#title' => t('Body'),
    '#default_value' => $settings['body'],
    '#format' => $settings['format'],
  );

  if (!empty($form_state['contexts'])) {
    // Set extended description if both CCK and Token modules are enabled, notifying of unlisted keywords
    if (module_exists('content') && module_exists('token')) {
      $description = t('If checked, context keywords will be substituted in this content. Note that CCK fields may be used as keywords using patterns like <em>%node:field_name-formatted</em>.');
    } elseif (!module_exists('token')) {
      $description = t('If checked, context keywords will be substituted in this content. More keywords will be available if you install the Token module, see http://drupal.org/project/token.');
    } else {
      $description = t('If checked, context keywords will be substituted in this content.');
    }

    $form['substitute'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use context keywords'),
      '#description' => $description,
      '#default_value' => !empty($settings['substitute']),
    );
    $form['contexts'] = array(
      '#title' => t('Substitutions'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $rows = array();
    foreach ($form_state['contexts'] as $context) {
      foreach (ctools_context_get_converters('%' . check_plain($context->keyword) . ':', $context) as $keyword => $title) {
        $rows[] = array(
          check_plain($keyword),
          t('@identifier: @title', array('@title' => $title, '@identifier' => $context->identifier)),
        );
      }
    }
    $header = array(t('Keyword'), t('Value'));
    $form['contexts']['context'] = array('#value' => theme('table', $header, $rows));
  }

  if (!user_access('administer custom content') || !module_exists('ctools_custom_content')) {
    return;
  }

  // Make the other form items dependent upon it.
  ctools_include('dependent');
  ctools_add_js('dependent');

  $form['reusable'] = array(
    '#type' => 'checkbox',
    '#title' => t('Make this content reusable'),
    '#default_value' => FALSE,
  );

  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Machine name'),
    '#description' => t('The machine readable name of this content. It must be unique, and it must contain only alphanumeric characters and underscores. Once created, you will not be able to change this value!'),
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('edit-reusable' => array(1)),
  );

  $form['category'] = array(
    '#type' => 'textfield',
    '#title' => t('Category'),
    '#description' => t('What category this content should appear in. If left blank the category will be "Miscellaneous".'),
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('edit-reusable' => array(1)),
  );

  $form['admin_description'] = array(
    '#type' => 'textarea',
    '#title' => t('Administrative description'),
    '#description' => t('A description of what this content is, does or is for, for administrative use.'),
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('edit-reusable' => array(1)),
  );
  return $form;
}

function _ctools_custom_content_type_edit_save(&$content, $form_state) {
  // Apply updates to the content object.
  $content->category = $form_state['values']['category'];
  $content->admin_title = $form_state['values']['admin_title'];
  $content->admin_description = $form_state['values']['admin_description'];
  foreach (array_keys($form_state['plugin']['defaults']) as $key) {
    if (isset($form_state['values'][$key])) {
      $content->settings[$key] = $form_state['values'][$key];
    }
  }

  ctools_export_crud_save('ctools_custom_content', $content);
}

/**
 * The validate form to ensure the custom content data is okay.
 */
function ctools_custom_content_type_edit_form_validate(&$form, &$form_state) {
  if ($form_state['settings']['custom_type'] != 'fixed' && !empty($form_state['values']['reusable'])) {
    if (empty($form_state['values']['name'])) {
      form_error($form['name'], t('Name is required.'));
    }

    // Check for string identifier sanity
    if (!preg_match('!^[a-z0-9_]+$!', $form_state['values']['name'])) {
      form_error($form['name'], t('The name can only consist of lowercase letters, underscores, and numbers.'));
      return;
    }

    // Check for name collision
    if ($form_state['values']['name'] == 'custom' || (ctools_export_crud_load('ctools_custom_content', $form_state['values']['name']))) {
      form_error($form['name'], t('Content with this name already exists. Please choose another name or delete the existing item before creating a new one.'));
    }
  }
}

/**
 * The submit form stores the data in $conf.
 */
function ctools_custom_content_type_edit_form_submit($form, &$form_state) {
  if ($form_state['settings']['custom_type'] == 'fixed') {
    _ctools_custom_content_type_edit_save($form_state['settings']['content'], $form_state);
  }
  // If the 'reusable' checkbox was checked, we will create a new
  // custom content and give it the proper values.
  else if (!empty($form_state['values']['reusable'])) {
    $content = ctools_export_crud_new('ctools_custom_content');
    $content->name = $form_state['values']['name'];
    _ctools_custom_content_type_edit_save($content, $form_state);
    $form_state['conf']['name'] = $content->name;
  }
  else {
    // Otherwise, just save values into $conf normally.
    // Because of changes in filter form, these two keys are out of position:
    $form_state['conf']['body'] = $form_state['values']['body']['value'];
    $form_state['conf']['format'] = $form_state['values']['body']['format'];

    foreach (array_keys($form_state['plugin']['defaults']) as $key) {
      if (isset($form_state['values'][$key]) && $key != 'body' && $key != 'format') {
        $form_state['conf'][$key] = $form_state['values'][$key];
      }
    }
  }
}
