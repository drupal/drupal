<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\Core\Utility\TableSort;

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
    // Views uses its own sanitization method. This is preserved to keep
    // backwards compatibility.
    // @todo https://www.drupal.org/project/drupal/issues/2977950 Decide what to
    //   do with the backwards compatibility layer.
    $bc_classes = explode(' ', preg_replace('/[^a-zA-Z0-9- ]/', '-', $css_class));
    // Sanitize the classes using the classes using the proper API.
    $sanitized_classes = array_map('\Drupal\Component\Utility\Html::cleanCssIdentifier', explode(' ', $css_class));
    $view_classes = array_unique(array_merge($bc_classes, $sanitized_classes));
    // Merge the view display classes into any existing classes if they exist.
    $variables['attributes']['class'] = !empty($variables['attributes']['class']) ? array_merge($variables['attributes']['class'], $view_classes) : $view_classes;
    $variables['css_class'] = implode(' ', $view_classes);
  }

  // contextual_preprocess() only works on render elements, and since this theme
  // hook is not for a render element, contextual_preprocess() falls back to the
  // first argument and checks if that is a render element. The first element is
  // view_array. However, view_array does not get set anywhere, but since we do
  // have access to the View object, we can also access the View object's
  // element, which is a render element that does have #contextual_links set if
  // the display supports it. Doing this allows contextual_preprocess() to
  // access this theme hook's render element, and therefore allows this template
  // to have contextual links.
  // @see views_theme()
  $variables['view_array'] = $variables['view']->element;

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
  // Ensure it's at least an empty array.
  $variables['fields'] = [];
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
      // Set up default value of the flag that indicates whether to display a
      // colon after the label.
      $object->has_label_colon = FALSE;

      $object->element_type = $object->handler->elementType(TRUE, !$variables['options']['default_field_elements'], $object->inline);
      if ($object->element_type) {
        $attributes = [];
        if ($object->handler->options['element_default_classes']) {
          $attributes['class'][] = 'field-content';
        }

        if ($classes = $object->handler->elementClasses($row->index)) {
          $attributes['class'][] = $classes;
        }
        $object->element_attributes = new Attribute($attributes);
      }

      $object->content = $field_output;
      if (isset($view->field[$id]->field_alias) && isset($row->{$view->field[$id]->field_alias})) {
        $object->raw = $row->{$view->field[$id]->field_alias};
      }
      else {
        // Make sure it exists to reduce NOTICE.
        $object->raw = NULL;
      }

      if (!empty($variables['options']['separator']) && $previous_inline && $object->inline && $object->content) {
        $object->separator = [
          '#markup' => $variables['options']['separator'],
        ];
      }

      $object->class = Html::cleanCssIdentifier($id);

      $previous_inline = $object->inline;
      // Set up field wrapper element.
      $object->wrapper_element = $object->handler->elementWrapperType(TRUE, TRUE);
      if ($object->wrapper_element === '' && $variables['options']['default_field_elements']) {
        $object->wrapper_element = $object->inline ? 'span' : 'div';
      }

      // Set up field wrapper attributes if field wrapper was set.
      if ($object->wrapper_element) {
        $attributes = [];
        if ($object->handler->options['element_default_classes']) {
          $attributes['class'][] = 'views-field';
          $attributes['class'][] = 'views-field-' . $object->class;
        }

        if ($classes = $object->handler->elementWrapperClasses($row->index)) {
          $attributes['class'][] = $classes;
        }
        $object->wrapper_attributes = new Attribute($attributes);
      }

      // Set up field label
      $object->label = $view->field[$id]->label();

      // Set up field label wrapper and its attributes.
      if ($object->label) {
        // Add a colon in a label suffix.
        if ($object->handler->options['element_label_colon']) {
          $object->label_suffix = ': ';
          $object->has_label_colon = TRUE;
        }

        // Set up label HTML element.
        $object->label_element = $object->handler->elementLabelType(TRUE, !$variables['options']['default_field_elements']);

        // Set up label attributes.
        if ($object->label_element) {
          $attributes = [];
          if ($object->handler->options['element_default_classes']) {
            $attributes['class'][] = 'views-label';
            $attributes['class'][] = 'views-label-' . $object->class;
          }

          // Set up field label.
          $element_label_class = $object->handler->elementLabelClasses($row->index);
          if ($element_label_class) {
            $attributes['class'][] = $element_label_class;
          }
          $object->label_attributes = new Attribute($attributes);
        }
      }

      $variables['fields'][$id] = $object;
    }
  }

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

  $url_options = [];

  if (!empty($view->exposed_raw_input)) {
    $url_options['query'] = $view->exposed_raw_input;
  }

  $active_urls = [
    // Force system path.
    Url::fromRoute('<current>', [], ['alias' => TRUE])->toString(),
    // Force system path.
    Url::fromRouteMatch(\Drupal::routeMatch())->setOption('alias', TRUE)->toString(),
    // Could be an alias.
    Url::fromRoute('<current>')->toString(),
    // Could be an alias.
    Url::fromRouteMatch(\Drupal::routeMatch())->toString(),
  ];
  $active_urls = array_combine($active_urls, $active_urls);

  // Collect all arguments foreach row, to be able to alter them for example
  // by the validator. This is not done per single argument value, because this
  // could cause performance problems.
  $row_args = [];

  foreach ($variables['rows'] as $id => $row) {
    $row_args[$id] = $argument->summaryArgument($row);
  }
  $argument->processSummaryArguments($row_args);

  foreach ($variables['rows'] as $id => $row) {
    $variables['rows'][$id]->attributes = [];
    $variables['rows'][$id]->link = $argument->summaryName($row);
    $args = $view->args;
    $args[$argument->position] = $row_args[$id];

    if (!empty($argument->options['summary_options']['base_path'])) {
      $base_path = $argument->options['summary_options']['base_path'];
      $tokens = $view->getDisplay()->getArgumentsTokens();
      $base_path = $argument->globalTokenReplace($base_path, $tokens);
      // @todo Views should expect and store a leading /. See:
      //   https://www.drupal.org/node/2423913
      $url = Url::fromUserInput('/' . $base_path);
      try {
        /** @var \Symfony\Component\Routing\Route $route */
        $route_name = $url->getRouteName();
        $route = \Drupal::service('router.route_provider')->getRouteByName($route_name);

        $route_variables = $route->compile()->getVariables();
        $parameters = $url->getRouteParameters();

        foreach ($route_variables as $variable_name) {
          $parameters[$variable_name] = array_shift($args);
        }

        $url->setRouteParameters($parameters);
      }
      catch (Exception $e) {
        // If the given route doesn't exist, default to <front>
        $url = Url::fromRoute('<front>');
      }
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
  /** @var \Drupal\views\ViewExecutable $view */
  $view = $variables['view'];
  $argument = $view->argument[$view->build_info['summary_level']];

  $url_options = [];

  if (!empty($view->exposed_raw_input)) {
    $url_options['query'] = $view->exposed_raw_input;
  }

  $count = 0;
  $active_urls = [
    // Force system path.
    Url::fromRoute('<current>', [], ['alias' => TRUE])->toString(),
    // Could be an alias.
    Url::fromRoute('<current>')->toString(),
  ];
  $active_urls = array_combine($active_urls, $active_urls);

  // Collect all arguments for each row, to be able to alter them for example
  // by the validator. This is not done per single argument value, because
  // this could cause performance problems.
  $row_args = [];
  foreach ($variables['rows'] as $id => $row) {
    $row_args[$id] = $argument->summaryArgument($row);
  }
  $argument->processSummaryArguments($row_args);

  foreach ($variables['rows'] as $id => $row) {
    // Only false on first time.
    if ($count++) {
      $variables['rows'][$id]->separator = Xss::filterAdmin($variables['options']['separator']);
    }
    $variables['rows'][$id]->attributes = [];
    $variables['rows'][$id]->link = $argument->summaryName($row);
    $args = $view->args;
    $args[$argument->position] = $row_args[$id];

    if (!empty($argument->options['summary_options']['base_path'])) {
      $base_path = $argument->options['summary_options']['base_path'];
      $tokens = $view->getDisplay()->getArgumentsTokens();
      $base_path = $argument->globalTokenReplace($base_path, $tokens);
      // @todo Views should expect and store a leading /. See:
      //   https://www.drupal.org/node/2423913
      $url = Url::fromUserInput('/' . $base_path);
      try {
        /** @var \Symfony\Component\Routing\Route $route */
        $route = \Drupal::service('router.route_provider')->getRouteByName($url->getRouteName());
        $route_variables = $route->compile()->getVariables();
        $parameters = $url->getRouteParameters();

        foreach ($route_variables as $variable_name) {
          $parameters[$variable_name] = array_shift($args);
        }

        $url->setRouteParameters($parameters);
      }
      catch (Exception $e) {
        // If the given route doesn't exist, default to <front>
        $url = Url::fromRoute('<front>');
      }
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
  $variables['rows'] = [];
  $variables['header'] = [];

  $options = $view->style_plugin->options;
  $handler = $view->style_plugin;

  $fields = &$view->field;
  $columns = $handler->sanitizeColumns($options['columns'], $fields);

  $active = !empty($handler->active) ? $handler->active : '';
  $order = !empty($handler->order) ? $handler->order : 'asc';

  // A boolean variable which stores whether the table has a responsive class.
  $responsive = FALSE;

  // For the actual site we want to not render full URLs, because this would
  // make pagers cacheable per URL, which is problematic in blocks, for example.
  // For the actual live preview though the javascript relies on properly
  // working URLs.
  $route_name = !empty($view->live_preview) ? '<current>' : '<none>';

  $query = TableSort::getQueryParameters(\Drupal::request());
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
      $variables['fields'][$field] .= ' is-active';
    }

    // Render the header labels.
    if ($field == $column && empty($fields[$field]->options['exclude'])) {
      $label = !empty($fields[$field]) ? $fields[$field]->label() : '';
      if (empty($options['info'][$field]['sortable']) || !$fields[$field]->clickSortable()) {
        $variables['header'][$field]['content'] = $label;
      }
      else {
        $initial = !empty($options['info'][$field]['default_sort_order']) ? $options['info'][$field]['default_sort_order'] : 'asc';

        if ($active == $field) {
          $initial = ($order == 'asc') ? 'desc' : 'asc';
        }

        $title = t('sort by @s', ['@s' => $label]);
        if ($active == $field) {
          $variables['header'][$field]['sort_indicator'] = [
            '#theme' => 'tablesort_indicator',
            '#style' => $initial,
          ];
        }

        $query['order'] = $field;
        $query['sort'] = $initial;
        $link_options = [
          'query' => $query,
        ];
        $url = new Url($route_name, [], $link_options);
        $variables['header'][$field]['url'] = $url->toString();
        $variables['header'][$field]['content'] = $label;
        $variables['header'][$field]['title'] = $title;
      }

      $variables['header'][$field]['default_classes'] = $fields[$field]->options['element_default_classes'];
      // Set up the header label class.
      $variables['header'][$field]['attributes'] = [];
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
          $variables['header'][$field]['wrapper_element'] = $element_label_type;
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
        $column_reference['attributes'] = [];
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
        $column_reference['attributes']['headers'] = [$variables['header'][$field]['attributes']['id']];
      }

      if (!empty($fields[$field])) {
        $field_output = $handler->getField($num, $field);
        $column_reference['wrapper_element'] = $fields[$field]->elementType(TRUE, TRUE);
        if (!isset($column_reference['content'])) {
          $column_reference['content'] = [];
        }

        // Only bother with separators and stuff if the field shows up.
        // Place the field into the column, along with an optional separator.
        if (trim($field_output) != '') {
          if (!empty($column_reference['content']) && !empty($options['info'][$column]['separator'])) {
            $column_reference['content'][] = [
              'separator' => ['#markup' => $options['info'][$column]['separator']],
              'field_output' => ['#markup' => $field_output],
            ];
          }
          else {
            $column_reference['content'][] = [
              'field_output' => ['#markup' => $field_output],
            ];
          }
        }
      }
      $column_reference['attributes'] = new Attribute($column_reference['attributes']);
    }

    // Remove columns if the "empty_column" option is checked and the
    // field is empty.
    if (!empty($options['info'][$field]['empty_column'])) {
      $empty = TRUE;
      foreach ($variables['rows'] as $columns) {
        $empty &= empty($columns['columns'][$column]['content']);
      }
      if ($empty) {
        foreach ($variables['rows'] as &$column_items) {
          unset($column_items['columns'][$column]);
        }
        unset($variables['header'][$column]);
      }
    }
  }

  // Hide table header if all labels are empty.
  if (!$has_header_labels) {
    $variables['header'] = [];
  }

  foreach ($variables['rows'] as $num => $row) {
    $variables['rows'][$num]['attributes'] = [];
    if ($row_class = $handler->getRowClass($num)) {
      $variables['rows'][$num]['attributes']['class'][] = $row_class;
    }
    $variables['rows'][$num]['attributes'] = new Attribute($variables['rows'][$num]['attributes']);
  }

  if (empty($variables['rows']) && !empty($options['empty_table'])) {
    $build = $view->display_handler->renderArea('empty');
    $variables['rows'][0]['columns'][0]['content'][0]['field_output'] = $build;
    $variables['rows'][0]['attributes'] = new Attribute(['class' => ['odd']]);
    // Calculate the amounts of rows with output.
    $variables['rows'][0]['columns'][0]['attributes'] = new Attribute([
      'colspan' => count($variables['header']),
      'class' => ['views-empty'],
    ]);
  }

  $variables['sticky'] = FALSE;
  if (!empty($options['sticky'])) {
    $variables['view']->element['#attached']['library'][] = 'core/drupal.tableheader';
    $variables['sticky'] = TRUE;
  }

  // Add the caption to the list if set.
  if (!empty($handler->options['caption'])) {
    $variables['caption'] = ['#markup' => $handler->options['caption']];
    $variables['caption_needed'] = TRUE;
  }
  elseif (!empty($variables['title'])) {
    $variables['caption'] = ['#markup' => $variables['title']];
    $variables['caption_needed'] = TRUE;
  }
  else {
    $variables['caption'] = '';
    $variables['caption_needed'] = FALSE;
  }

  // For backwards compatibility, initialize the 'summary' and 'description'
  // variables, although core templates now all use 'summary_element' instead.
  $variables['summary'] = $handler->options['summary'];
  $variables['description'] = $handler->options['description'];
  if (!empty($handler->options['summary']) || !empty($handler->options['description'])) {
    $variables['summary_element'] = [
      '#type' => 'details',
      '#title' => $handler->options['summary'],
      // To ensure that the description is properly escaped during rendering,
      // use an 'inline_template' to let Twig do its magic, instead of 'markup'.
      'description' => [
        '#type' => 'inline_template',
        '#template' => '{{ description }}',
        '#context' => [
          'description' => $handler->options['description'],
        ],
      ],
    ];
    $variables['caption_needed'] = TRUE;
  }

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
  $items = [];
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
      $row_attributes = ['class' => []];
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
      $col_attributes = ['class' => []];
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
    $variables['rows'][$id] = [];
    $variables['rows'][$id]['content'] = $row;
    $variables['rows'][$id]['attributes'] = new Attribute();
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
  $handler = $variables['view']->style_plugin;

  // Fetch classes from handler options.
  if ($handler->options['class']) {
    $class = explode(' ', $handler->options['class']);
    $class = array_map('\Drupal\Component\Utility\Html::cleanCssIdentifier', $class);

    // Initialize a new attribute class for $class.
    $variables['list']['attributes'] = new Attribute(['class' => $class]);
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
  $view = $variables['view'];
  $items = $variables['rows'];
  $style = $view->style_plugin;

  $config = \Drupal::config('system.site');

  // The RSS 2.0 "spec" doesn't indicate HTML can be used in the description.
  // We strip all HTML tags, but need to prevent double encoding from properly
  // escaped source data (such as &amp becoming &amp;amp;).
  $variables['description'] = Html::decodeEntities(strip_tags($style->getDescription()));

  if ($view->display_handler->getOption('sitename_title')) {
    $title = $config->get('name');
    if ($slogan = $config->get('slogan')) {
      $title .= ' - ' . $slogan;
    }
  }
  else {
    $title = $view->getTitle();
  }
  $variables['title'] = $title;
  $variables['link'] = Url::fromRoute('<front>')->setAbsolute()->toString();
  $variables['langcode'] = \Drupal::languageManager()->getCurrentLanguage()->getId();
  $variables['namespaces'] = new Attribute($style->namespaces);
  $variables['items'] = $items;
  $variables['channel_elements'] = $style->channel_elements;

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
  $variables['title'] = $item->title;
  $variables['link'] = $item->link;

  // The description is the only place where we should find HTML.
  // @see https://validator.w3.org/feed/docs/rss2.html#hrelementsOfLtitemgt
  // If we have a render array, render it here and pass the result to the
  // template, letting Twig autoescape it.
  if (isset($item->description) && is_array($item->description)) {
    $variables['description'] = (string) \Drupal::service('renderer')->render($item->description);
  }

  $variables['item_elements'] = [];
  foreach ($item->elements as $element) {
    if (isset($element['attributes']) && is_array($element['attributes'])) {
      $element['attributes'] = new Attribute($element['attributes']);
    }
    $variables['item_elements'][] = $element;
  }
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
  $view = $variables['view'];
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
  $variables['title'] = $title;
  $variables['items'] = $items;
  $variables['updated'] = gmdate(DATE_RFC2822, \Drupal::time()->getRequestTime());

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
  /** @var \Drupal\Core\Pager\PagerManagerInterface $pager_manager */
  $pager_manager = \Drupal::service('pager.manager');

  $tags = &$variables['tags'];
  $element = $variables['element'];
  $parameters = $variables['parameters'];
  $pager = $pager_manager->getPager($element);
  if (!$pager) {
    return;
  }
  $current = $pager->getCurrentPage();
  $total = $pager->getTotalPages();

  // Current is the page we are currently paged to.
  $variables['items']['current'] = $current + 1;

  if ($total > 1 && $current > 0) {
    $options = [
      'query' => $pager_manager->getUpdatedParameters($parameters, $element, $current - 1),
    ];
    $variables['items']['previous']['href'] = Url::fromRoute('<current>', [], $options)->toString();
    if (isset($tags[1])) {
      $variables['items']['previous']['text'] = $tags[1];
    }
    $variables['items']['previous']['attributes'] = new Attribute();
  }

  if ($current < ($total - 1)) {
    $options = [
      'query' => $pager_manager->getUpdatedParameters($parameters, $element, $current + 1),
    ];
    $variables['items']['next']['href'] = Url::fromRoute('<current>', [], $options)->toString();
    if (isset($tags[3])) {
      $variables['items']['next']['text'] = $tags[3];
    }
    $variables['items']['next']['attributes'] = new Attribute();
  }

  // This is based on the entire current query string. We need to ensure
  // cacheability is affected accordingly.
  $variables['#cache']['contexts'][] = 'url.query_args';

  $variables['heading_id'] = Html::getUniqueId('pagination-heading');
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
 * @ingroup views_overview
 * @see \Drupal\views\ViewExecutable::buildThemeFunctions()
 * @}
 */
