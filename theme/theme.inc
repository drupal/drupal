<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

/**
 * Provide a full array of possible themes to try for a given hook.
 *
 * @param $hook
 *   The hook to use. This is the base theme/template name.
 * @param $view
 *   The view being rendered.
 * @param $display
 *   The display being rendered, if applicable.
 */
function _views_theme_functions($hook, $view, $display = NULL) {
  $themes = array();

  if ($display) {
    $themes[] = $hook . '__' . $view->name . '__' . $display->id;
    $themes[] = $hook . '__' . $display->id;
    $themes[] = $hook . '__' . preg_replace('/[^a-z0-9]/', '_', strtolower($view->tag));

    // Add theme suggestions foreach single tag.
    foreach (drupal_explode_tags($view->tag) as $tag) {
      $themes[] = $hook . '__' . preg_replace('/[^a-z0-9]/', '_', strtolower($tag));
    }

    if ($display->id != $display->display_plugin) {
      $themes[] = $hook . '__' . $view->name . '__' . $display->display_plugin;
      $themes[] = $hook . '__' . $display->display_plugin;
    }
  }
  $themes[] = $hook . '__' . $view->name;
  $themes[] = $hook;
  return $themes;
}

/**
 * Preprocess the primary theme implementation for a view.
 */
function template_preprocess_views_view(&$vars) {
  global $base_path;

  $view = $vars['view'];

  $vars['rows']       = (!empty($view->result) || $view->style_plugin->even_empty()) ? $view->style_plugin->render($view->result) : '';

  $vars['css_name']   = drupal_clean_css_identifier($view->name);
  $vars['name']       = $view->name;
  $vars['display_id'] = $view->current_display;

  // Basic classes
  $vars['classes_array'] = array();
  $vars['classes_array'][] = 'view';
  $vars['classes_array'][] = 'view-' . drupal_clean_css_identifier($vars['name']);
  $vars['classes_array'][] = 'view-id-' . $vars['name'];
  $vars['classes_array'][] = 'view-display-id-' . $vars['display_id'];

  $css_class = $view->display_handler->get_option('css_class');
  if (!empty($css_class)) {
    $vars['css_class'] = preg_replace('/[^a-zA-Z0-9- ]/', '-', $css_class);
    $vars['classes_array'][] = $vars['css_class'];
  }

  $empty = empty($vars['rows']);

  $vars['header'] = $view->display_handler->render_area('header', $empty);
  $vars['footer'] = $view->display_handler->render_area('footer', $empty);
  if ($empty) {
    $vars['empty'] = $view->display_handler->render_area('empty');
  }

  $vars['exposed']    = !empty($view->exposed_widgets) ? $view->exposed_widgets : '';
  $vars['more']       = $view->display_handler->render_more_link();
  $vars['feed_icon']  = !empty($view->feed_icon) ? $view->feed_icon : '';

  $vars['pager']      = '';

  // @todo: Figure out whether this belongs into views_ui_preprocess_views_view.
  // Render title for the admin preview.
  $vars['title'] = !empty($view->views_ui_context) ? filter_xss_admin($view->get_title()) : '';

  if ($view->display_handler->render_pager()) {
    $exposed_input = isset($view->exposed_raw_input) ? $view->exposed_raw_input : NULL;
    $vars['pager']      = $view->query->render_pager($exposed_input);
  }

  $vars['attachment_before'] = !empty($view->attachment_before) ? $view->attachment_before : '';
  $vars['attachment_after'] = !empty($view->attachment_after) ? $view->attachment_after : '';

  // Add contextual links to the view. We need to attach them to the dummy
  // $view_array variable, since contextual_preprocess() requires that they be
  // attached to an array (not an object) in order to process them. For our
  // purposes, it doesn't matter what we attach them to, since once they are
  // processed by contextual_preprocess() they will appear in the $title_suffix
  // variable (which we will then render in views-view.tpl.php).
  views_add_contextual_links($vars['view_array'], 'view', $view, $view->current_display);

  // Attachments are always updated with the outer view, never by themselves,
  // so they do not have dom ids.
  if (empty($view->is_attachment)) {
    // Our JavaScript needs to have some means to find the HTML belonging to this
    // view.
    //
    // It is true that the DIV wrapper has classes denoting the name of the view
    // and its display ID, but this is not enough to unequivocally match a view
    // with its HTML, because one view may appear several times on the page. So
    // we set up a hash with the current time, $dom_id, to issue a "unique" identifier for
    // each view. This identifier is written to both Drupal.settings and the DIV
    // wrapper.
    $vars['dom_id'] = $view->dom_id;
    $vars['classes_array'][] = 'view-dom-id-' . $vars['dom_id'];
  }

  // If using AJAX, send identifying data about this view.
  if ($view->use_ajax && empty($view->is_attachment) && empty($view->live_preview)) {
    $settings = array(
      'views' => array(
        'ajax_path' => url('views/ajax'),
        'ajaxViews' => array(
          'views_dom_id:' . $vars['dom_id'] => array(
            'view_name' => $view->name,
            'view_display_id' => $view->current_display,
            'view_args' => check_plain(implode('/', $view->args)),
            'view_path' => check_plain($_GET['q']),
            // Pass through URL to ensure we get e.g. language prefixes.
//            'view_base_path' => isset($view->display['page']) ? substr(url($view->display['page']->display_options['path']), strlen($base_path)) : '',
            'view_base_path' => $view->get_path(),
            'view_dom_id' => $vars['dom_id'],
            // To fit multiple views on a page, the programmer may have
            // overridden the display's pager_element.
            'pager_element' => isset($view->query->pager) ? $view->query->pager->get_pager_id() : 0,
          ),
        ),
      ),
    );

    drupal_add_js($settings, 'setting');
    views_add_js('ajax_view');
  }

  // If form fields were found in the View, reformat the View output as a form.
  if (views_view_has_form_elements($view)) {
    $output = !empty($vars['rows']) ? $vars['rows'] : $vars['empty'];
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
 * Process function to render certain elements into the view.
 */
function template_process_views_view(&$vars) {
  if (is_array($vars['rows'])) {
    $vars['rows'] = drupal_render($vars['rows']);
  }

  // Flatten the classes to a string for the template file.
  $vars['classes'] = implode(' ', $vars['classes_array']);
}

/**
 * Preprocess theme function to print a single record from a row, with fields
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
        $class = '';
        if ($object->handler->options['element_default_classes']) {
          $class = 'field-content';
        }

        if ($classes = $object->handler->element_classes($view->row_index)) {
          if ($class) {
            $class .= ' ';
          }
          $class .=  $classes;
        }

        $pre = '<' . $object->element_type;
        if ($class) {
          $pre .= ' class="' . $class . '"';
        }
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
        $class = '';
        if ($object->handler->options['element_default_classes']) {
          $class = "views-field views-field-" . $object->class;
        }

        if ($classes = $object->handler->element_wrapper_classes($view->row_index)) {
          if ($class) {
            $class .= ' ';
          }
          $class .= $classes;
        }

        $object->wrapper_prefix = '<' . $object->inline_html;
        if ($class) {
          $object->wrapper_prefix .= ' class="' . $class . '"';
        }
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
          $class = '';
          if ($object->handler->options['element_default_classes']) {
            $class = 'views-label views-label-' . $object->class;
          }

          $element_label_class = $object->handler->element_label_classes($view->row_index);
          if ($element_label_class) {
            if ($class) {
              $class .= ' ';
            }

            $class .= $element_label_class;
          }

          $pre = '<' . $object->element_label_type;
          if ($class) {
            $pre .= ' class="' . $class . '"';
          }
          $pre .= '>';

          $object->label_html = $pre . $object->label_html . '</' . $object->element_label_type . '>';
        }
      }

      $vars['fields'][$id] = $object;
    }
  }

}

/**
 * Display a single views grouping.
 */
function theme_views_view_grouping($vars) {
  $view = $vars['view'];
  $title = $vars['title'];
  $content = $vars['content'];

  $output = '<div class="view-grouping">';
  $output .= '<div class="view-grouping-header">' . $title . '</div>';
  $output .= '<div class="view-grouping-content">' . $content . '</div>' ;
  $output .= '</div>';

  return $output;
}

/**
 * Process a single grouping within a view.
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
 * Process a single field within a view.
 *
 * This preprocess function isn't normally run, as a function is used by
 * default, for performance. However, by creating a template, this
 * preprocess should get picked up.
 */
function template_preprocess_views_view_field(&$vars) {
  $vars['output'] = $vars['field']->advanced_render($vars['row']);
}

/**
 * Preprocess theme function to print a single record from a row, with fields
 */
function template_preprocess_views_view_summary(&$vars) {
  $view     = $vars['view'];
  $argument = $view->argument[$view->build_info['summary_level']];
  $vars['row_classes'] = array();

  $url_options = array();

  if (!empty($view->exposed_raw_input)) {
    $url_options['query'] = $view->exposed_raw_input;
  }

  $active_urls = drupal_map_assoc(array(
    url($_GET['q'], array('alias' => TRUE)), // force system path
    url($_GET['q']), // could be an alias
  ));

  // Collect all arguments foreach row, to be able to alter them for example by the validator.
  // This is not done per single argument value, because this could cause performance problems.
  $row_args = array();

  foreach ($vars['rows'] as $id => $row) {
    $row_args[$id] = $argument->summary_argument($row);
  }
  $argument->process_summary_arguments($row_args);

  foreach ($vars['rows'] as $id => $row) {
    $vars['row_classes'][$id] = '';

    $vars['rows'][$id]->link = $argument->summary_name($row);
    $args = $view->args;
    $args[$argument->position] = $row_args[$id];

    $base_path = NULL;
    if (!empty($argument->options['summary_options']['base_path'])) {
      $base_path = $argument->options['summary_options']['base_path'];
    }
    $vars['rows'][$id]->url = url($view->get_url($args, $base_path), $url_options);
    $vars['rows'][$id]->count = intval($row->{$argument->count_alias});
    if (isset($active_urls[$vars['rows'][$id]->url])) {
      $vars['row_classes'][$id] = 'active';
    }
  }
}

/**
 * Template preprocess theme function to print summary basically
 * unformatted.
 */
function template_preprocess_views_view_summary_unformatted(&$vars) {
  $view     = $vars['view'];
  $argument = $view->argument[$view->build_info['summary_level']];
  $vars['row_classes'] = array();

  $url_options = array();

  if (!empty($view->exposed_raw_input)) {
    $url_options['query'] = $view->exposed_raw_input;
  }

  $count = 0;
  $active_urls = drupal_map_assoc(array(
    url($_GET['q'], array('alias' => TRUE)), // force system path
    url($_GET['q']), // could be an alias
  ));

  // Collect all arguments foreach row, to be able to alter them for example by the validator.
  // This is not done per single argument value, because this could cause performance problems.
  $row_args = array();
  foreach ($vars['rows'] as $id => $row) {
    $row_args[$id] = $argument->summary_argument($row);
  }
  $argument->process_summary_arguments($row_args);

  foreach ($vars['rows'] as $id => $row) {
    // only false on first time:
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
    $vars['rows'][$id]->url = url($view->get_url($args, $base_path), $url_options);
    $vars['rows'][$id]->count = intval($row->{$argument->count_alias});
    if (isset($active_urls[$vars['rows'][$id]->url])) {
      $vars['row_classes'][$id] = 'active';
    }
  }
}

/**
 * Display a view as a table style.
 */
function template_preprocess_views_view_table(&$vars) {
  $view     = $vars['view'];

  // We need the raw data for this grouping, which is passed in as $vars['rows'].
  // However, the template also needs to use for the rendered fields.  We
  // therefore swap the raw data out to a new variable and reset $vars['rows']
  // so that it can get rebuilt.
  // Store rows so that they may be used by further preprocess functions.
  $result   = $vars['result'] = $vars['rows'];
  $vars['rows'] = array();
  $vars['field_classes'] = array();
  $vars['header'] = array();

  $options  = $view->style_plugin->options;
  $handler  = $view->style_plugin;

  $default_row_class = isset($options['default_row_class']) ? $options['default_row_class'] : TRUE;
  $row_class_special = isset($options['row_class_special']) ? $options['row_class_special'] : TRUE;

  $fields   = &$view->field;
  $columns  = $handler->sanitize_columns($options['columns'], $fields);

  $active   = !empty($handler->active) ? $handler->active : '';
  $order    = !empty($handler->order) ? $handler->order : 'asc';

  $query    = tablesort_get_query_parameters();
  if (isset($view->exposed_raw_input)) {
    $query += $view->exposed_raw_input;
  }

  // Fields must be rendered in order as of Views 2.3, so we will pre-render
  // everything.
  $renders = $handler->render_fields($result);

  foreach ($columns as $field => $column) {
    // Create a second variable so we can easily find what fields we have and what the
    // CSS classes should be.
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
        $vars['header'][$field] = l($label, $_GET['q'], $link_options);
      }

      $vars['header_classes'][$field] = '';
      // Set up the header label class.
      if ($fields[$field]->options['element_default_classes']) {
        $vars['header_classes'][$field] .= "views-field views-field-" . $vars['fields'][$field];
      }
      $class = $fields[$field]->element_label_classes(0);
      if ($class) {
        if ($vars['header_classes'][$field]) {
          $vars['header_classes'][$field] .= ' ';
        }
        $vars['header_classes'][$field] .= $class;
      }
      // Add a CSS align class to each field if one was set
      if (!empty($options['info'][$field]['align'])) {
        $vars['header_classes'][$field] .= ' ' . drupal_clean_css_identifier($options['info'][$field]['align']);
      }

      // Add a header label wrapper if one was selected.
      if ($vars['header'][$field]) {
        $element_label_type = $fields[$field]->element_label_type(TRUE, TRUE);
        if ($element_label_type) {
          $vars['header'][$field] = '<' . $element_label_type . '>' . $vars['header'][$field] . '</' . $element_label_type . '>';
        }
      }

    }

    // Add a CSS align class to each field if one was set
    if (!empty($options['info'][$field]['align'])) {
      $vars['fields'][$field] .= ' ' . drupal_clean_css_identifier($options['info'][$field]['align']);
    }

    // Render each field into its appropriate column.
    foreach ($result as $num => $row) {
      // Add field classes
      $vars['field_classes'][$field][$num] = '';
      if ($fields[$field]->options['element_default_classes']) {
        $vars['field_classes'][$field][$num] = "views-field views-field-" . $vars['fields'][$field];
      }
      if ($classes = $fields[$field]->element_classes($num)) {
        if ($vars['field_classes'][$field][$num]) {
          $vars['field_classes'][$field][$num] .= ' ';
        }

        $vars['field_classes'][$field][$num] .= $classes;
      }
      $vars['field_attributes'][$field][$num] = array();

      if (!empty($fields[$field]) && empty($fields[$field]->options['exclude'])) {
        $field_output = $renders[$num][$field];
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

    // Remove columns if the option is hide empty column is checked and the field is not empty.
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
  foreach ($vars['rows'] as $num => $row) {
    if ($row_class_special) {
      $vars['row_classes'][$num][] = ($count++ % 2 == 0) ? 'odd' : 'even';
    }
    if ($row_class = $handler->get_row_class($num)) {
      $vars['row_classes'][$num][] = $row_class;
    }
  }

  if ($row_class_special) {
    $vars['row_classes'][0][] = 'views-row-first';
    $vars['row_classes'][count($vars['row_classes']) - 1][] = 'views-row-last';
  }

  $vars['classes_array'] = array('views-table');
  if (empty($vars['rows']) && !empty($options['empty_table'])) {
    $vars['rows'][0][0] = $view->display_handler->render_area('empty');
    // Calculate the amounts of rows with output.
    $vars['field_attributes'][0][0]['colspan'] = count($vars['header']);
    $vars['field_classes'][0][0] = 'views-empty';
  }


  if (!empty($options['sticky'])) {
    drupal_add_js('misc/tableheader.js');
    $vars['classes_array'][] = "sticky-enabled";
  }
  $vars['classes_array'][] = 'cols-'. count($vars['header']);

  if (!empty($handler->options['summary'])) {
    $vars['attributes_array'] = array('summary' => $handler->options['summary']);
  }
}

/**
 * Display a view as a grid style.
 */
function template_preprocess_views_view_grid(&$vars) {
  $view     = $vars['view'];
  $result   = $view->result;
  $options  = $view->style_plugin->options;
  $handler  = $view->style_plugin;
  $default_row_class = isset($options['default_row_class']) ? $options['default_row_class'] : TRUE;
  $row_class_special = isset($options['row_class_special']) ? $options['row_class_special'] : TRUE;

  $columns  = $options['columns'];

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
      // This should be string so that's okay :)
      if (!isset($rows[count($rows) - 1][$i])) {
        $rows[count($rows) - 1][$i] = '';
      }
    }
  }

  // Apply the row classes
  foreach ($rows as $row_number => $row) {
    $row_classes = array();
    if ($default_row_class) {
      $row_classes[] =  'row-' . ($row_number + 1);
    }
    if ($row_class_special) {
      if ($row_number == 0) {
        $row_classes[] =  'row-first';
      }
      if (count($rows) == ($row_number + 1)) {
        $row_classes[] =  'row-last';
      }
    }
    $vars['row_classes'][$row_number] = implode(' ', $row_classes);
    foreach ($rows[$row_number] as $column_number => $item) {
      $column_classes = array();
      if ($default_row_class) {
        $column_classes[] = 'col-'. ($column_number + 1);
      }
      if ($row_class_special) {
        if ($column_number == 0) {
          $column_classes[] = 'col-first';
        }
        elseif (count($rows[$row_number]) == ($column_number + 1)) {
          $column_classes[] = 'col-last';
        }
      }
      if (isset($row_indexes[$row_number][$column_number]) && $column_class = $view->style_plugin->get_row_class($row_indexes[$row_number][$column_number])) {
        $column_classes[] = $column_class;
      }
      $vars['column_classes'][$row_number][$column_number] = implode(' ', $column_classes);
    }
  }
  $vars['rows'] = $rows;
  $vars['class'] = 'views-view-grid cols-' . $columns;
  if (!empty($handler->options['summary'])) {
    $vars['attributes_array'] = array('summary' => $handler->options['summary']);
  }
}

/**
 * Display the simple view of rows one after another
 */
function template_preprocess_views_view_unformatted(&$vars) {
  $view = $vars['view'];
  $rows = $vars['rows'];
  $style = $view->style_plugin;
  $options = $style->options;

  $vars['classes_array'] = array();
  $vars['classes'] = array();
  $default_row_class = isset($options['default_row_class']) ? $options['default_row_class'] : FALSE;
  $row_class_special = isset($options['row_class_special']) ? $options['row_class_special'] : FALSE;
  // Set up striping values.
  $count = 0;
  $max = count($rows);
  foreach ($rows as $id => $row) {
    $count++;
    if ($default_row_class) {
      $vars['classes'][$id][] = 'views-row';
      $vars['classes'][$id][] = 'views-row-' . $count;
    }
    if ($row_class_special) {
      $vars['classes'][$id][] = 'views-row-' . ($count % 2 ? 'odd' : 'even');
      if ($count == 1) {
        $vars['classes'][$id][] = 'views-row-first';
      }
      if ($count == $max) {
        $vars['classes'][$id][] = 'views-row-last';
      }
    }

    if ($row_class = $view->style_plugin->get_row_class($id)) {
      $vars['classes'][$id][] = $row_class;
    }

    // Flatten the classes to a string for each row for the template file.
    $vars['classes_array'][$id] = isset($vars['classes'][$id]) ? implode(' ', $vars['classes'][$id]) : '';
  }
}

/**
 * Display the view as an HTML list element
 */
function template_preprocess_views_view_list(&$vars) {
  $handler  = $vars['view']->style_plugin;

  $class = explode(' ', $handler->options['class']);
  $class = array_map('views_clean_css_identifier', $class);

  $wrapper_class = explode(' ', $handler->options['wrapper_class']);
  $wrapper_class = array_map('views_clean_css_identifier', $wrapper_class);

  $vars['class'] = implode(' ', $class);
  $vars['wrapper_class'] = implode(' ', $wrapper_class);
  $vars['wrapper_prefix'] = '';
  $vars['wrapper_suffix'] = '';
  $vars['list_type_prefix'] = '<' . $handler->options['type'] . '>';
  $vars['list_type_suffix'] = '</' . $handler->options['type'] . '>';
  if ($vars['wrapper_class']) {
    $vars['wrapper_prefix'] = '<div class="' . $vars['wrapper_class'] . '">';
    $vars['wrapper_suffix'] = '</div>';
  }

  if ($vars['class']) {
    $vars['list_type_prefix'] = '<' . $handler->options['type'] . ' class="' . $vars['class'] . '">';
  }
  template_preprocess_views_view_unformatted($vars);
}

/**
 * Preprocess an RSS feed
 */
function template_preprocess_views_view_rss(&$vars) {
  global $base_url;
  global $language;

  $view     = &$vars['view'];
  $options  = &$vars['options'];
  $items    = &$vars['rows'];

  $style    = &$view->style_plugin;

  // The RSS 2.0 "spec" doesn't indicate HTML can be used in the description.
  // We strip all HTML tags, but need to prevent double encoding from properly
  // escaped source data (such as &amp becoming &amp;amp;).
  $vars['description'] = check_plain(decode_entities(strip_tags($style->get_description())));

  if ($view->display_handler->get_option('sitename_title')) {
    $title = variable_get('site_name', 'Drupal');
    if ($slogan = variable_get('site_slogan', '')) {
      $title .= ' - ' . $slogan;
    }
  }
  else {
    $title = $view->get_title();
  }
  $vars['title'] = check_plain($title);

  // Figure out which display which has a path we're using for this feed. If there isn't
  // one, use the global $base_url
  $link_display_id = $view->display_handler->get_link_display();
  if ($link_display_id && !empty($view->display[$link_display_id])) {
    $path = $view->display[$link_display_id]->handler->get_path();
  }

  if ($path) {
    $path = $view->get_url(NULL, $path);
    $url_options = array('absolute' => TRUE);
    if (!empty($view->exposed_raw_input)) {
      $url_options['query'] = $view->exposed_raw_input;
    }

    // Compare the link to the default home page; if it's the default home page, just use $base_url.
    if ($path == variable_get('site_frontpage', 'node')) {
      $path = '';
    }

    $vars['link'] = check_url(url($path, $url_options));
  }

  $vars['langcode'] = check_plain($language->language);
  $vars['namespaces'] = drupal_attributes($style->namespaces);
  $vars['items'] = $items;
  $vars['channel_elements'] = format_xml_elements($style->channel_elements);

  // During live preview we don't want to output the header since the contents
  // of the feed are being displayed inside a normal HTML page.
  if (empty($vars['view']->live_preview)) {
    drupal_add_http_header('Content-Type', 'application/rss+xml; charset=utf-8');
  }
}

/**
 * Default theme function for all RSS rows.
 */
function template_preprocess_views_view_row_rss(&$vars) {
  $view     = &$vars['view'];
  $options  = &$vars['options'];
  $item     = &$vars['row'];

  $vars['title'] = check_plain($item->title);
  $vars['link'] = check_url($item->link);
  $vars['description'] = check_plain($item->description);
  $vars['item_elements'] = empty($item->elements) ? '' : format_xml_elements($item->elements);
}

/**
 * Default theme function for all filter forms.
 */
function template_preprocess_views_exposed_form(&$vars) {
  $form = &$vars['form'];

  // Put all single checkboxes together in the last spot.
  $checkboxes = '';

  if (!empty($form['q'])) {
    $vars['q'] = drupal_render($form['q']);
  }

  $vars['widgets'] = array();
  foreach ($form['#info'] as $id => $info) {
    // Set aside checkboxes.
    if (isset($form[$info['value']]['#type']) && $form[$info['value']]['#type'] == 'checkbox') {
      $checkboxes .= drupal_render($form[$info['value']]);
      continue;
    }
    $widget = new stdClass;
    // set up defaults so that there's always something there.
    $widget->label = $widget->operator = $widget->widget = NULL;

    $widget->id = isset($form[$info['value']]['#id']) ? $form[$info['value']]['#id'] : '';
    if (!empty($info['label'])) {
      $widget->label = check_plain($info['label']);
    }
    if (!empty($info['operator'])) {
      $widget->operator = drupal_render($form[$info['operator']]);
    }
    $widget->widget = drupal_render($form[$info['value']]);
    $vars['widgets'][$id] = $widget;
  }

  // Wrap up all the checkboxes we set aside into a widget.
  if ($checkboxes) {
    $widget = new stdClass;
    // set up defaults so that there's always something there.
    $widget->label = $widget->operator = $widget->widget = NULL;
    $widget->id = 'checkboxes';
    $widget->widget = $checkboxes;
    $vars['widgets']['checkboxes'] = $widget;
  }

  if (isset($form['sort_by'])) {
    $vars['sort_by'] = drupal_render($form['sort_by']);
    $vars['sort_order'] = drupal_render($form['sort_order']);
  }
  if (isset($form['items_per_page'])) {
    $vars['items_per_page'] = drupal_render($form['items_per_page']);
  }
  if (isset($form['offset'])) {
    $vars['offset'] = drupal_render($form['offset']);
  }
  if (isset($form['reset'])) {
    $vars['reset_button'] = drupal_render($form['reset']);
  }
  // This includes the submit button.
  $vars['button'] = drupal_render_children($form);
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
  $substitutions = module_invoke_all('views_form_substitutions');
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
  $quantity = $vars['quantity'];

  // Calculate various markers within this pager piece:
  // Middle is used to "center" pages around the current page.
  $pager_middle = ceil($quantity / 2);
  // current is the page we are currently paged to
  $pager_current = $pager_page_array[$element] + 1;
  // max is the maximum page number
  $pager_max = $pager_total[$element];
  // End of marker calculations.



  if ($pager_total[$element] > 1) {

    $li_previous = theme('pager_previous',
      array(
        'text' => (isset($tags[1]) ? $tags[1] : t('‹‹')),
        'element' => $element,
        'interval' => 1,
        'parameters' => $parameters,
      )
    );
    if (empty($li_previous)) {
      $li_previous = "&nbsp;";
    }

    $li_next = theme('pager_next',
      array(
        'text' => (isset($tags[3]) ? $tags[3] : t('››')),
        'element' => $element,
        'interval' => 1,
        'parameters' => $parameters,
      )
    );

    if (empty($li_next)) {
      $li_next = "&nbsp;";
    }

    $items[] = array(
      'class' => array('pager-previous'),
      'data' => $li_previous,
    );

    $items[] = array(
      'class' => array('pager-current'),
      'data' => t('@current of @max', array('@current' => $pager_current, '@max' => $pager_max)),
    );

    $items[] = array(
      'class' => array('pager-next'),
      'data' => $li_next,
    );
    return theme('item_list',
      array(
        'items' => $items,
        'title' => NULL,
        'type' => 'ul',
        'attributes' => array('class' => array('pager')),
      )
    );
  }
}

/**
 * Generic <div> container function.
 */
function theme_views_container($variables) {
  $element = $variables['element'];
  return '<div' . drupal_attributes($element['#attributes']) . '>' . $element['#children'] . '</div>';
}

/**
 * @defgroup views_templates Views template files
 * @{
 * All views templates can be overridden with a variety of names, using
 * the view, the display ID of the view, the display type of the view,
 * or some combination thereof.
 *
 * For each view, there will be a minimum of two templates used. The first
 * is used for all views: views-view.tpl.php.
 *
 * The second template is determined by the style selected for the view. Note
 * that certain aspects of the view can also change which style is used; for
 * example, arguments which provide a summary view might change the style to
 * one of the special summary styles.
 *
 * The default style for all views is views-view-unformatted.tpl.php
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
 * - views-view.tpl.php
 *
 * - views-view-unformatted--foobar--page.tpl.php
 * - views-view-unformatted--page.tpl.php
 * - views-view-unformatted--foobar.tpl.php
 * - views-view-unformatted.tpl.php
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
