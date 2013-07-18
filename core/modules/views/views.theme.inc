<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Language\Language;
use Drupal\Core\Template\Attribute;
use Drupal\views\ViewExecutable;

/**
 * Prepares variables for view templates.
 *
 * Default template: views-view.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: The ViewExecutable object.
 */
function template_preprocess_views_view(&$variables) {
  global $base_path;

  $view = $variables['view'];

  $variables['rows'] = (!empty($view->result) || $view->style_plugin->evenEmpty()) ? $view->style_plugin->render($view->result) : array();
  // Force a render array so CSS/JS can be added.
  if (!is_array($variables['rows'])) {
    $variables['rows'] = array('#markup' => $variables['rows']);
  }

  $variables['css_name'] = drupal_clean_css_identifier($view->storage->id());
  $variables['id'] = $view->storage->id();
  $variables['display_id'] = $view->current_display;

  // Basic classes.
  $variables['css_class'] = '';

  $variables['attributes']['class'] = array();
  $variables['attributes']['class'][] = 'view';
  $variables['attributes']['class'][] = 'view-' . drupal_clean_css_identifier($variables['id']);
  $variables['attributes']['class'][] = 'view-id-' . $variables['id'];
  $variables['attributes']['class'][] = 'view-display-id-' . $variables['display_id'];

  $css_class = $view->display_handler->getOption('css_class');
  if (!empty($css_class)) {
    $variables['css_class'] = preg_replace('/[^a-zA-Z0-9- ]/', '-', $css_class);
    $variables['attributes']['class'][] = $variables['css_class'];
  }

  $empty = empty($view->result);
  $variables['header'] = $view->display_handler->renderArea('header', $empty);
  $variables['footer'] = $view->display_handler->renderArea('footer', $empty);
  $variables['empty'] = $empty ? $view->display_handler->renderArea('empty', $empty) : FALSE;

  $variables['exposed']    = !empty($view->exposed_widgets) ? $view->exposed_widgets : '';
  $variables['more']       = $view->display_handler->renderMoreLink();
  $variables['feed_icon']  = !empty($view->feed_icon) ? $view->feed_icon : '';

  $variables['pager']      = '';

  // @todo: Figure out whether this belongs into views_ui_preprocess_views_view.
  // Render title for the admin preview.
  $variables['title'] = !empty($view->views_ui_context) ? filter_xss_admin($view->getTitle()) : '';

  if ($view->display_handler->renderPager()) {
    $exposed_input = isset($view->exposed_raw_input) ? $view->exposed_raw_input : NULL;
    $variables['pager'] = $view->renderPager($exposed_input);
  }

  if (!empty($view->attachment_before)) {
    $variables['attachment_before'] = $view->attachment_before;
  }
  else {
    $variables['attachment_before'] = array();
  }
  if (!empty($view->attachment_after)) {
    $variables['attachment_after'] = $view->attachment_after;
  }
  else {
    $variables['attachment_after'] = array();
  }

  // Add contextual links to the view. We need to attach them to the dummy
  // $view_array variable, since contextual_preprocess() requires that they be
  // attached to an array (not an object) in order to process them. For our
  // purposes, it doesn't matter what we attach them to, since once they are
  // processed by contextual_preprocess() they will appear in the $title_suffix
  // variable (which we will then render in views-view.html.twig).
  views_add_contextual_links($variables['view_array'], 'view', $view, $view->current_display);

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
    $variables['dom_id'] = $view->dom_id;
    $variables['attributes']['class'][] = 'view-dom-id-' . $variables['dom_id'];
  }

  // If using AJAX, send identifying data about this view.
  if ($view->ajaxEnabled() && empty($view->is_attachment) && empty($view->live_preview)) {
    $settings = array(
      'views' => array(
        'ajax_path' => url('views/ajax'),
        'ajaxViews' => array(
          'views_dom_id:' . $variables['dom_id'] => array(
            'view_name' => $view->storage->id(),
            'view_display_id' => $view->current_display,
            'view_args' => check_plain(implode('/', $view->args)),
            'view_path' => check_plain(current_path()),
            'view_base_path' => $view->getPath(),
            'view_dom_id' => $variables['dom_id'],
            // To fit multiple views on a page, the programmer may have
            // overridden the display's pager_element.
            'pager_element' => isset($view->pager) ? $view->pager->getPagerId() : 0,
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
    $rows = $variables['rows'];
    $rows = drupal_render($rows);
    $output = !empty($rows) ? $rows : $variables['empty'];
    $form = drupal_get_form(views_form_id($view), $view, $output);
    // The form is requesting that all non-essential views elements be hidden,
    // usually because the rendered step is not a view result.
    if ($form['show_view_elements']['#value'] == FALSE) {
      $variables['header'] = '';
      $variables['exposed'] = '';
      $variables['pager'] = '';
      $variables['footer'] = '';
      $variables['more'] = '';
      $variables['feed_icon'] = '';
    }
    $variables['rows'] = $form;
  }
}

/**
 * Prepares variables for views fields templates.
 *
 * Default template: views-view-fields.html.twig.
 *
 * @param array $variables
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
function template_preprocess_views_view_fields(&$variables) {
  $view = $variables['view'];

  // Loop through the fields for this view.
  $previous_inline = FALSE;
  $variables['fields'] = array(); // ensure it's at least an empty array.
  foreach ($view->field as $id => $field) {
    // render this even if set to exclude so it can be used elsewhere.
    $field_output = $view->style_plugin->getField($view->row_index, $id);
    $empty = $field->isValueEmpty($field_output, $field->options['empty_zero']);
    if (empty($field->options['exclude']) && (!$empty || (empty($field->options['hide_empty']) && empty($variables['options']['hide_empty'])))) {
      $object = new stdClass();
      $object->handler = &$view->field[$id];
      $object->inline = !empty($variables['options']['inline'][$id]);

      $object->element_type = $object->handler->elementType(TRUE, !$variables['options']['default_field_elements'], $object->inline);
      if ($object->element_type) {
        $attributes = array();
        if ($object->handler->options['element_default_classes']) {
          $attributes['class'][] = 'field-content';
        }

        if ($classes = $object->handler->elementClasses($view->row_index)) {
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
      if (isset($view->field[$id]->field_alias) && isset($variables['row']->{$view->field[$id]->field_alias})) {
        $object->raw = $variables['row']->{$view->field[$id]->field_alias};
      }
      else {
        $object->raw = NULL; // make sure it exists to reduce NOTICE
      }

      if (!empty($variables['options']['separator']) && $previous_inline && $object->inline && $object->content) {
        $object->separator = filter_xss_admin($variables['options']['separator']);
      }

      $object->class = drupal_clean_css_identifier($id);

      $previous_inline = $object->inline;
      $object->inline_html = $object->handler->elementWrapperType(TRUE, TRUE);
      if ($object->inline_html === '' && $variables['options']['default_field_elements']) {
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

        if ($classes = $object->handler->elementWrapperClasses($view->row_index)) {
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

        $object->elementLabelType = $object->handler->elementLabelType(TRUE, !$variables['options']['default_field_elements']);
        if ($object->elementLabelType) {
          $attributes = array();
          if ($object->handler->options['element_default_classes']) {
            $attributes['class'][] = 'views-label';
            $attributes['class'][] = 'views-label-' . $object->class;
          }

          $element_label_class = $object->handler->elementLabelClasses($view->row_index);
          if ($element_label_class) {
            $attributes['class'][] = $element_label_class;
          }
          $attributes = new Attribute($attributes);

          $pre = '<' . $object->elementLabelType;
          $pre .= $attributes;
          $pre .= '>';

          $object->label_html = $pre . $object->label_html . '</' . $object->elementLabelType . '>';
        }
      }

      $variables['fields'][$id] = $object;
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
 * @param array $variables
 *   An associative array containing:
 *   - view: The view object.
 *   - rows: The rows returned from the view.
 *   - grouping_level: Integer indicating the hierarchical level of the
 *     grouping.
 *   - content: The content to be grouped.
 *   - title: The group heading.
  */
function template_preprocess_views_view_grouping(&$variables) {
  $variables['content'] = $variables['view']->style_plugin->renderGroupingSets($variables['rows'], $variables['grouping_level']);
}

/**
 * Display a single views field.
 *
 * Interesting bits of info:
 * $field->field_alias says what the raw value in $row will be. Reach it like
 * this: @code { $row->{$field->field_alias} @endcode
 */
function theme_views_view_field($variables) {
  $view = $variables['view'];
  $field = $variables['field'];
  $row = $variables['row'];
  return $variables['output'];
}

/**
 * Prepares variables for views field templates.
 *
 * Default template: views-view-field.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - field: The field handler object for the current field.
 *   - row: Object representing the raw result of the SQL query for the current
 *     field.
 *   - view: Instance of the ViewExecutable object for the parent view.
 */
function template_preprocess_views_view_field(&$variables) {
  $variables['output'] = $variables['field']->advancedRender($variables['row']);
}

/**
 * Prepares variables for views summary templates.
 *
 * The summary prints a single record from a row, with fields.
 *
 * Default template: views-view-summary.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_view_summary(&$variables) {
  $view = $variables['view'];
  $argument = $view->argument[$view->build_info['summary_level']];
  $variables['row_classes'] = array();

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

  foreach ($variables['rows'] as $id => $row) {
    $row_args[$id] = $argument->summaryArgument($row);
  }
  $argument->processSummaryArguments($row_args);

  foreach ($variables['rows'] as $id => $row) {
    $variables['rows'][$id]->link = $argument->summaryName($row);
    $args = $view->args;
    $args[$argument->position] = $row_args[$id];

    $base_path = NULL;
    if (!empty($argument->options['summary_options']['base_path'])) {
      $base_path = $argument->options['summary_options']['base_path'];
    }
    $variables['rows'][$id]->url = url($view->getUrl($args, $base_path), $url_options);
    $variables['rows'][$id]->count = intval($row->{$argument->count_alias});
    $variables['row_classes'][$id] = array();
    if (isset($active_urls[$variables['rows'][$id]->url])) {
      $variables['row_classes'][$id]['class'][] = 'active';
    }
    $variables['row_classes'][$id] = new Attribute($variables['row_classes'][$id]);
  }
}

/**
 * Prepares variables for unformatted summary view templates.
 *
 * Default template: views-view-summary-unformatted.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 *   - options: An array of options. Each option contains:
 *     - separator: A string to be placed between inline fields to keep them
 *       visually distinct.
 */
function template_preprocess_views_view_summary_unformatted(&$variables) {
  $view = $variables['view'];
  $argument = $view->argument[$view->build_info['summary_level']];
  $variables['row_classes'] = array();

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
  foreach ($variables['rows'] as $id => $row) {
    $row_args[$id] = $argument->summaryArgument($row);
  }
  $argument->processSummaryArguments($row_args);

  foreach ($variables['rows'] as $id => $row) {
    // Only false on first time.
    if ($count++) {
      $variables['rows'][$id]->separator = filter_xss_admin($variables['options']['separator']);
    }
    $variables['rows'][$id]->link = $argument->summaryName($row);
    $args = $view->args;
    $args[$argument->position] = $row_args[$id];

    $base_path = NULL;
    if (!empty($argument->options['summary_options']['base_path'])) {
      $base_path = $argument->options['summary_options']['base_path'];
    }
    $variables['rows'][$id]->url = url($view->getUrl($args, $base_path), $url_options);
    $variables['rows'][$id]->count = intval($row->{$argument->count_alias});
    $variables['row_classes'][$id] = array();
    if (isset($active_urls[$variables['rows'][$id]->url])) {
      $variables['row_classes'][$id]['class'][] = 'active';
    }
    $variables['row_classes'][$id] = new Attribute($variables['row_classes'][$id]);
  }
}

/**
 * Prepares variables for views table templates.
 *
 * Default template: views-view-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_view_table(&$variables) {
  $view = $variables['view'];

  // We need the raw data for this grouping, which is passed in
  // as $variables['rows'].
  // However, the template also needs to use for the rendered fields.  We
  // therefore swap the raw data out to a new variable and reset $variables['rows']
  // so that it can get rebuilt.
  // Store rows so that they may be used by further preprocess functions.
  $result = $variables['result'] = $variables['rows'];
  $variables['rows'] = array();
  $variables['field_classes'] = array();
  $variables['header'] = array();

  $options = $view->style_plugin->options;
  $handler = $view->style_plugin;

  $default_row_class = isset($options['default_row_class']) ? $options['default_row_class'] : TRUE;
  $row_class_special = isset($options['row_class_special']) ? $options['row_class_special'] : TRUE;

  $fields = &$view->field;
  $columns = $handler->sanitizeColumns($options['columns'], $fields);

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
    $variables['fields'][$field] = drupal_clean_css_identifier($field);
    if ($active == $field) {
      $variables['fields'][$field] .= ' active';
    }

    // render the header labels
    if ($field == $column && empty($fields[$field]->options['exclude'])) {
      $label = check_plain(!empty($fields[$field]) ? $fields[$field]->label() : '');
      if (empty($options['info'][$field]['sortable']) || !$fields[$field]->clickSortable()) {
        $variables['header'][$field] = $label;
      }
      else {
        $initial = !empty($options['info'][$field]['default_sort_order']) ? $options['info'][$field]['default_sort_order'] : 'asc';

        if ($active == $field) {
          $initial = ($order == 'asc') ? 'desc' : 'asc';
        }

        $title = t('sort by @s', array('@s' => $label));
        if ($active == $field) {
          $tablesort_indicator = array(
            '#theme' => 'tablesort_indicator',
            '#style' => $initial,
          );
          $label .= drupal_render($tablesort_indicator);
        }

        $query['order'] = $field;
        $query['sort'] = $initial;
        $link_options = array(
          'html' => TRUE,
          'attributes' => array('title' => $title),
          'query' => $query,
        );
        $variables['header'][$field] = l($label, current_path(), $link_options);
      }

      // Set up the header label class.
      $variables['header_classes'][$field] = array();
      if ($fields[$field]->options['element_default_classes']) {
        $variables['header_classes'][$field]['class'][] = 'views-field';
        $variables['header_classes'][$field]['class'][] = 'views-field-' . $variables['fields'][$field];
      }
      $variables['header_classes'][$field] = new Attribute($variables['header_classes'][$field]);
      $class = $fields[$field]->elementLabelClasses(0);
      if ($class) {
        $variables['header_classes'][$field]['class'][] = $class;
      }
      // Add responsive header classes.
      if (!empty($options['info'][$field]['responsive'])) {
        $variables['header_classes'][$field]['class'][] = $options['info'][$field]['responsive'];
        $responsive = TRUE;
      }
      // Add a CSS align class to each field if one was set.
      if (!empty($options['info'][$field]['align'])) {
        $variables['header_classes'][$field]['class'][] = drupal_clean_css_identifier($options['info'][$field]['align']);
      }
      // Add a header label wrapper if one was selected.
      if ($variables['header'][$field]) {
        $element_label_type = $fields[$field]->elementLabelType(TRUE, TRUE);
        if ($element_label_type) {
          $variables['header'][$field] = '<' . $element_label_type . '>' . $variables['header'][$field] . '</' . $element_label_type . '>';
        }
      }
    }

    // Add a CSS align class to each field if one was set.
    if (!empty($options['info'][$field]['align'])) {
      $variables['fields'][$field] .= ' ' . drupal_clean_css_identifier($options['info'][$field]['align']);
    }

    // Render each field into its appropriate column.
    foreach ($result as $num => $row) {
      // Add field classes.
      $variables['field_classes'][$field][$num] = array();
      if ($fields[$field]->options['element_default_classes']) {
        $variables['field_classes'][$field][$num]['class'][] = 'views-field';
        $variables['field_classes'][$field][$num]['class'][] = 'views-field-' . $variables['fields'][$field];
      }
      $variables['field_classes'][$field][$num] = new Attribute($variables['field_classes'][$field][$num]);

      if ($classes = $fields[$field]->elementClasses($num)) {
        $variables['field_classes'][$field][$num]['class'][] = $classes;
      }
      // Add responsive header classes.
      if (!empty($options['info'][$field]['responsive'])) {
        $variables['field_classes'][$field][$num]['class'][] = $options['info'][$field]['responsive'];
      }

      if (!empty($fields[$field]) && empty($fields[$field]->options['exclude'])) {
        $field_output = $handler->getField($num, $field);
        $element_type = $fields[$field]->elementType(TRUE, TRUE);
        if ($element_type) {
          $field_output = '<' . $element_type . '>' . $field_output . '</' . $element_type . '>';
        }

        // Don't bother with separators and stuff if the field does not show up.
        if (empty($field_output) && !empty($variables['rows'][$num][$column])) {
          continue;
        }

        // Place the field into the column, along with an optional separator.
        if (!empty($variables['rows'][$num][$column])) {
          if (!empty($options['info'][$column]['separator'])) {
            $variables['rows'][$num][$column] .= filter_xss_admin($options['info'][$column]['separator']);
          }
        }
        else {
          $variables['rows'][$num][$column] = '';
        }
        $variables['rows'][$num][$column] .= $field_output;
      }
    }

    // Remove columns if the option is hide empty column is checked and the
    // field is not empty.
    if (!empty($options['info'][$field]['empty_column'])) {
      $empty = TRUE;
      foreach ($variables['rows'] as $num => $columns) {
        $empty &= empty($columns[$column]);
      }
      if ($empty) {
        foreach ($variables['rows'] as $num => &$column_items) {
          unset($column_items[$column]);
          unset($variables['header'][$column]);
        }
      }
    }
  }

  // Hide table header if all labels are empty.
  if (!array_filter($variables['header'])) {
    $variables['header'] = array();
  }

  $count = 0;
  $variables['row_classes'] = array();
  foreach ($variables['rows'] as $num => $row) {
    $variables['row_classes'][$num] = array();
    if ($row_class_special) {
      $variables['row_classes'][$num]['class'][] = ($count++ % 2 == 0) ? 'odd' : 'even';
      if ($num === 0) {
        $variables['row_classes'][$num]['class'][] = 'views-row-first';
      }
      elseif ($num === (count($variables['rows']) - 1)) {
        $variables['row_classes'][$num]['class'][] = 'views-row-last';
      }
    }
    if ($row_class = $handler->getRowClass($num)) {
      $variables['row_classes'][$num]['class'][] = $row_class;
    }
    $variables['row_classes'][$num] = new Attribute($variables['row_classes'][$num]);
  }

  $variables['attributes']['class'][] = 'views-table';
  $variables['attributes']['class'][] = 'views-view-table';
  if (empty($variables['rows']) && !empty($options['empty_table'])) {
    $build = $view->display_handler->renderArea('empty');
    $variables['rows'][0][0] = drupal_render($build);
    $variables['row_classes'][0] = new Attribute(array('class' => 'odd'));
    // Calculate the amounts of rows with output.
    $variables['field_classes'][0][0] = new Attribute(array(
      'colspan' => count($variables['header']),
      'class' => 'views-empty',
    ));
  }

  if (!empty($options['sticky'])) {
    $variables['view']->element['#attached']['library'][] = array('system', 'drupal.tableheader');
    $variables['attributes']['class'][] = "sticky-enabled";
  }
  $variables['attributes']['class'][] = 'cols-' . count($variables['header']);

  // Add the caption to the list if set.
  if (!empty($handler->options['caption'])) {
    $variables['caption'] = Xss::filterAdmin($handler->options['caption']);
    $variables['caption_needed'] = TRUE;
  }
  else {
    $variables['caption'] = '';
    $variables['caption_needed'] = FALSE;
  }

  $variables['summary'] = $handler->options['summary'];
  $variables['description'] = $handler->options['description'];
  $variables['caption_needed'] |= !empty($variables['summary']) || !empty($variables['description']);

  // If the table has headers and it should react responsively to columns hidden
  // with the classes represented by the constants RESPONSIVE_PRIORITY_MEDIUM
  // and RESPONSIVE_PRIORITY_LOW, add the tableresponsive behaviors.
  if (isset($variables['header']) && $responsive) {
    $variables['view']->element['#attached']['library'][] = array('system', 'drupal.tableresponsive');
    // Add 'responsive-enabled' class to the table to identify it for JS.
    // This is needed to target tables constructed by this function.
    $variables['attributes']['class'][] = 'responsive-enabled';
  }
}

/**
 * Prepares variables for views grid style templates.
 *
 * Default template: views-view-grid.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: The view object.
 *   - rows: An array of row items. Each row is an array of content.
 */
function template_preprocess_views_view_grid(&$variables) {
  $options = $variables['options'] = $variables['view']->style_plugin->options;
  $horizontal = ($options['alignment'] === 'horizontal');

  $variables['attributes']['class'] = array(
    'views-view-grid',
    $options['alignment'],
    'cols-' . $options['columns'],
    'clearfix',
  );

  $col = 0;
  $row = 0;
  $items = array();
  $remainders = count($variables['rows']) % $options['columns'];
  $num_rows = floor(count($variables['rows']) / $options['columns']);

  // Iterate over each rendered views result row.
  foreach ($variables['rows'] as $item) {

    // Add the item.
    if ($horizontal) {
      $items[$row]['content'][$col]['content'] = $item;
    }
    else {
      $items[$col]['content'][$row]['content'] = $item;
    }

    // Create attributes for rows.
    if (!$horizontal || ($horizontal && empty($items[$row]['attributes']))) {
      $row_attributes = array('class' => array());
      // Add default views row classes.
      if ($options['row_class_default']) {
        $row_attributes['class'][] = 'views-row';
        $row_attributes['class'][] = 'row-' . ($row + 1);
        if ($horizontal) {
          $row_attributes['class'][] = 'clearfix';
        }
      }
      // Add custom row classes.
      $row_class = array_filter(explode(' ', $options['row_class_custom']));
      if (!empty($row_class)) {
        $row_attributes['class'] = array_merge($row_attributes['class'], $row_class);
      }
      // Add row attributes to the item.
      if ($horizontal) {
        $items[$row]['attributes'] = new Attribute($row_attributes);
      }
      else {
        $items[$col]['content'][$row]['attributes'] = new Attribute($row_attributes);
      }
    }

    // Create attributes for columns.
    if ($horizontal || (!$horizontal && empty($items[$col]['attributes']))) {
      $col_attributes = array('class' => array());
      // Add default views column classes.
      if ($options['col_class_default']) {
        $col_attributes['class'][] = 'views-col';
        $col_attributes['class'][] = 'col-' . ($col + 1);
        if (!$horizontal) {
          $col_attributes['class'][] = 'clearfix';
        }
      }
      // Add custom column classes.
      $col_class = array_filter(explode(' ', $options['col_class_custom']));
      if (!empty($col_class)) {
        $col_attributes['class'] = array_merge($col_attributes['class'], $col_class);
      }
      // Add automatic width for columns.
      if ($options['automatic_width']) {
        $col_attributes['style'] = 'width: ' . (100 / $options['columns']) . '%;';
      }
      // Add column attributes to the item.
      if ($horizontal) {
        $items[$row]['content'][$col]['attributes'] = new Attribute($col_attributes);
      }
      else {
        $items[$col]['attributes'] = new Attribute($col_attributes);
      }
    }

    // Increase, decrease or reset appropriate integers.
    if ($horizontal) {
      if ($col == 0 && $col != ($options['columns'] - 1)) {
        $col++;
      }
      elseif ($col >= ($options['columns'] - 1)) {
        $col = 0;
        $row++;
      }
      else {
        $col++;
      }
    }
    else {
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
  }

  // Add items to the variables array.
  $variables['items'] = $items;
}

/**
 * Prepares variables for views unformatted rows templates.
 *
 * Default template: views-view-unformatted.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: The view object.
 *   - rows: An array of row items. Each row is an array of content.
 */
function template_preprocess_views_view_unformatted(&$variables) {
  $view = $variables['view'];
  $rows = $variables['rows'];
  $style = $view->style_plugin;
  $options = $style->options;

  $variables['row_classes'] = array();
  $variables['classes'] = array();
  $default_row_class = isset($options['default_row_class']) ? $options['default_row_class'] : FALSE;
  $row_class_special = isset($options['row_class_special']) ? $options['row_class_special'] : FALSE;
  // Set up striping values.
  $count = 0;
  $max = count($rows);
  foreach ($rows as $id => $row) {
    $variables['row_classes'][$id] = array();
    $count++;
    if ($default_row_class) {
      $variables['row_classes'][$id]['class'][] = 'views-row';
      $variables['row_classes'][$id]['class'][] = 'views-row-' . $count;
    }
    if ($row_class_special) {
      $variables['row_classes'][$id]['class'][] = 'views-row-' . ($count % 2 ? 'odd' : 'even');
      if ($count == 1) {
        $variables['row_classes'][$id]['class'][] = 'views-row-first';
      }
      if ($count == $max) {
        $variables['row_classes'][$id]['class'][] = 'views-row-last';
      }
    }

    if ($row_class = $view->style_plugin->getRowClass($id)) {
      $variables['row_classes'][$id]['class'][] = $row_class;
    }
    $variables['row_classes'][$id] = new Attribute($variables['row_classes'][$id]);
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
 * @param array $variables
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
*/
function template_preprocess_views_view_rss(&$variables) {
  global $base_url;

  $view = $variables['view'];
  $items = $variables['rows'];

  $style = $view->style_plugin;

  $config = config('system.site');

  // The RSS 2.0 "spec" doesn't indicate HTML can be used in the description.
  // We strip all HTML tags, but need to prevent double encoding from properly
  // escaped source data (such as &amp becoming &amp;amp;).
  $variables['description'] = check_plain(decode_entities(strip_tags($style->getDescription())));

  if ($view->display_handler->getOption('sitename_title')) {
    $title = $config->get('name');
    if ($slogan = $config->get('slogan')) {
      $title .= ' - ' . $slogan;
    }
  }
  else {
    $title = $view->getTitle();
  }
  $variables['title'] = check_plain($title);

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

    $variables['link'] = check_url(url($path, $url_options));
  }

  $variables['langcode'] = check_plain(language(Language::TYPE_INTERFACE)->id);
  $variables['namespaces'] = new Attribute($style->namespaces);
  $variables['items'] = $items;
  $variables['channel_elements'] = format_xml_elements($style->channel_elements);

  // During live preview we don't want to output the header since the contents
  // of the feed are being displayed inside a normal HTML page.
  if (empty($variables['view']->live_preview)) {
    $variables['view']->getResponse()->headers->set('Content-Type', 'application/rss+xml; charset=utf-8');
  }
}

/**
 * Prepares variables for views RSS item templates.
 *
 * Default template: views-view-row-rss.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - row: The raw results rows.
 */
function template_preprocess_views_view_row_rss(&$variables) {
  $item = $variables['row'];

  $variables['title'] = check_plain($item->title);
  $variables['link'] = check_url($item->link);
  $variables['description'] = check_plain($item->description);
  $variables['item_elements'] = empty($item->elements) ? '' : format_xml_elements($item->elements);
}

/**
 * Prepares variables for views exposed form templates.
 *
 * Default template: views-exposed-form.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 */
function template_preprocess_views_exposed_form(&$variables) {
  $form = &$variables['form'];

  if (!empty($form['q'])) {
    $variables['q'] = $form['q'];
  }

  // Include basic theming for exposed forms.
  $form['#attached']['library'][] = array('views', 'views.exposed-form');

  foreach ($form['#info'] as $id => $info) {
    if (!empty($info['label'])) {
      $form[$info['value']]['#title'] = $info['label'];
    }
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

/**
 * Theme function for the Mini pager.
 */
function theme_views_mini_pager($variables) {
  global $pager_page_array, $pager_total;

  $tags = &$variables['tags'];
  $element = $variables['element'];
  $parameters = $variables['parameters'];

  // Fill in default link labels.
  $tags += array(
    1 => t('‹‹'),
    3 => t('››'),
  );

  // Current is the page we are currently paged to.
  $pager_current = $pager_page_array[$element] + 1;
  $current_path = current_path();

  $li_previous = array();
  if ($pager_total[$element] > 1 && $pager_page_array[$element] > 0) {
    $li_previous = array(
      '#type' => 'link',
      '#title' => $tags[1],
      '#href' => $current_path,
      '#options' => array(
        'query' => pager_query_add_page($parameters, $element, $pager_page_array[$element] - 1),
        'attributes' => array(
          'title' => t('Go to previous page'),
          'rel' => 'prev',
        ),
        // Below is ignored by default, supplied to support hook_link_alter
        // implementations.
        'pager_context' => array(
          'link_type' => 'previous',
          'element' => $element,
          'interval' => -1,
        ),
      ),
    );
  }

  $li_next = array();
  if ($pager_page_array[$element] < ($pager_total[$element] - 1)) {
    $li_next = array(
      '#type' => 'link',
      '#title' => $tags[3],
      '#href' => $current_path,
      '#options' => array(
        'query' => pager_query_add_page($parameters, $element, $pager_page_array[$element] + 1),
        'attributes' => array(
          'title' => t('Go to next page'),
          'rel' => 'next',
        ),
        // Below is ignored by default, supplied to support hook_link_alter
        // implementations.
        'pager_context' => array(
          'link_type' => 'previous',
          'element' => $element,
          'interval' => 1,
        ),
      ),
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

  $item_list = array(
    '#theme' => 'item_list__pager',
    '#items' => $items,
    '#title' => NULL,
    '#list_type' => 'ul',
    '#attributes' => array(
      'class' => array('pager'),
    ),
  );
  return drupal_render($item_list);
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
 * @see \Drupal\views\ViewExecutable::buildThemeFunctions()
 * @}
 */
