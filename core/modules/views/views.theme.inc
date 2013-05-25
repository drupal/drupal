<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\Core\Template\Attribute;
use Drupal\views\ViewExecutable;

/**
 * Provide a full array of possible themes to try for a given hook.
 *
 * @param string $hook
 *   The hook to use. This is the base theme/template name.
 * @param \Drupal\views\ViewExecutable $view
 *   The view being rendered.
 * @param string|null $display
 *   The display being rendered, if applicable.
 */
function _views_theme_functions($hook, ViewExecutable $view, $display = NULL) {
  $themes = array();

  if ($display) {
    $themes[] = $hook . '__' . $view->storage->id() . '__' . $display['id'];
    $themes[] = $hook . '__' . $display['id'];
    // Add theme suggestions for each single tag.
    foreach (drupal_explode_tags($view->storage->get('tag')) as $tag) {
      $themes[] = $hook . '__' . preg_replace('/[^a-z0-9]/', '_', strtolower($tag));
    }

    if ($display['id'] != $display['display_plugin']) {
      $themes[] = $hook . '__' . $view->storage->id() . '__' . $display['display_plugin'];
      $themes[] = $hook . '__' . $display['display_plugin'];
    }
  }
  $themes[] = $hook . '__' . $view->storage->id();
  $themes[] = $hook;
  return $themes;
}

/**
 * Prepares variables for view templates.
 *
 * Default template: views-view.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: The ViewExecutable object.
 */
function template_preprocess_views_view(&$vars) {
  global $base_path;

  $view = $vars['view'];

  $vars['rows'] = (!empty($view->result) || $view->style_plugin->even_empty()) ? $view->style_plugin->render($view->result) : array();
  // Force a render array so CSS/JS can be added.
  if (!is_array($vars['rows'])) {
    $vars['rows'] = array('#markup' => $vars['rows']);
  }

  $vars['css_name'] = drupal_clean_css_identifier($view->storage->id());
  $vars['id'] = $view->storage->id();
  $vars['display_id'] = $view->current_display;

  // Basic classes.
  $vars['css_class'] = '';

  $vars['attributes']['class'] = array();
  $vars['attributes']['class'][] = 'view';
  $vars['attributes']['class'][] = 'view-' . drupal_clean_css_identifier($vars['id']);
  $vars['attributes']['class'][] = 'view-id-' . $vars['id'];
  $vars['attributes']['class'][] = 'view-display-id-' . $vars['display_id'];

  $css_class = $view->display_handler->getOption('css_class');
  if (!empty($css_class)) {
    $vars['css_class'] = preg_replace('/[^a-zA-Z0-9- ]/', '-', $css_class);
    $vars['attributes']['class'][] = $vars['css_class'];
  }

  $empty = empty($view->result);
  $vars['header'] = $view->display_handler->renderArea('header', $empty);
  $vars['footer'] = $view->display_handler->renderArea('footer', $empty);
  $vars['empty'] = $empty ? $view->display_handler->renderArea('empty', $empty) : FALSE;

  $vars['exposed']    = !empty($view->exposed_widgets) ? $view->exposed_widgets : '';
  $vars['more']       = $view->display_handler->renderMoreLink();
  $vars['feed_icon']  = !empty($view->feed_icon) ? $view->feed_icon : '';

  $vars['pager']      = '';

  // @todo: Figure out whether this belongs into views_ui_preprocess_views_view.
  // Render title for the admin preview.
  $vars['title'] = !empty($view->views_ui_context) ? filter_xss_admin($view->getTitle()) : '';

  if ($view->display_handler->renderPager()) {
    $exposed_input = isset($view->exposed_raw_input) ? $view->exposed_raw_input : NULL;
    $vars['pager'] = $view->renderPager($exposed_input);
  }

  if (!empty($view->attachment_before)) {
    $vars['attachment_before'] = $view->attachment_before;
  }
  else {
    $vars['attachment_before'] = array();
  }
  if (!empty($view->attachment_after)) {
    $vars['attachment_after'] = $view->attachment_after;
  }
  else {
    $vars['attachment_after'] = array();
  }

  // Add contextual links to the view. We need to attach them to the dummy
  // $view_array variable, since contextual_preprocess() requires that they be
  // attached to an array (not an object) in order to process them. For our
  // purposes, it doesn't matter what we attach them to, since once they are
  // processed by contextual_preprocess() they will appear in the $title_suffix
  // variable (which we will then render in views-view.html.twig).
  views_add_contextual_links($vars['view_array'], 'view', $view, $view->current_display);

  // Attachments are always updated with the outer view, never by themselves,
  // so they do not have dom ids.
  if (empty($view->is_attachment)) {
    // Our JavaScript needs to have some means to find the HTML belonging to
    // this view.
    //
    // It is true that the DIV wrapper has classes denoting the name of the view
    // and its display ID, but this is not enough to unequivocally match a view
    // with its HTML, because one view may appear several times on the page. So
    // we set up a hash with the current time, $dom_id, to issue a "unique"
    // identifier for each view. This identifier is written to both
    // Drupal.settings and the DIV wrapper.
    $vars['dom_id'] = $view->dom_id;
    $vars['attributes']['class'][] = 'view-dom-id-' . $vars['dom_id'];
  }

  // If using AJAX, send identifying data about this view.
  if ($view->ajaxEnabled() && empty($view->is_attachment) && empty($view->live_preview)) {
    $settings = array(
      'views' => array(
        'ajax_path' => url('views/ajax'),
        'ajaxViews' => array(
          'views_dom_id:' . $vars['dom_id'] => array(
            'view_name' => $view->storage->id(),
            'view_display_id' => $view->current_display,
            'view_args' => check_plain(implode('/', $view->args)),
            'view_path' => check_plain(current_path()),
            'view_base_path' => $view->getPath(),
            'view_dom_id' => $vars['dom_id'],
            // To fit multiple views on a page, the programmer may have
            // overridden the display's pager_element.
            'pager_element' => isset($view->pager) ? $view->pager->get_pager_id() : 0,
          ),
        ),
      ),
    );
    $view->element['#attached']['js'][] = array('type' => 'setting', 'data' => $settings);
    $view->element['#attached']['library'][] = array('views', 'views.ajax');
  }

  // If form fields were found in the view, reformat the view output as a form.
  if (views_view_has_form_elements($view)) {
    // Copy the rows so as not to modify them by reference when rendering.
    $rows = $vars['rows'];
    $rows = drupal_render($rows);
    $output = !empty($rows) ? $rows : $vars['empty'];
    $form = drupal_get_form(views_form_id($view), $view, $output);
    // The form is requesting that all non-essential views elements be hidden,
    // usually because the rendered step is not a view result.
    if ($form['show_view_elements']['#value'] == FALSE) {
      $vars['header'] = '';
      $vars['exposed'] = '';
      $vars['pager'] = '';
      $vars['footer'] = '';
      $vars['more'] = '';
      $vars['feed_icon'] = '';
    }
    $vars['rows'] = $form;
  }
}

/**
 * Prepares variables for views fields templates.
 *
 * Default template: views-view-fields.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: The view object.
 *   - options: An array of options. Each option contains:
 *     - inline: An array that contains the fields that are to be
 *       displayed inline.
 *     - default_field_elements: If default field wrapper
 *       elements are to be provided.
 *     - hide_empty: Whether the field is to be hidden if empty.
 *     - element_default_classes: If the default classes are to be added.
 *     - separator: A string to be placed between inline fields to keep them
 *       visually distinct.
 *    - row: An array containing information about the current row.
 */
function template_preprocess_views_view_fields(&$vars) {
  $view = $vars['view'];

  // Loop through the fields for this view.
  $previous_inline = FALSE;
  $vars['fields'] = array(); // ensure it's at least an empty array.
  foreach ($view->field as $id => $field) {
    // render this even if set to exclude so it can be used elsewhere.
    $field_output = $view->style_plugin->get_field($view->row_index, $id);
    $empty = $field->is_value_empty($field_output, $field->options['empty_zero']);
    if (empty($field->options['exclude']) && (!$empty || (empty($field->options['hide_empty']) && empty($vars['options']['hide_empty'])))) {
      $object = new stdClass();
      $object->handler = &$view->field[$id];
      $object->inline = !empty($vars['options']['inline'][$id]);

      $object->element_type = $object->handler->element_type(TRUE, !$vars['options']['default_field_elements'], $object->inline);
      if ($object->element_type) {
        $attributes = array();
        if ($object->handler->options['element_default_classes']) {
          $attributes['class'][] = 'field-content';
        }

        if ($classes = $object->handler->element_classes($view->row_index)) {
          $attributes['class'][] = $classes;
        }
        $attributes = new Attribute($attributes);

        $pre = '<' . $object->element_type;
        $pre .= $attributes;
        $field_output = $pre . '>' . $field_output . '</' . $object->element_type . '>';
      }

      // Protect ourself somewhat for backward compatibility. This will prevent
      // old templates from producing invalid HTML when no element type is selected.
      if (empty($object->element_type)) {
        $object->element_type = 'span';
      }

      $object->content = $field_output;
      if (isset($view->field[$id]->field_alias) && isset($vars['row']->{$view->field[$id]->field_alias})) {
        $object->raw = $vars['row']->{$view->field[$id]->field_alias};
      }
      else {
        $object->raw = NULL; // make sure it exists to reduce NOTICE
      }

      if (!empty($vars['options']['separator']) && $previous_inline && $object->inline && $object->content) {
        $object->separator = filter_xss_admin($vars['options']['separator']);
      }

      $object->class = drupal_clean_css_identifier($id);

      $previous_inline = $object->inline;
      $object->inline_html = $object->handler->element_wrapper_type(TRUE, TRUE);
      if ($object->inline_html === '' && $vars['options']['default_field_elements']) {
        $object->inline_html = $object->inline ? 'span' : 'div';
      }

      // Set up the wrapper HTML.
      $object->wrapper_prefix = '';
      $object->wrapper_suffix = '';

      if ($object->inline_html) {
        $attributes = array();
        if ($object->handler->options['element_default_classes']) {
          $attributes['class'][] = 'views-field';
          $attributes['class'][] = 'views-field-' . $object->class;
        }

        if ($classes = $object->handler->element_wrapper_classes($view->row_index)) {
          $attributes['class'][] = $classes;
        }
        $attributes = new Attribute($attributes);

        $object->wrapper_prefix = '<' . $object->inline_html;
        $object->wrapper_prefix .= $attributes;
        $object->wrapper_prefix .= '>';
        $object->wrapper_suffix = '</' . $object->inline_html . '>';
      }

      // Set up the label for the value and the HTML to make it easier
      // on the template.
      $object->label = check_plain($view->field[$id]->label());
      $object->label_html = '';
      if ($object->label) {
        $object->label_html .= $object->label;
        if ($object->handler->options['element_label_colon']) {
          $object->label_html .= ': ';
        }

        $object->element_label_type = $object->handler->element_label_type(TRUE, !$vars['options']['default_field_elements']);
        if ($object->element_label_type) {
          $attributes = array();
          if ($object->handler->options['element_default_classes']) {
            $attributes['class'][] = 'views-label';
            $attributes['class'][] = 'views-label-' . $object->class;
          }

          $element_label_class = $object->handler->element_label_classes($view->row_index);
          if ($element_label_class) {
            $attributes['class'][] = $element_label_class;
          }
          $attributes = new Attribute($attributes);

          $pre = '<' . $object->element_label_type;
          $pre .= $attributes;
          $pre .= '>';

          $object->label_html = $pre . $object->label_html . '</' . $object->element_label_type . '>';
        }
      }

      $vars['fields'][$id] = $object;
    }
  }

}

/**
* Returns HTML for multiple views fields.
*
* @param $variables
*   An associative array containing:
*   - fields: An array of field objects. Each field object contains:
*     - separator: A string that separates the fields.
*     - wrapper_suffix: A string added to the beginning of the fields.
*     - label_html: An HTML string that labels the fields.
*     - content: The fields.
*     - wrapper_suffix: A string added to the end of the fields.
*
* @see template_preprocess_views_view_fields()
*/
function theme_views_view_fields($variables) {
  $fields = $variables['fields'];
  $output = '';

  foreach ($fields as $id => $field) {
    if (!empty($field->separator)) {
      $output .= $field->separator;
    }

    $output .= $field->wrapper_prefix;
    $output .= $field->label_html;
    $output .= $field->content;

    $output .= $field->wrapper_suffix;
  }

  return $output;
}

/**
 * Prepares variables for views single grouping templates.
 *
 * Default template: views-view-grouping.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: The view object.
 *   - rows: The rows returned from the view.
 *   - grouping_level: Integer indicating the hierarchical level of the
 *     grouping.
 *   - content: The content to be grouped.
 *   - title: The group heading.
  */
function template_preprocess_views_view_grouping(&$vars) {
  $vars['content'] = $vars['view']->style_plugin->render_grouping_sets($vars['rows'], $vars['grouping_level']);
}

/**
 * Display a single views field.
 *
 * Interesting bits of info:
 * $field->field_alias says what the raw value in $row will be. Reach it like
 * this: @code { $row->{$field->field_alias} @endcode
 */
function theme_views_view_field($vars) {
  $view = $vars['view'];
  $field = $vars['field'];
  $row = $vars['row'];
  return $vars['output'];
}

/**
 * Prepares variables for views field templates.
 *
 * Default template: views-view-field.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - field: The field handler object for the current field.
 *   - row: Object representing the raw result of the SQL query for the current
 *     field.
 *   - view: Instance of the ViewExecutable object for the parent view.
 */
function template_preprocess_views_view_field(&$vars) {
  $vars['output'] = $vars['field']->advanced_render($vars['row']);
}

/**
 * Prepares variables for views summary templates.
 *
 * The summary prints a single record from a row, with fields.
 *
 * Default template: views-view-summary.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_view_summary(&$vars) {
  $view = $vars['view'];
  $argument = $view->argument[$view->build_info['summary_level']];
  $vars['row_classes'] = array();

  $url_options = array();

  if (!empty($view->exposed_raw_input)) {
    $url_options['query'] = $view->exposed_raw_input;
  }

  $active_urls = drupal_map_assoc(array(
    url(current_path(), array('alias' => TRUE)), // force system path
    url(current_path()), // could be an alias
  ));

  // Collect all arguments foreach row, to be able to alter them for example
  // by the validator. This is not done per single argument value, because this
  // could cause performance problems.
  $row_args = array();

  foreach ($vars['rows'] as $id => $row) {
    $row_args[$id] = $argument->summary_argument($row);
  }
  $argument->process_summary_arguments($row_args);

  foreach ($vars['rows'] as $id => $row) {
    $vars['rows'][$id]->link = $argument->summary_name($row);
    $args = $view->args;
    $args[$argument->position] = $row_args[$id];

    $base_path = NULL;
    if (!empty($argument->options['summary_options']['base_path'])) {
      $base_path = $argument->options['summary_options']['base_path'];
    }
    $vars['rows'][$id]->url = url($view->getUrl($args, $base_path), $url_options);
    $vars['rows'][$id]->count = intval($row->{$argument->count_alias});
    $vars['row_classes'][$id] = array();
    if (isset($active_urls[$vars['rows'][$id]->url])) {
      $vars['row_classes'][$id]['class'][] = 'active';
    }
    $vars['row_classes'][$id] = new Attribute($vars['row_classes'][$id]);
  }
}

/**
 * Prepares variables for unformatted summary view templates.
 *
 * Default template: views-view-summary-unformatted.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 *   - options: An array of options. Each option contains:
 *     - separator: A string to be placed between inline fields to keep them
 *       visually distinct.
 */
function template_preprocess_views_view_summary_unformatted(&$vars) {
  $view = $vars['view'];
  $argument = $view->argument[$view->build_info['summary_level']];
  $vars['row_classes'] = array();

  $url_options = array();

  if (!empty($view->exposed_raw_input)) {
    $url_options['query'] = $view->exposed_raw_input;
  }

  $count = 0;
  $active_urls = drupal_map_assoc(array(
    // Force system path.
    url(current_path(), array('alias' => TRUE)),
    // Could be an alias.
    url(current_path()),
  ));

  // Collect all arguments for each row, to be able to alter them for example
  // by the validator. This is not done per single argument value, because
  // this could cause performance problems.
  $row_args = array();
  foreach ($vars['rows'] as $id => $row) {
    $row_args[$id] = $argument->summary_argument($row);
  }
  $argument->process_summary_arguments($row_args);

  foreach ($vars['rows'] as $id => $row) {
    // Only false on first time.
    if ($count++) {
      $vars['rows'][$id]->separator = filter_xss_admin($vars['options']['separator']);
    }
    $vars['rows'][$id]->link = $argument->summary_name($row);
    $args = $view->args;
    $args[$argument->position] = $row_args[$id];

    $base_path = NULL;
    if (!empty($argument->options['summary_options']['base_path'])) {
      $base_path = $argument->options['summary_options']['base_path'];
    }
    $vars['rows'][$id]->url = url($view->getUrl($args, $base_path), $url_options);
    $vars['rows'][$id]->count = intval($row->{$argument->count_alias});
    $vars['row_classes'][$id] = array();
    if (isset($active_urls[$vars['rows'][$id]->url])) {
      $vars['row_classes'][$id]['class'][] = 'active';
    }
    $vars['row_classes'][$id] = new Attribute($vars['row_classes'][$id]);
  }
}

/**
 * Prepares variables for views table templates.
 *
 * Default template: views-view-table.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_view_table(&$vars) {
  $view = $vars['view'];

  // We need the raw data for this grouping, which is passed in
  // as $vars['rows'].
  // However, the template also needs to use for the rendered fields.  We
  // therefore swap the raw data out to a new variable and reset $vars['rows']
  // so that it can get rebuilt.
  // Store rows so that they may be used by further preprocess functions.
  $result = $vars['result'] = $vars['rows'];
  $vars['rows'] = array();
  $vars['field_classes'] = array();
  $vars['header'] = array();

  $options = $view->style_plugin->options;
  $handler = $view->style_plugin;

  $default_row_class = isset($options['default_row_class']) ? $options['default_row_class'] : TRUE;
  $row_class_special = isset($options['row_class_special']) ? $options['row_class_special'] : TRUE;

  $fields = &$view->field;
  $columns = $handler->sanitize_columns($options['columns'], $fields);

  $active = !empty($handler->active) ? $handler->active : '';
  $order = !empty($handler->order) ? $handler->order : 'asc';

  // A boolean variable which stores whether the table has a responsive class.
  $responsive = FALSE;

  $query = tablesort_get_query_parameters();
  if (isset($view->exposed_raw_input)) {
    $query += $view->exposed_raw_input;
  }

  foreach ($columns as $field => $column) {
    // Create a second variable so we can easily find what fields we have and
    // what the CSS classes should be.
    $vars['fields'][$field] = drupal_clean_css_identifier($field);
    if ($active == $field) {
      $vars['fields'][$field] .= ' active';
    }

    // render the header labels
    if ($field == $column && empty($fields[$field]->options['exclude'])) {
      $label = check_plain(!empty($fields[$field]) ? $fields[$field]->label() : '');
      if (empty($options['info'][$field]['sortable']) || !$fields[$field]->click_sortable()) {
        $vars['header'][$field] = $label;
      }
      else {
        $initial = !empty($options['info'][$field]['default_sort_order']) ? $options['info'][$field]['default_sort_order'] : 'asc';

        if ($active == $field) {
          $initial = ($order == 'asc') ? 'desc' : 'asc';
        }

        $title = t('sort by @s', array('@s' => $label));
        if ($active == $field) {
          $label .= theme('tablesort_indicator', array('style' => $initial));
        }

        $query['order'] = $field;
        $query['sort'] = $initial;
        $link_options = array(
          'html' => TRUE,
          'attributes' => array('title' => $title),
          'query' => $query,
        );
        $vars['header'][$field] = l($label, current_path(), $link_options);
      }

      // Set up the header label class.
      $vars['header_classes'][$field] = array();
      if ($fields[$field]->options['element_default_classes']) {
        $vars['header_classes'][$field]['class'][] = 'views-field';
        $vars['header_classes'][$field]['class'][] = 'views-field-' . $vars['fields'][$field];
      }
      $vars['header_classes'][$field] = new Attribute($vars['header_classes'][$field]);
      $class = $fields[$field]->element_label_classes(0);
      if ($class) {
        $vars['header_classes'][$field]['class'][] = $class;
      }
      // Add responsive header classes.
      if (!empty($options['info'][$field]['responsive'])) {
        $vars['header_classes'][$field]['class'][] = $options['info'][$field]['responsive'];
        $responsive = TRUE;
      }
      // Add a CSS align class to each field if one was set.
      if (!empty($options['info'][$field]['align'])) {
        $vars['header_classes'][$field]['class'][] = drupal_clean_css_identifier($options['info'][$field]['align']);
      }
      // Add a header label wrapper if one was selected.
      if ($vars['header'][$field]) {
        $element_label_type = $fields[$field]->element_label_type(TRUE, TRUE);
        if ($element_label_type) {
          $vars['header'][$field] = '<' . $element_label_type . '>' . $vars['header'][$field] . '</' . $element_label_type . '>';
        }
      }
    }

    // Add a CSS align class to each field if one was set.
    if (!empty($options['info'][$field]['align'])) {
      $vars['fields'][$field] .= ' ' . drupal_clean_css_identifier($options['info'][$field]['align']);
    }

    // Render each field into its appropriate column.
    foreach ($result as $num => $row) {
      // Add field classes.
      $vars['field_classes'][$field][$num] = array();
      if ($fields[$field]->options['element_default_classes']) {
        $vars['field_classes'][$field][$num]['class'][] = 'views-field';
        $vars['field_classes'][$field][$num]['class'][] = 'views-field-' . $vars['fields'][$field];
      }
      $vars['field_classes'][$field][$num] = new Attribute($vars['field_classes'][$field][$num]);

      if ($classes = $fields[$field]->element_classes($num)) {
        $vars['field_classes'][$field][$num]['class'][] = $classes;
      }
      // Add responsive header classes.
      if (!empty($options['info'][$field]['responsive'])) {
        $vars['field_classes'][$field][$num]['class'][] = $options['info'][$field]['responsive'];
      }

      if (!empty($fields[$field]) && empty($fields[$field]->options['exclude'])) {
        $field_output = $handler->get_field($num, $field);
        $element_type = $fields[$field]->element_type(TRUE, TRUE);
        if ($element_type) {
          $field_output = '<' . $element_type . '>' . $field_output . '</' . $element_type . '>';
        }

        // Don't bother with separators and stuff if the field does not show up.
        if (empty($field_output) && !empty($vars['rows'][$num][$column])) {
          continue;
        }

        // Place the field into the column, along with an optional separator.
        if (!empty($vars['rows'][$num][$column])) {
          if (!empty($options['info'][$column]['separator'])) {
            $vars['rows'][$num][$column] .= filter_xss_admin($options['info'][$column]['separator']);
          }
        }
        else {
          $vars['rows'][$num][$column] = '';
        }
        $vars['rows'][$num][$column] .= $field_output;
      }
    }

    // Remove columns if the option is hide empty column is checked and the
    // field is not empty.
    if (!empty($options['info'][$field]['empty_column'])) {
      $empty = TRUE;
      foreach ($vars['rows'] as $num => $columns) {
        $empty &= empty($columns[$column]);
      }
      if ($empty) {
        foreach ($vars['rows'] as $num => &$column_items) {
          unset($column_items[$column]);
          unset($vars['header'][$column]);
        }
      }
    }
  }

  // Hide table header if all labels are empty.
  if (!array_filter($vars['header'])) {
    $vars['header'] = array();
  }

  $count = 0;
  $vars['row_classes'] = array();
  foreach ($vars['rows'] as $num => $row) {
    $vars['row_classes'][$num] = array();
    if ($row_class_special) {
      $vars['row_classes'][$num]['class'][] = ($count++ % 2 == 0) ? 'odd' : 'even';
      if ($num === 0) {
        $vars['row_classes'][$num]['class'][] = 'views-row-first';
      }
      elseif ($num === (count($vars['rows']) - 1)) {
        $vars['row_classes'][$num]['class'][] = 'views-row-last';
      }
    }
    if ($row_class = $handler->get_row_class($num)) {
      $vars['row_classes'][$num]['class'][] = $row_class;
    }
    $vars['row_classes'][$num] = new Attribute($vars['row_classes'][$num]);
  }

  $vars['attributes']['class'][] = 'views-table';
  $vars['attributes']['class'][] = 'views-view-table';
  if (empty($vars['rows']) && !empty($options['empty_table'])) {
    $build = $view->display_handler->renderArea('empty');
    $vars['rows'][0][0] = drupal_render($build);
    $vars['row_classes'][0] = new Attribute();
    // Calculate the amounts of rows with output.
    $vars['field_classes'][0][0] = new Attribute(array(
      'colspan' => count($vars['header']),
      'class' => 'views-empty',
    ));
  }

  if (!empty($options['sticky'])) {
    $vars['view']->element['#attached']['library'][] = array('system', 'drupal.tableheader');
    $vars['attributes']['class'][] = "sticky-enabled";
  }
  $vars['attributes']['class'][] = 'cols-' . count($vars['header']);

  if (!empty($handler->options['summary'])) {
    $vars['attributes']['summary'] = $handler->options['summary'];
  }
  // If the table has headers and it should react responsively to columns hidden
  // with the classes represented by the constants RESPONSIVE_PRIORITY_MEDIUM
  // and RESPONSIVE_PRIORITY_LOW, add the tableresponsive behaviors.
  if (count($vars['header']) && $responsive) {
    $vars['view']->element['#attached']['library'][] = array('system', 'drupal.tableresponsive');
    // Add 'responsive-enabled' class to the table to identify it for JS.
    // This is needed to target tables constructed by this function.
    $vars['attributes']['class'][] = 'responsive-enabled';
  }
}

/**
 * Prepares variables for views grid style templates.
 *
 * Default template: views-view-grid.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: The view object.
 *   - rows: An array of row items. Each row is an array of content.
 */
function template_preprocess_views_view_grid(&$vars) {
  $view = $vars['view'];
  $options = $view->style_plugin->options;
  $handler = $view->style_plugin;
  $default_row_class = isset($options['default_row_class']) ? $options['default_row_class'] : TRUE;
  $row_class_special = isset($options['row_class_special']) ? $options['row_class_special'] : TRUE;

  $columns = $options['columns'];
  $vars['attributes']['class'][] = 'views-view-grid cols-' . $columns;

  $rows = array();
  $row_indexes = array();

  if ($options['alignment'] == 'horizontal') {
    $row = array();
    $col_count = 0;
    $row_count = 0;
    $count = 0;
    foreach ($vars['rows'] as $row_index => $item) {
      $count++;
      $row[] = $item;
      $row_indexes[$row_count][$col_count] = $row_index;
      $col_count++;
      if ($count % $columns == 0) {
        $rows[] = $row;
        $row = array();
        $col_count = 0;
        $row_count++;
      }
    }
    if ($row) {
      // Fill up the last line only if it's configured, but this is default.
      if (!empty($handler->options['fill_single_line']) && count($rows)) {
        for ($i = 0; $i < ($columns - $col_count); $i++) {
          $row[] = '';
        }
      }
      $rows[] = $row;
    }
  }
  else {
    $num_rows = floor(count($vars['rows']) / $columns);
    // The remainders are the 'odd' columns that are slightly longer.
    $remainders = count($vars['rows']) % $columns;
    $row = 0;
    $col = 0;
    foreach ($vars['rows'] as $count => $item) {
      $rows[$row][$col] = $item;
      $row_indexes[$row][$col] = $count;
      $row++;

      if (!$remainders && $row == $num_rows) {
        $row = 0;
        $col++;
      }
      elseif ($remainders && $row == $num_rows + 1) {
        $row = 0;
        $col++;
        $remainders--;
      }
    }
    for ($i = 0; $i < count($rows[0]); $i++) {
      // This should be a string so this is ok.
      if (!isset($rows[count($rows) - 1][$i])) {
        $rows[count($rows) - 1][$i] = '';
      }
    }
  }

  // Apply the row classes.
  foreach ($rows as $row_number => $row) {
    $row_classes = array();
    if ($default_row_class) {
      $row_classes['class'][] = 'row-' . ($row_number + 1);
    }
    if ($row_class_special) {
      if ($row_number == 0) {
        $row_classes['class'][] = 'row-first';
      }
      if (count($rows) == ($row_number + 1)) {
        $row_classes['class'][] = 'row-last';
      }
    }
    $row_classes = new Attribute($row_classes);

    foreach ($rows[$row_number] as $column_number => $item) {
      $vars['column_classes'][$row_number][$column_number] = array();
      if ($default_row_class) {
        $vars['column_classes'][$row_number][$column_number]['class'][] = 'col-' . ($column_number + 1);
      }
      if ($row_class_special) {
        if ($column_number == 0) {
          $vars['column_classes'][$row_number][$column_number]['class'][] = 'col-first';
        }
        elseif (count($rows[$row_number]) == ($column_number + 1)) {
          $vars['column_classes'][$row_number][$column_number]['class'][] = 'col-last';
        }
      }
      if (isset($row_indexes[$row_number][$column_number]) && $column_class = $view->style_plugin->get_row_class($row_indexes[$row_number][$column_number])) {
        $vars['column_classes'][$row_number][$column_number]['class'][] = $column_class;
      }
      $vars['column_classes'][$row_number][$column_number] = new Attribute($vars['column_classes'][$row_number][$column_number]);
    }
  }
  $vars['rows'] = $rows;
  if (!empty($handler->options['summary'])) {
    $vars['attributes']['summary'] = $handler->options['summary'];
  }
}

/**
 * Prepares variables for views unformatted rows templates.
 *
 * Default template: views-view-unformatted.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: The view object.
 *   - rows: An array of row items. Each row is an array of content.
 */
function template_preprocess_views_view_unformatted(&$vars) {
  $view = $vars['view'];
  $rows = $vars['rows'];
  $style = $view->style_plugin;
  $options = $style->options;

  $vars['row_classes'] = array();
  $vars['classes'] = array();
  $default_row_class = isset($options['default_row_class']) ? $options['default_row_class'] : FALSE;
  $row_class_special = isset($options['row_class_special']) ? $options['row_class_special'] : FALSE;
  // Set up striping values.
  $count = 0;
  $max = count($rows);
  foreach ($rows as $id => $row) {
    $vars['row_classes'][$id] = array();
    $count++;
    if ($default_row_class) {
      $vars['row_classes'][$id]['class'][] = 'views-row';
      $vars['row_classes'][$id]['class'][] = 'views-row-' . $count;
    }
    if ($row_class_special) {
      $vars['row_classes'][$id]['class'][] = 'views-row-' . ($count % 2 ? 'odd' : 'even');
      if ($count == 1) {
        $vars['row_classes'][$id]['class'][] = 'views-row-first';
      }
      if ($count == $max) {
        $vars['row_classes'][$id]['class'][] = 'views-row-last';
      }
    }

    if ($row_class = $view->style_plugin->get_row_class($id)) {
      $vars['row_classes'][$id]['class'][] = $row_class;
    }
    $vars['row_classes'][$id] = new Attribute($vars['row_classes'][$id]);
  }
}

/**
 * Prepares variables for Views HTML list templates.
 *
 * Default template: views-view-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: A View object.
 */
function template_preprocess_views_view_list(&$variables) {
  $handler  = $variables['view']->style_plugin;

  // Fetch classes from handler options.
  $class = explode(' ', $handler->options['class']);
  $class = array_map('drupal_clean_css_identifier', $class);

  // Fetch wrapper classes from handler options.
  $wrapper_class = explode(' ', $handler->options['wrapper_class']);
  $wrapper_class = array_map('drupal_clean_css_identifier', $wrapper_class);

  // Initialize a new attribute class for $wrapper_class.
  if ($wrapper_class) {
    $variables['wrapper_attributes'] = new Attribute(array('class' => $wrapper_class));
  }

  // Initialize a new attribute class for $class.
  $variables['list']['attributes'] = new Attribute(array('class' => $class));
  $variables['list']['type'] = $handler->options['type'];

  template_preprocess_views_view_unformatted($variables);
}

/**
 * Prepares variables for RSS feed templates.
 *
 * Default template: views-view-rss.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
*/
function template_preprocess_views_view_rss(&$vars) {
  global $base_url;

  $view = $vars['view'];
  $items = $vars['rows'];

  $style = $view->style_plugin;

  $config = config('system.site');

  // The RSS 2.0 "spec" doesn't indicate HTML can be used in the description.
  // We strip all HTML tags, but need to prevent double encoding from properly
  // escaped source data (such as &amp becoming &amp;amp;).
  $vars['description'] = check_plain(decode_entities(strip_tags($style->get_description())));

  if ($view->display_handler->getOption('sitename_title')) {
    $title = $config->get('name');
    if ($slogan = $config->get('slogan')) {
      $title .= ' - ' . $slogan;
    }
  }
  else {
    $title = $view->getTitle();
  }
  $vars['title'] = check_plain($title);

  // Figure out which display which has a path we're using for this feed. If
  // there isn't one, use the global $base_url
  $link_display_id = $view->display_handler->getLinkDisplay();
  if ($link_display_id && $display = $view->displayHandlers->get($link_display_id)) {
    $path = $view->displayHandlers->get($link_display_id)->getPath();
  }

  if ($path) {
    $path = $view->getUrl(NULL, $path);
    $url_options = array('absolute' => TRUE);
    if (!empty($view->exposed_raw_input)) {
      $url_options['query'] = $view->exposed_raw_input;
    }

    // Compare the link to the default home page; if it's the default home page,
    // just use $base_url.
    if ($path == $config->get('page.front')) {
      $path = '';
    }

    $vars['link'] = check_url(url($path, $url_options));
  }

  $vars['langcode'] = check_plain(language(LANGUAGE_TYPE_INTERFACE)->langcode);
  $vars['namespaces'] = new Attribute($style->namespaces);
  $vars['items'] = $items;
  $vars['channel_elements'] = format_xml_elements($style->channel_elements);

  // During live preview we don't want to output the header since the contents
  // of the feed are being displayed inside a normal HTML page.
  if (empty($vars['view']->live_preview)) {
    $vars['view']->getResponse()->headers->set('Content-Type', 'application/rss+xml; charset=utf-8');
  }
}

/**
 * Prepares variables for views RSS item templates.
 *
 * Default template: views-view-row-rss.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - row: The raw results rows.
 */
function template_preprocess_views_view_row_rss(&$vars) {
  $item = $vars['row'];

  $vars['title'] = check_plain($item->title);
  $vars['link'] = check_url($item->link);
  $vars['description'] = check_plain($item->description);
  $vars['item_elements'] = empty($item->elements) ? '' : format_xml_elements($item->elements);
}

/**
 * Prepares variables for views exposed form templates.
 *
 * Default template: views-exposed-form.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - form: A render element representing the form.
 */
function template_preprocess_views_exposed_form(&$vars) {
  $form = &$vars['form'];

  // Put all single checkboxes together in the last spot.
  $checkboxes = array();

  if (!empty($form['q'])) {
    $vars['q'] = $form['q'];
  }

  $vars['widgets'] = array();
  foreach ($form['#info'] as $id => $info) {
    // Set aside checkboxes.
    if (isset($form[$info['value']]['#type']) && $form[$info['value']]['#type'] == 'checkbox') {
      $checkboxes[] = $form[$info['value']];
      continue;
    }
    $widget = new stdClass();
    // set up defaults so that there's always something there.
    $widget->label = $widget->operator = $widget->widget = $widget->description = '';

    $widget->id = isset($form[$info['value']]['#id']) ? $form[$info['value']]['#id'] : '';

    if (!empty($info['label'])) {
      $widget->label = check_plain($info['label']);
    }
    if (!empty($info['operator']) && isset($form[$info['operator']])) {
      $widget->operator = $form[$info['operator']];
    }

    $widget->widget = $form[$info['value']];

    if (!empty($info['description'])) {
      $widget->description = check_plain($info['description']);
    }

    $vars['widgets'][$id] = $widget;
    // Unset the widget, so that it doesn't get rendered twice.
    unset($form[$info['value']]);
  }

  // Wrap up all the checkboxes we set aside into a widget.
  if (!empty($checkboxes)) {
    $widget = new stdClass();
    // set up defaults so that there's always something there.
    $widget->label = $widget->operator = $widget->widget = NULL;
    $widget->id = 'checkboxes';
    $widget->widget = $checkboxes;
    $vars['widgets']['checkboxes'] = $widget;
  }
}

/**
 * Theme function for a View with form elements: replace the placeholders.
 */
function theme_views_form_views_form($variables) {
  $form = $variables['form'];

  // Placeholders and their substitutions (usually rendered form elements).
  $search = array();
  $replace = array();

  // Add in substitutions provided by the form.
  foreach ($form['#substitutions']['#value'] as $substitution) {
    $field_name = $substitution['field_name'];
    $row_id = $substitution['row_id'];

    $search[] = $substitution['placeholder'];
    $replace[] = isset($form[$field_name][$row_id]) ? drupal_render($form[$field_name][$row_id]) : '';
  }
  // Add in substitutions from hook_views_form_substitutions().
  $substitutions = Drupal::moduleHandler()->invokeAll('views_form_substitutions');
  foreach ($substitutions as $placeholder => $substitution) {
    $search[] = $placeholder;
    $replace[] = $substitution;
  }

  // Apply substitutions to the rendered output.
  $form['output']['#markup'] = str_replace($search, $replace, $form['output']['#markup']);

  // Render and add remaining form fields.
  return drupal_render_children($form);
}

function theme_views_mini_pager($vars) {
  global $pager_page_array, $pager_total;

  $tags = $vars['tags'];
  $element = $vars['element'];
  $parameters = $vars['parameters'];

  // Current is the page we are currently paged to.
  $pager_current = $pager_page_array[$element] + 1;
  // End of marker calculations.

  $li_previous = array();
  if ($pager_total[$element] > 1 && $pager_page_array[$element] > 0) {
    $li_previous = array(
      '#theme' => 'pager_link__previous',
      '#text' => (isset($tags[1]) ? $tags[1] : t('‹‹')),
      '#attributes' => array('title' => t('Go to previous page')),
      '#page_new' => pager_load_array($pager_page_array[$element] - 1, $element, $pager_page_array),
      '#element' => $element,
      '#interval' => 1,
      '#parameters' => $parameters,
    );
  }

  $li_next = array();
  if ($pager_page_array[$element] < ($pager_total[$element] - 1)) {
    $li_next = array(
      '#theme' => 'pager_link__next',
      '#text' => (isset($tags[3]) ? $tags[3] : t('››')),
      '#attributes' => array('title' => t('Go to next page')),
      '#page_new' => pager_load_array($pager_page_array[$element] + 1, $element, $pager_page_array),
      '#element' => $element,
      '#interval' => 1,
      '#parameters' => $parameters,
    );
  }

  $items[] = array(
    '#wrapper_attributes' => array('class' => array('pager-previous')),
  ) + $li_previous;

  $items[] = array(
    '#wrapper_attributes' => array('class' => array('pager-current')),
    '#markup' => t('Page @current', array('@current' => $pager_current)),
  );

  $items[] = array(
    '#wrapper_attributes' => array('class' => array('pager-next')),
  ) + $li_next;

  return theme('item_list',
    array(
      'items' => $items,
      'title' => NULL,
      'type' => 'ul',
      'attributes' => array('class' => array('pager')),
    )
  );
}

/**
 * @defgroup views_templates Views template files
 * @{
 * All views templates can be overridden with a variety of names, using
 * the view, the display ID of the view, the display type of the view,
 * or some combination thereof.
 *
 * For each view, there will be a minimum of two templates used. The first
 * is used for all views: views-view.html.twig.
 *
 * The second template is determined by the style selected for the view. Note
 * that certain aspects of the view can also change which style is used; for
 * example, arguments which provide a summary view might change the style to
 * one of the special summary styles.
 *
 * The default style for all views is views-view-unformatted.html.twig.
 *
 * Many styles will then farm out the actual display of each row to a row
 * style; the default row style is views-view-fields.tpl.php.
 *
 * Here is an example of all the templates that will be tried in the following
 * case:
 *
 * View, named foobar. Style: unformatted. Row style: Fields. Display: Page.
 *
 * - views-view--foobar--page.tpl.php
 * - views-view--page.tpl.php
 * - views-view--foobar.tpl.php
 * - views-view.html.twig
 *
 * - views-view-unformatted--foobar--page.html.twig
 * - views-view-unformatted--page.html.twig
 * - views-view-unformatted--foobar.html.twig
 * - views-view-unformatted.html.twig
 *
 * - views-view-fields--foobar--page.tpl.php
 * - views-view-fields--page.tpl.php
 * - views-view-fields--foobar.tpl.php
 * - views-view-fields.tpl.php
 *
 * Important! When adding a new template to your theme, be sure to flush the
 * theme registry cache!
 *
 * @see _views_theme_functions()
 * @}
 */
