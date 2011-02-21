<?php
// $Id: theme.inc,v 1.84.4.43 2011/01/04 00:00:20 dereine Exp $

/**
 * @file theme.inc
 *
 * An array of preprocessors to fill variables for templates and helper
 * functions to make theming easier.
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

  $vars['rows']       = !empty($view->result) || !empty($view->style_plugin->definition['even empty']) ? $view->style_plugin->render($view->result) : '';

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
  $vars['pager']      = $view->query->render_pager();

  $vars['attachment_before'] = !empty($view->attachment_before) ? $view->attachment_before : '';
  $vars['attachment_after'] = !empty($view->attachment_after) ? $view->attachment_after : '';

  // if administrator, add some links. These used to be tabs, but this is better.
  if (user_access('administer views') && module_exists('views_ui') && module_exists('contextual') && empty($view->hide_admin_links) && !variable_get('views_no_hover_links', FALSE)) {
    $links = array(
      'views-edit' => array(
        'title' => t('Edit view'),
        'attributes' => array('title' => t("Edit this view")),
        'href' => "admin/structure/views/edit/$view->name",
        'fragment' => 'views-tab-' . $view->current_display,
        'query' => drupal_get_destination(),
      ),
    );
    drupal_alter('views_admin_links', $links, $view);

    $build = array(
      '#prefix' => '<div class="contextual-links-wrapper">',
      '#suffix' => '</div>',
      '#theme' => 'links__contextual',
      '#links' => $links,
      '#attributes' => array('class' => array('contextual-links')),
      '#attached' => array(
        'library' => array(array('contextual', 'contextual-links')),
      ),
    );
    $vars['classes_array'][] = 'contextual-links-region';
    $vars['admin_links'] = drupal_render($build);
  }
  else {
    $vars['admin_links'] = '';
    $vars['admin_links_raw'] = array();
  }

  // Attachments are always updated with the outer view, never by themselves,
  // so they do not have dom ids.
  if (empty($view->is_attachment)) {
    // Our JavaScript needs to have some means to find the HTML belonging to this
    // view.
    //
    // It is true that the DIV wrapper has classes denoting the name of the view
    // and its display ID, but this is not enough to unequivocally match a view
    // with its HTML, because one view may appear several times on the page. So
    // we set up a running counter, $dom_id, to issue a "unique" identifier for
    // each view. This identifier is written to both Drupal.settings and the DIV
    // wrapper.
    static $dom_id = 1;
    $vars['dom_id'] = !empty($view->dom_id) ? $view->dom_id : $dom_id++;
    $vars['classes_array'][] = 'view-dom-id-' . $vars['dom_id'];
  }

  // If using AJAX, send identifying data about this view.
  if ($view->use_ajax && empty($view->is_attachment)) {
    $settings = array(
      'views' => array(
        'ajax_path' => url('views/ajax'),
        'ajaxViews' => array(
          array(
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
  // Flatten the classes to a string for the template file.
  $vars['classes'] = implode(' ', $vars['classes_array']);
}

/**
 * Preprocess theme function to print a single record from a row, with fields
 */
function template_preprocess_views_view_fields(&$vars) {
  $view = $vars['view'];

  // Loop through the fields for this view.
  $inline = FALSE;
  $vars['fields'] = array(); // ensure it's at least an empty array.
  foreach ($view->field as $id => $field) {
    // render this even if set to exclude so it can be used elsewhere.
    $field_output = $view->style_plugin->get_field($view->row_index, $id);
    $empty = $field_output !== 0 && empty($field_output);
    if (empty($field->options['exclude']) && (!$empty || (empty($field->options['hide_empty']) && empty($vars['options']['hide_empty'])))) {
      $object = new stdClass();
      $object->handler = &$view->field[$id];

      $object->element_type = $object->handler->element_type(TRUE);
      if ($object->element_type) {
        $class = '';
        if ($object->handler->options['element_default_classes']) {
          $class = 'field-content';
        }

        if ($classes = $object->handler->element_classes()) {
          if ($class) {
            $class .= ' ';
          }
          $class .=  $classes;
        }

        $field_output = '<' . $object->element_type . ' class="' . $class . '">' . $field_output . '</' . $object->element_type . '>';
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

      if (!empty($vars['options']['separator']) && $inline && $object->inline && $object->content) {
        $object->separator = filter_xss_admin($vars['options']['separator']);
      }

      $object->class = drupal_clean_css_identifier($id);
      $object->inline = !empty($vars['options']['inline'][$id]);
      $inline = $object->inline;
      $object->inline_html = $object->handler->element_wrapper_type(TRUE, TRUE);

      if ($object->inline_html === '') {
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

        if ($classes = $object->handler->element_wrapper_classes()) {
          if ($class) {
            $class .= ' ';
          }
          $class .= ' ' . $classes;
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
      $object->element_label_type = $object->handler->element_label_type(TRUE);
      if ($object->element_label_type && $object->label) {
        $class = '';
        if ($object->handler->options['element_default_classes']) {
          $class = 'views-label-' . $object->class;
        }

        $element_label_class = $object->handler->element_label_classes();
        if ($element_label_class) {
          if ($class) {
            $class .= ' ';
          }

          $class .= $element_label_class;
        }

        $object->label_html = '<' . $object->element_label_type . ' class="' . $class . '">';
        $object->label_html .= $object->label;
        if ($object->handler->options['element_label_colon']) {
          $object->label_html .= ': ';
        }
        $object->label_html .= '</' . $object->element_label_type . '>';
      }

      $vars['fields'][$id] = $object;
    }
  }

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

  $url_options = array();

  if (!empty($view->exposed_raw_input)) {
    $url_options['query'] = $view->exposed_raw_input;
  }
  $vars['classes'] = array();
  foreach ($vars['rows'] as $id => $row) {
    $vars['rows'][$id]->link = $argument->summary_name($row);
    $args = $view->args;
    $args[$argument->position] = $argument->summary_argument($row);

    $base_path = NULL;
    if (!empty($argument->options['style_options']['base_path'])) {
      $base_path = $argument->options['style_options']['base_path'];
    }
    $vars['rows'][$id]->url = url($view->get_url($args, $base_path), $url_options);
    $vars['rows'][$id]->count = intval($row->{$argument->count_alias});
    if ($vars['rows'][$id]->url == base_path() . $_GET['q'] || $vars['rows'][$id]->url == base_path() . drupal_get_path_alias($_GET['q'])) {
      $vars['classes'][$id] = 'active';
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
  $vars['classes'] = array();

  $url_options = array();

  if (!empty($view->exposed_raw_input)) {
    $url_options['query'] = $view->exposed_raw_input;
  }

  $count = 0;
  foreach ($vars['rows'] as $id => $row) {
    // only false on first time:
    if ($count++) {
      $vars['rows'][$id]->separator = filter_xss_admin($vars['options']['separator']);
    }
    $vars['rows'][$id]->link = $argument->summary_name($row);
    $args = $view->args;
    $args[$argument->position] = $argument->summary_argument($row);

    $base_path = NULL;
    if (!empty($argument->options['style_options']['base_path'])) {
      $base_path = $argument->options['style_options']['base_path'];
    }
    $vars['rows'][$id]->url = url($view->get_url($args, $base_path), $url_options);
    $vars['rows'][$id]->count = intval($row->{$argument->count_alias});
    if ($vars['rows'][$id]->url == base_path() . $_GET['q'] || $vars['rows'][$id]->url == base_path() . drupal_get_path_alias($_GET['q'])) {
      $vars['classes'][$id] = 'active';
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

  $options  = $view->style_plugin->options;
  $handler  = $view->style_plugin;

  $fields   = &$view->field;
  $columns  = $handler->sanitize_columns($options['columns'], $fields);

  $active   = !empty($handler->active) ? $handler->active : '';
  $order    = !empty($handler->order) ? $handler->order : 'asc';

  $query    = tablesort_get_query_parameters();

  $header = array();

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
      $class = $fields[$field]->element_label_classes();
      if ($class) {
        if ($vars['header_classes'][$field]) {
          $vars['header_classes'][$field] .= ' ';
        }
        $vars['header_classes'][$field] .= $class;
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
      if ($classes = $fields[$field]->element_classes()) {
        if ($vars['field_classes'][$field][$num]) {
          $vars['field_classes'][$field][$num] .= ' ';
        }

        $vars['field_classes'][$field][$num] .= $classes;
      }

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
  }

  $count = 0;
  foreach ($vars['rows'] as $num => $row) {
    $vars['row_classes'][$num][] = ($count++ % 2 == 0) ? 'odd' : 'even';
    if ($row_class = $handler->get_row_class($num)) {
      $vars['row_classes'][$num][] = $row_class;
    }
  }

  $vars['row_classes'][0][] = 'views-row-first';
  $vars['row_classes'][count($vars['row_classes']) - 1][] = 'views-row-last';

  $vars['classes_array'] = array('views-table');
  if (!empty($options['sticky'])) {
    drupal_add_js('misc/tableheader.js');
    $vars['classes_array'][] = "sticky-enabled";
  }
  $vars['classes_array'][] = 'cols-'. count($vars['rows']);

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
      if (!empty($handler->options['fill_single_line']) || count($rows)) {
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
    $row_classes[] =  'row-' . ($row_number + 1);
    if ($row_number == 0) {
      $row_classes[] =  'row-first';
    }
    if (count($rows) == ($row_number + 1)) {
      $row_classes[] =  'row-last';
    }
    $vars['row_classes'][$row_number] = implode(' ', $row_classes);
    foreach ($rows[$row_number] as $column_number => $item) {
      $column_classes = array();
      $column_classes[] = 'col-'. ($column_number + 1);
      if ($column_number == 0) {
        $column_classes[] = 'col-first';
      }
      elseif (count($rows[$row_number]) == ($column_number + 1)) {
        $column_classes[] = 'col-last';
      }
      if (isset($row_indexes[$row_number][$column_number]) && $column_class = $view->style_plugin->get_row_class($row_indexes[$row_number][$column_number])) {
        $column_classes[] = $column_class;
      }
      $vars['column_classes'][$row_number][$column_number] = implode(' ', $column_classes);
    }
  }
  $vars['rows'] = $rows;

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

  $vars['classes_array'] = array();
  $vars['classes'] = array();
  // Set up striping values.
  $count = 0;
  $max = count($rows);
  foreach ($rows as $id => $row) {
    $count++;
    $vars['classes'][$id][] = 'views-row';
    $vars['classes'][$id][] = 'views-row-' . $count;
    $vars['classes'][$id][] = 'views-row-' . ($count % 2 ? 'odd' : 'even');
    if ($count == 1) {
      $vars['classes'][$id][] = 'views-row-first';
    }
    if ($count == $max) {
      $vars['classes'][$id][] = 'views-row-last';
    }

    if ($row_class = $view->style_plugin->get_row_class($id)) {
      $vars['classes'][$id][] = $row_class;
    }

    // Flatten the classes to a string for each row for the template file.
    $vars['classes_array'][$id] = implode(' ', $vars['classes'][$id]);
  }
}

/**
 * Display the view as an HTML list element
 */
function template_preprocess_views_view_list(&$vars) {
  $handler  = $vars['view']->style_plugin;

  $vars['class'] = drupal_clean_css_identifier($handler->options['class']);
  $vars['wrapper_class'] = drupal_clean_css_identifier($handler->options['wrapper_class']);
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
  $vars['description'] = check_plain(decode_entities(strip_tags($options['description'])));

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

  drupal_add_http_header('Content-Type', 'application/rss+xml; charset=utf-8');
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

    $widget->id = $form[$info['value']]['#id'];
    if (!empty($info['label'])) {
      $widget->label = $info['label'];
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

  if ($pager_total[$element] > 1) {
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
 * @defgroup views_templates Views' template files
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
 * @see _views_theme_functions
 * @}
 */
