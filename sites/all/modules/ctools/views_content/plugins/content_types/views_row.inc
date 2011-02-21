<?php
// $Id: views_row.inc,v 1.2 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Allow a view context to display individual rows.
 */

$plugin = array(
  'title' => t('View row'),
  'category' => t('View context'),
  'icon' => 'icon_views_page.png',
  'description' => t('Display one or more rows from a loaded view context.'),
  'required context' => new ctools_context_required(t('View'), 'view'),
  'defaults' => array(
    'rows' => array(),
    'use_fields' => array(),
    'fields' => array(),
  ),
  'add form' => array(
    'views_content_views_row_content_type_edit_form' => t('Select context'),
    'views_content_views_row_edit' => t('Configure rows'),
  ),
  'edit form' => array(
    'views_content_views_row_content_type_edit_form' => t('Select context'),
    'views_content_views_row_edit' => t('Configure rows'),
  ),
);

/**
 * Render the node_terms content type.
 */
function views_content_views_row_content_type_render($subtype, $conf, $panel_args, $context) {
  if (empty($context) || empty($context->data)) {
    return;
  }

  // Build the content type block.
  $block = new stdClass();
  $block->module  = 'views_row';
  $block->delta   = $context->argument;
  $block->title   = '';
  $block->content = '';

  // This guarantees the view is rendered normally which must happen.
  $view = views_content_context_get_view($context);
  $output = views_content_context_get_output($context);
  if (empty($conf['use_fields']) || empty($view->style_plugin->row_plugin)) {
    foreach ($conf['rows'] as $row) {
      // We store the row number 1-indexed but they are 0-indexed internally.
      $block->content .= $output['rows'][$row - 1];
    }
  }
  else {
    // If we're using specific fields, go through and poke the 'exclude' flag.
    foreach ($view->field as $id => $field) {
      $view->field[$id]->options['exclude'] = empty($conf['fields'][$id]);
    }

    // Rerender just the rows we need.
    foreach ($conf['rows'] as $row) {
      $view->row_index = $row - 1;
      if (!empty($view->result[$row - 1])) {
        $block->content .= $view->style_plugin->row_plugin->render($view->result[$row - 1]);
      }
    }
  }

  return $block;
}

function views_content_views_row_content_type_edit_form($form, &$form_state) {
  // This form does nothing; it exists to let the main form select the view context.
}

function views_content_views_row_content_type_edit_form_submit(&$form, &$form_state) {

}

function views_content_views_row_edit(&$form, &$form_state) {
  $conf = $form_state['conf'];

  if (empty($form_state['contexts'][$conf['context']])) {
    $form['markup'] = array('#value' => '<p>' . t('Invalid context selected.') . '</p>');
    return;
  }

  $view = views_content_context_get_view($form_state['contexts'][$conf['context']]);
  if (empty($view)) {
    $form['markup'] = array('#value' => '<p>' . t('Context contains an invalid view.') . '</p>');
    return;
  }

  // Support Views 2 and Views 3.
  if (method_exists($view, 'get_items_per_page')) {
    $view->init_pager();
    $rows = $view->get_items_per_page();
  }
  else {
    $rows = $view->display_handler->get_option('items_per_page');
  }
  if (empty($rows)) {
    $form['markup'] = array('#value' => '<p>' . t('The view must have a maximum number of items set to use this content type.') . '</p>');
    return;
  }

  foreach (range(1, $rows) as $row) {
    $options[$row] = t('Row @number', array('@number' => $row));
  }

  $form['rows'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Display'),
    '#options' => $options,
    '#default_value' => $conf['rows'],
  );

  if ($view->display_handler->uses_fields()) {
    $form['use_fields'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display specific fields'),
      '#default_value' => $conf['use_fields'],
    );

    ctools_include('dependent');
    $form['fields'] = array(
      '#type' => 'checkboxes',
      '#options' => $view->display_handler->get_field_labels(),
      '#default_value' => $conf['fields'],
      '#prefix' => '<div id="edit-fields-wrapper"><div id="edit-fields">',
      '#suffix' => '</div></div>',
      '#process' => array('ctools_dependent_process', 'expand_checkboxes'),
      '#dependency' => array('edit-use-fields' => array(TRUE)),
    );
  }
}

function views_content_views_row_edit_validate(&$form, &$form_state) {
  if (!array_filter($form_state['values']['rows'])) {
    form_error($form['rows'], t('You must select at least one row to display.'));
  }
}

function views_content_views_row_edit_submit(&$form, &$form_state) {
  $form_state['conf']['rows'] = array_filter($form_state['values']['rows']);
  $form_state['conf']['use_fields'] = $form_state['values']['use_fields'];
  $form_state['conf']['fields'] = array_filter($form_state['values']['fields']);
}

function views_content_views_row_content_type_admin_info($subtype, $conf, $contexts) {
  $context = $contexts[$conf['context']];
  $block->title = t('Row information');

  if (!empty($conf['use_fields'])) {
    $display_fields = array();
    $view = views_content_context_get_view($context);
    if (empty($view)) {
      $block->title = t('Broken view');
      return $block;
    }
    $fields = $view->display_handler->get_field_labels();

    foreach ($conf['fields'] as $field) {
      if (!empty($fields[$field])) {
        $display_fields[$field] = '"<em>' . check_plain($fields[$field]) . '</em>"';
      }
    }

    if ($display_fields) {
      $block->content = t('Displaying: !fields', array('!fields' => implode(', ', $display_fields)));
    }
    else {
      $block->content = t('Displaying no fields due to misconfiguration.');
    }
  }
  else {
    $block->content = t('Displaying the configured row.');
  }

  return $block;
}

function views_content_views_row_content_type_admin_title($subtype, $conf, $context) {
  $rows = array_filter($conf['rows']);
  return format_plural(count($rows),
    '"@context" row @rows',
    '"@context" rows @rows',
    array('@context' => $context->identifier, '@rows' => implode(', ', $rows))
  );
}
