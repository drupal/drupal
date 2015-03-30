<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

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
  $view = $variables['view'];
  $id = $view->storage->id();

  $variables['css_name'] = Html::cleanCssIdentifier($id);
  $variables['id'] = $id;
  $variables['display_id'] = $view->current_display;
  // Override the title to be empty by default. For example, if viewing a page
  // view, 'title' will already be populated in $variables. This can still be
  // overridden to use a title when needed. See views_ui_preprocess_views_view()
  // for an example of this.
  $variables['title'] = '';

  $css_class = $view->display_handler->getOption('css_class');
  if (!empty($css_class)) {
    $variables['css_class'] = preg_replace('/[^a-zA-Z0-9- ]/', '-', $css_class);
    $variables['attributes']['class'][] = $variables['css_class'];
  }

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
    // drupalSettings and the DIV wrapper.
    $variables['dom_id'] = $view->dom_id;
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
  /** @var \Drupal\views\ResultRow $row */
  $row = $variables['row'];
  foreach ($view->field as $id => $field) {
    // render this even if set to exclude so it can be used elsewhere.
    $field_output = $view->style_plugin->getField($row->index, $id);
    $empty = $field->isValueEmpty($field_output, $field->options['empty_zero']);
    if (empty($field->options['exclude']) && (!$empty || (empty($field->options['hide_empty']) && empty($variables['options']['hide_empty'])))) {
      $object = new stdClass();
      $object->handler = $view->field[$id];
      $object->inline = !empty($variables['options']['inline'][$id]);

      $object->element_type = $object->handler->elementType(TRUE, !$variables['options']['default_field_elements'], $object->inline);
      if ($object->element_type) {
        $attributes = array();
        if ($object->handler->options['element_default_classes']) {
          $attributes['class'][] = 'field-content';
        }

        if ($classes = $object->handler->elementClasses($row->index)) {
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
      if (isset($view->field[$id]->field_alias) && isset($row->{$view->field[$id]->field_alias})) {
        $object->raw = $row->{$view->field[$id]->field_alias};
      }
      else {
        $object->raw = NULL; // make sure it exists to reduce NOTICE
      }

      if (!empty($variables['options']['separator']) && $previous_inline && $object->inline && $object->content) {
        $object->separator = Xss::filterAdmin($variables['options']['separator']);
      }

      $object->class = Html::cleanCssIdentifier($id);

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

        if ($classes = $object->handler->elementWrapperClasses($row->index)) {
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
      $object->label = SafeMarkup::checkPlain($view->field[$id]->label());
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

          $element_label_class = $object->handler->elementLabelClasses($row->index);
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
 *
 * @ingroup themeable
 */
function theme_views_view_fields($variables) {
  $fields = $variables['fields'];
  $output = '';

  foreach ($fields as $field) {
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
 *
 * @ingroup themeable
 */
function theme_views_view_field($variables) {
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
  /** @var \Drupal\views\ViewExecutable $view */
  $view = $variables['view'];
  $argument = $view->argument[$view->build_info['summary_level']];

  $url_options = array();

  if (!empty($view->exposed_raw_input)) {
    $url_options['query'] = $view->exposed_raw_input;
  }

  $active_urls = array(
    // Force system path.
    \Drupal::url('<current>', [], ['alias' => TRUE]),
    // Force system path.
    Url::fromRouteMatch(\Drupal::routeMatch())->setOption('alias', TRUE)->toString(),
    // Could be an alias.
    \Drupal::url('<current>'),
    // Could be an alias.
    Url::fromRouteMatch(\Drupal::routeMatch())->toString(),
  );
  $active_urls = array_combine($active_urls, $active_urls);

  // Collect all arguments foreach row, to be able to alter them for example
  // by the validator. This is not done per single argument value, because this
  // could cause performance problems.
  $row_args = array();

  foreach ($variables['rows'] as $id => $row) {
    $row_args[$id] = $argument->summaryArgument($row);
  }
  $argument->processSummaryArguments($row_args);

  foreach ($variables['rows'] as $id => $row) {
    $variables['rows'][$id]->attributes = array();
    $variables['rows'][$id]->link = $argument->summaryName($row);
    $args = $view->args;
    $args[$argument->position] = $row_args[$id];

    if (!empty($argument->options['summary_options']['base_path'])) {
      $base_path = $argument->options['summary_options']['base_path'];
      $tokens = $this->getArgumentsTokens();
      $base_path = $this->viewsTokenReplace($base_path, $tokens);
      // @todo Views should expect and store a leading /. See:
      //   https://www.drupal.org/node/2423913
      $url = Url::fromUserInput('/' . $base_path);
    }
    else {
      $url = $view->getUrl($args)->setOptions($url_options);
    }
    $variables['rows'][$id]->url = $url->toString();
    $variables['rows'][$id]->count = intval($row->{$argument->count_alias});
    $variables['rows'][$id]->attributes = new Attribute($variables['rows'][$id]->attributes);
    $variables['rows'][$id]->active = isset($active_urls[$variables['rows'][$id]->url]);
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

  $url_options = array();

  if (!empty($view->exposed_raw_input)) {
    $url_options['query'] = $view->exposed_raw_input;
  }

  $count = 0;
  $active_urls = array(
    // Force system path.
    \Drupal::url('<current>', [], ['alias' => TRUE]),
    // Could be an alias.
    \Drupal::url('<current>'),
  );
  $active_urls = array_combine($active_urls, $active_urls);

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
      $variables['rows'][$id]->separator = Xss::filterAdmin($variables['options']['separator']);
    }
    $variables['rows'][$id]->attributes = array();
    $variables['rows'][$id]->link = $argument->summaryName($row);
    $args = $view->args;
    $args[$argument->position] = $row_args[$id];

    if (!empty($argument->options['summary_options']['base_path'])) {
      $base_path = $argument->options['summary_options']['base_path'];
      $tokens = $this->getArgumentsTokens();
      $base_path = $this->viewsTokenReplace($base_path, $tokens);
      // @todo Views should expect and store a leading /. See:
      //   https://www.drupal.org/node/2423913
      $url = Url::fromUserInput('/' . $base_path);
    }
    else {
      $url = $view->getUrl($args)->setOptions($url_options);
    }
    $variables['rows'][$id]->url = $url->toString();
    $variables['rows'][$id]->count = intval($row->{$argument->count_alias});
    $variables['rows'][$id]->active = isset($active_urls[$variables['rows'][$id]->url]);
    $variables['rows'][$id]->attributes = new Attribute($variables['rows'][$id]->attributes);
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
  $variables['header'] = array();

  $options = $view->style_plugin->options;
  $handler = $view->style_plugin;

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

  // A boolean to store whether the table's header has any labels.
  $has_header_labels = FALSE;
  foreach ($columns as $field => $column) {
    // Create a second variable so we can easily find what fields we have and
    // what the CSS classes should be.
    $variables['fields'][$field] = Html::cleanCssIdentifier($field);
    if ($active == $field) {
      $variables['fields'][$field] .= ' active';
    }

    // Render the header labels.
    if ($field == $column && empty($fields[$field]->options['exclude'])) {
      $label = SafeMarkup::checkPlain(!empty($fields[$field]) ? $fields[$field]->label() : '');
      if (empty($options['info'][$field]['sortable']) || !$fields[$field]->clickSortable()) {
        $variables['header'][$field]['content'] = $label;
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
          $markup = drupal_render($tablesort_indicator);
          $label = SafeMarkup::set($label . $markup);
        }

        $query['order'] = $field;
        $query['sort'] = $initial;
        $link_options = array(
          'attributes' => array('title' => $title),
          'query' => $query,
        );
        $variables['header'][$field]['content'] = \Drupal::l($label, new Url('<current>', [], $link_options));
      }

      $variables['header'][$field]['default_classes'] = $fields[$field]->options['element_default_classes'];
      // Set up the header label class.
      $variables['header'][$field]['attributes'] = array();
      $class = $fields[$field]->elementLabelClasses(0);
      if ($class) {
        $variables['header'][$field]['attributes']['class'][] = $class;
      }
      // Add responsive header classes.
      if (!empty($options['info'][$field]['responsive'])) {
        $variables['header'][$field]['attributes']['class'][] = $options['info'][$field]['responsive'];
        $responsive = TRUE;
      }
      // Add a CSS align class to each field if one was set.
      if (!empty($options['info'][$field]['align'])) {
        $variables['header'][$field]['attributes']['class'][] = Html::cleanCssIdentifier($options['info'][$field]['align']);
      }
      // Add a header label wrapper if one was selected.
      if ($variables['header'][$field]['content']) {
        $element_label_type = $fields[$field]->elementLabelType(TRUE, TRUE);
        if ($element_label_type) {
          $variables['header'][$field]['content'] = '<' . $element_label_type . '>' . $variables['header'][$field]['content'] . '</' . $element_label_type . '>';
        }
        // Improves accessibility of complex tables.
        $variables['header'][$field]['attributes']['id'] = Html::getUniqueId('view-' . $field . '-table-column');
      }
      // Check if header label is not empty.
      if (!empty($variables['header'][$field]['content'])) {
        $has_header_labels = TRUE;
      }

      $variables['header'][$field]['attributes'] = new Attribute($variables['header'][$field]['attributes']);
    }

    // Add a CSS align class to each field if one was set.
    if (!empty($options['info'][$field]['align'])) {
      $variables['fields'][$field] .= ' ' . Html::cleanCssIdentifier($options['info'][$field]['align']);
    }

    // Render each field into its appropriate column.
    foreach ($result as $num => $row) {

      // Skip building the attributes and content if the field is to be excluded
      // from the display.
      if (!empty($fields[$field]->options['exclude'])) {
        continue;
      }

      // Reference to the column in the loop to make the code easier to read.
      $column_reference =& $variables['rows'][$num]['columns'][$column];

      $column_reference['default_classes'] = $fields[$field]->options['element_default_classes'];

      // Set the field key to the column so it can be used for adding classes
      // in a template.
      $column_reference['fields'][] = $variables['fields'][$field];

      // Add field classes.
      if (!isset($column_reference['attributes'])) {
        $column_reference['attributes'] = array();
      }

      if ($classes = $fields[$field]->elementClasses($num)) {
        $column_reference['attributes']['class'][] = $classes;
      }

      // Add responsive header classes.
      if (!empty($options['info'][$field]['responsive'])) {
        $column_reference['attributes']['class'][] = $options['info'][$field]['responsive'];
      }

      // Improves accessibility of complex tables.
      if (isset($variables['header'][$field]['attributes']['id'])) {
        $column_reference['attributes']['headers'] = array($variables['header'][$field]['attributes']['id']);
      }

      if (!empty($fields[$field])) {
        $field_output = $handler->getField($num, $field);
        $element_type = $fields[$field]->elementType(TRUE, TRUE);
        if ($element_type) {
          $field_output = SafeMarkup::set('<' . $element_type . '>' .  SafeMarkup::escape($field_output) . '</' . $element_type . '>');
        }

        // Only bother with separators and stuff if the field shows up.
        if (!empty($field_output)) {
          // Place the field into the column, along with an optional separator.
          if (!empty($column_reference['content'])) {
            if (!empty($options['info'][$column]['separator'])) {
              $safe_content = SafeMarkup::escape($column_reference['content']);
              $safe_separator = Xss::filterAdmin($options['info'][$column]['separator']);
              $column_reference['content'] = SafeMarkup::set($safe_content . $safe_separator);
            }
          }
          else {
            $column_reference['content'] = '';
          }
          $safe_content = SafeMarkup::escape($column_reference['content']);
          $safe_field_output = SafeMarkup::escape($field_output);
          $column_reference['content'] = SafeMarkup::set($safe_content . $safe_field_output);
        }
      }
      $column_reference['attributes'] = new Attribute($column_reference['attributes']);
    }

    // Remove columns if the option is hide empty column is checked and the
    // field is not empty.
    if (!empty($options['info'][$field]['empty_column'])) {
      $empty = TRUE;
      foreach ($variables['rows'] as $columns) {
        $empty &= empty($columns[$column]);
      }
      if ($empty) {
        foreach ($variables['rows'] as &$column_items) {
          unset($column_items[$column]);
          unset($variables['header'][$column]);
        }
      }
    }
  }

  // Hide table header if all labels are empty.
  if (!$has_header_labels) {
    $variables['header'] = array();
  }

  foreach ($variables['rows'] as $num => $row) {
    $variables['rows'][$num]['attributes'] = array();
    if ($row_class = $handler->getRowClass($num)) {
      $variables['rows'][$num]['attributes']['class'][] = $row_class;
    }
    $variables['rows'][$num]['attributes'] = new Attribute($variables['rows'][$num]['attributes']);
  }

  if (empty($variables['rows']) && !empty($options['empty_table'])) {
    $build = $view->display_handler->renderArea('empty');
    $variables['rows'][0]['columns'][0]['content'] = drupal_render($build);
    $variables['rows'][0]['attributes'] = new Attribute(array('class' => 'odd'));
    // Calculate the amounts of rows with output.
    $variables['rows'][0]['columns'][0]['attributes'] = new Attribute(array(
      'colspan' => count($variables['header']),
      'class' => 'views-empty',
    ));
  }

  $variables['sticky'] = FALSE;
  if (!empty($options['sticky'])) {
    $variables['view']->element['#attached']['library'][] = 'core/drupal.tableheader';
    $variables['sticky'] = TRUE;
  }

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

  $variables['responsive'] = FALSE;
  // If the table has headers and it should react responsively to columns hidden
  // with the classes represented by the constants RESPONSIVE_PRIORITY_MEDIUM
  // and RESPONSIVE_PRIORITY_LOW, add the tableresponsive behaviors.
  if (isset($variables['header']) && $responsive) {
    $variables['view']->element['#attached']['library'][] = 'core/drupal.tableresponsive';
    // Add 'responsive-enabled' class to the table to identify it for JS.
    // This is needed to target tables constructed by this function.
    $variables['responsive'] = TRUE;
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

  $col = 0;
  $row = 0;
  $items = array();
  $remainders = count($variables['rows']) % $options['columns'];
  $num_rows = floor(count($variables['rows']) / $options['columns']);

  // Iterate over each rendered views result row.
  foreach ($variables['rows'] as $result_index => $item) {

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
      // Add custom row classes.
      $row_class = array_filter(explode(' ', $variables['view']->style_plugin->getCustomClass($result_index, 'row')));
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
      // Add custom column classes.
      $col_class = array_filter(explode(' ', $variables['view']->style_plugin->getCustomClass($result_index, 'col')));
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

  $variables['default_row_class'] = !empty($options['default_row_class']);
  foreach ($rows as $id => $row) {
    $variables['rows'][$id] = array();
    $variables['rows'][$id]['content'] = $row;
    $variables['rows'][$id]['attributes'] = array();
    $variables['rows'][$id]['attributes'] = new Attribute($variables['rows'][$id]['attributes']);
    if ($row_class = $view->style_plugin->getRowClass($id)) {
      $variables['rows'][$id]['attributes']->addClass($row_class);
    }
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
  if ($handler->options['class']) {
    $class = explode(' ', $handler->options['class']);
    $class = array_map('\Drupal\Component\Utility\Html::cleanCssIdentifier', $class);

    // Initialize a new attribute class for $class.
    $variables['list']['attributes'] = new Attribute(array('class' => $class));
  }

  // Fetch wrapper classes from handler options.
  if ($handler->options['wrapper_class']) {
    $wrapper_class = explode(' ', $handler->options['wrapper_class']);
    $variables['attributes']['class'] = array_map('\Drupal\Component\Utility\Html::cleanCssIdentifier', $wrapper_class);
  }

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
  $view  = $variables['view'];
  $items = $variables['rows'];
  $style = $view->style_plugin;

  $config = \Drupal::config('system.site');

  // The RSS 2.0 "spec" doesn't indicate HTML can be used in the description.
  // We strip all HTML tags, but need to prevent double encoding from properly
  // escaped source data (such as &amp becoming &amp;amp;).
  $variables['description'] = SafeMarkup::checkPlain(Html::decodeEntities(strip_tags($style->getDescription())));

  if ($view->display_handler->getOption('sitename_title')) {
    $title = $config->get('name');
    if ($slogan = $config->get('slogan')) {
      $title .= ' - ' . $slogan;
    }
  }
  else {
    $title = $view->getTitle();
  }
  $variables['title'] = SafeMarkup::checkPlain($title);

  // Figure out which display which has a path we're using for this feed. If
  // there isn't one, use the global $base_url
  $link_display_id = $view->display_handler->getLinkDisplay();
  if ($link_display_id && $display = $view->displayHandlers->get($link_display_id)) {
    $url = $view->getUrl(NULL, $link_display_id);
  }

  /** @var \Drupal\Core\Url $url */
  if ($url) {
    $url_options = array('absolute' => TRUE);
    if (!empty($view->exposed_raw_input)) {
      $url_options['query'] = $view->exposed_raw_input;
    }

    // Compare the link to the default home page; if it's the default home page,
    // just use $base_url.
    $url_string = $url->setOptions($url_options)->toString();
    // @todo Should page.front be stored with a leading slash? See
    //   https://www.drupal.org/node/2430595.
    if ($url_string === Url::fromUserInput('/' . $config->get('page.front'))->toString()) {
      $url_string = Url::fromRoute('<front>')->setAbsolute()->toString();
    }

    $variables['link'] = $url_string;
  }

  $variables['langcode'] = SafeMarkup::checkPlain(\Drupal::languageManager()->getCurrentLanguage()->getId());
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

  $variables['title'] = SafeMarkup::checkPlain($item->title);
  $variables['link'] = $item->link;
  $variables['description'] = SafeMarkup::checkPlain($item->description);
  $variables['item_elements'] = empty($item->elements) ? '' : format_xml_elements($item->elements);
}

/**
 * Prepares variables for OPML feed templates.
 *
 * Default template: views-view-opml.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_view_opml(&$variables) {
  $view  = $variables['view'];
  $items = $variables['rows'];

  $config = \Drupal::config('system.site');

  if ($view->display_handler->getOption('sitename_title')) {
    $title = $config->get('name');
    if ($slogan = $config->get('slogan')) {
      $title .= ' - ' . $slogan;
    }
  }
  else {
    $title = $view->getTitle();
  }
  $variables['title'] = SafeMarkup::checkPlain($title);
  $variables['items'] = $items;
  $variables['updated'] = gmdate(DATE_RFC2822, REQUEST_TIME);

  // During live preview we don't want to output the header since the contents
  // of the feed are being displayed inside a normal HTML page.
  if (empty($variables['view']->live_preview)) {
    $variables['view']->getResponse()->headers->set('Content-Type', 'text/xml; charset=utf-8');
  }
}

/**
 * Prepares variables for views OPML item templates.
 *
 * Default template: views-view-row-opml.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - row: The raw results rows.
 */
function template_preprocess_views_view_row_opml(&$variables) {
  $item = $variables['row'];

  $variables['attributes'] = new Attribute($item);
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

  foreach ($form['#info'] as $info) {
    if (!empty($info['label'])) {
      $form[$info['value']]['#title'] = $info['label'];
    }
    if (!empty($info['description'])) {
      $form[$info['value']]['#description'] = $info['description'];
    }
  }
}

/**
 * Prepares variables for views mini-pager templates.
 *
 * Default template: views-mini-pager.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - tags: Provides link text for the next/previous links.
 *   - element: The pager's id.
 *   - parameters: Any extra GET parameters that should be retained, such as
 *     exposed input.
 */
function template_preprocess_views_mini_pager(&$variables) {
  global $pager_page_array, $pager_total;

  $tags = &$variables['tags'];
  $element = $variables['element'];
  $parameters = $variables['parameters'];

  // Current is the page we are currently paged to.
  $variables['items']['current'] = $pager_page_array[$element] + 1;

  if ($pager_total[$element] > 1 && $pager_page_array[$element] > 0) {
    $options = array(
      'query' => pager_query_add_page($parameters, $element, $pager_page_array[$element] - 1),
    );
    $variables['items']['previous']['href'] = \Drupal::url('<current>', [], $options);
    if (isset($tags[1])) {
      $variables['items']['previous']['text'] = $tags[1];
    }
    $variables['items']['previous']['attributes'] = new Attribute();
  }

  $li_next = array();
  if ($pager_page_array[$element] < ($pager_total[$element] - 1)) {
    $options = array(
      'query' => pager_query_add_page($parameters, $element, $pager_page_array[$element] + 1),
    );
    $variables['items']['next']['href'] = \Drupal::url('<current>', [], $options);
    if (isset($tags[3])) {
      $variables['items']['next']['text'] = $tags[3];
    }
    $variables['items']['next']['attributes'] = new Attribute();
  }
}

/**
 * @defgroup views_templates Views template files
 * @{
 * Describes various views templates & overriding options.
 *
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
 * style; the default row style is views-view-fields.html.twig.
 *
 * Here is an example of all the templates that will be tried in the following
 * case:
 *
 * View, named foobar. Style: unformatted. Row style: Fields. Display: Page.
 *
 * - views-view--foobar--page.html.twig
 * - views-view--page.html.twig
 * - views-view--foobar.html.twig
 * - views-view.html.twig
 *
 * - views-view-unformatted--foobar--page.html.twig
 * - views-view-unformatted--page.html.twig
 * - views-view-unformatted--foobar.html.twig
 * - views-view-unformatted.html.twig
 *
 * - views-view-fields--foobar--page.html.twig
 * - views-view-fields--page.html.twig
 * - views-view-fields--foobar.html.twig
 * - views-view-fields.html.twig
 *
 * Important! When adding a new template to your theme, be sure to flush the
 * theme registry cache!
 *
 * @see \Drupal\views\ViewExecutable::buildThemeFunctions()
 * @}
 */
