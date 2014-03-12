<?php

/**
 * @file
 * Preprocessors and theme functions for the Views UI.
 */

use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for Views UI display tab setting templates.
 *
 * Default template: views-ui-display-tab-setting.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - link: The setting's primary link.
 *   - settings_links: An array of links for this setting.
 *   - defaulted: A boolean indicating the setting is in its default state.
 *   - overridden: A boolean indicating the setting has been overridden from
 *     the default.
 *   - description: The setting's description.
 *   - description_separator: A boolean indicating a separator colon should be
 *     appended to the setting's description.
 */
function template_preprocess_views_ui_display_tab_setting(&$variables) {

  // Add default class attributes.
  $variables['attributes']['class'][] = 'views-display-setting';
  $variables['attributes']['class'][] = 'clearfix';

  // Put the primary link to the left side.
  array_unshift($variables['settings_links'], $variables['link']);

  $variables['attributes']['class'][] = 'views-ui-display-tab-setting';

  if (!empty($variables['defaulted'])) {
    $variables['attributes']['class'][] = 'defaulted';
  }
  if (!empty($variables['overridden'])) {
    $variables['attributes']['class'][] = 'overridden';
    $variables['attributes']['title'][] = t('Overridden');
  }

  // Append a colon to the description, if requested.
  if ($variables['description'] && $variables['description_separator']) {
    $variables['description'] .= t(':');
  }
}

/**
 * Prepares variables for Views UI display tab bucket templates.
 *
 * Default template: views-ui-display-tab-bucket.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #name, #overridden, #children, #title, #actions.
 */
function template_preprocess_views_ui_display_tab_bucket(&$variables) {
  $element = $variables['element'];

  $variables['attributes']['class'][] = 'views-ui-display-tab-bucket';

  if (!empty($element['#name'])) {
    $variables['attributes']['class'][] = drupal_html_class($element['#name']);
  }
  if (!empty($element['#overridden'])) {
    $variables['attributes']['class'][] = 'overridden';
    $variables['attributes']['title'][] = t('Overridden');
  }

  $variables['content'] = $element['#children'];
  $variables['title'] = $element['#title'];
  $variables['actions'] = !empty($element['#actions']) ? $element['#actions'] : array();
}

/**
 * Prepares variables for Views UI View info templates.
 *
 * Default template: views-ui-view-info.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: The View object.
 */
function template_preprocess_views_ui_view_info(&$variables) {
  $variables['title'] = $variables['view']->label();
  $variables['displays'] = empty($variables['displays']) ? t('None') : format_plural(count($variables['displays']), 'Display', 'Displays') . ': ' . '<em>' . implode(', ', $variables['displays']) . '</em>';
}

/**
 * Theme the expose filter form.
 */
function theme_views_ui_expose_filter_form($variables) {
  $form = $variables['form'];
  $more = drupal_render($form['more']);

  $output = drupal_render($form['form_description']);
  $output .= drupal_render($form['expose_button']);
  $output .= drupal_render($form['group_button']);
  if (isset($form['required'])) {
    $output .= drupal_render($form['required']);
  }
  $output .= drupal_render($form['label']);
  $output .= drupal_render($form['description']);

  $output .= drupal_render($form['operator']);
  $output .= drupal_render($form['value']);

  if (isset($form['use_operator'])) {
    $output .= '<div class="views-left-40">';
    $output .= drupal_render($form['use_operator']);
    $output .= '</div>';
  }

  // Only output the right column markup if there's a left column to begin with
  if (!empty($form['operator']['#type'])) {
    $output .= '<div class="views-right-60">';
    $output .= drupal_render_children($form);
    $output .= '</div>';
  }
  else {
    $output .= drupal_render_children($form);
  }

  $output .= $more;

  return $output;
}

/**
 * Theme the build group filter form.
 */
function theme_views_ui_build_group_filter_form($variables) {
  $form = $variables['form'];
  $more = drupal_render($form['more']);

  $output = drupal_render($form['form_description']);
  $output .= drupal_render($form['expose_button']);
  $output .= drupal_render($form['group_button']);
  if (isset($form['required'])) {
    $output .= drupal_render($form['required']);
  }

  $output .= drupal_render($form['operator']);
  $output .= drupal_render($form['value']);

  $output .= '<div class="views-left-40">';
  $output .= drupal_render($form['optional']);
  $output .= drupal_render($form['remember']);
  $output .= '</div>';

  $output .= '<div class="views-right-60">';
  $output .= drupal_render($form['widget']);
  $output .= drupal_render($form['label']);
  $output .= drupal_render($form['description']);
  $output .= '</div>';

  $header = array(
    t('Default'),
    t('Weight'),
    t('Label'),
    t('Operator'),
    t('Value'),
    t('Operations'),
  );

  $form['default_group'] = form_process_radios($form['default_group']);
  $form['default_group_multiple'] = form_process_checkboxes($form['default_group_multiple']);
  $form['default_group']['All']['#title'] = '';

  hide($form['default_group_multiple']['All']);
  $rows[] = array(
    drupal_render($form['default_group']['All']),
    '',
    array(
      'data' => \Drupal::config('views.settings')->get('ui.exposed_filter_any_label') == 'old_any' ? t('&lt;Any&gt;') : t('- Any -'),
      'colspan' => 4,
      'class' => array('class' => 'any-default-radios-row'),
    ),
  );

  foreach (element_children($form['group_items']) as $group_id) {
    $form['group_items'][$group_id]['value']['#title'] = '';
    $data = array(
      'default' => drupal_render($form['default_group'][$group_id]) . drupal_render($form['default_group_multiple'][$group_id]),
      'weight' => drupal_render($form['group_items'][$group_id]['weight']),
      'title' => drupal_render($form['group_items'][$group_id]['title']),
      'operator' => drupal_render($form['group_items'][$group_id]['operator']),
      'value' => drupal_render($form['group_items'][$group_id]['value']),
      'remove' => drupal_render($form['group_items'][$group_id]['remove']) . l('<span>' . t('Remove') . '</span>', 'javascript:void()', array('attributes' => array('id' => 'views-remove-link-' . $group_id, 'class' => array('views-hidden', 'views-button-remove', 'views-groups-remove-link', 'views-remove-link'), 'alt' => t('Remove this item'), 'title' => t('Remove this item')), 'html' => true)),
    );
    $rows[] = array('data' => $data, 'id' => 'views-row-' . $group_id, 'class' => array('draggable'));
  }
  $table = array(
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => array(
      'class' => array('views-filter-groups'),
      'id' => 'views-filter-groups',
    ),
    '#tabledrag' => array(
      array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'weight',
      )
    ),
  );
  $render_form = drupal_render_children($form);
  return $output . $render_form . drupal_render($table) . drupal_render($form['add_group']) . $more;
}

/**
 * Turn the rearrange form into a proper table
 */
function theme_views_ui_rearrange_filter_form(&$variables) {
  $form = $variables['form'];
  $rows = $ungroupable_rows = array();
  // Enable grouping only if > 1 group.
  $grouping = count(array_keys($form['#group_options'])) > 1;

  foreach ($form['#group_renders'] as $group_id => $contents) {
    // Header row for the group.
    if ($group_id !== 'ungroupable') {
      // Set up tabledrag so that it changes the group dropdown when rows are
      // dragged between groups.
      $options = array(
        'table_id' => 'views-rearrange-filters',
        'action' => 'match',
        'relationship' => 'sibling',
        'group' => 'views-group-select',
        'subgroup' => 'views-group-select-' . $group_id,
      );
      drupal_attach_tabledrag($form['override'], $options);

      // Title row, spanning all columns.
      $row = array();
      // Add a cell to the first row, containing the group operator.
      $row[] = array('class' => array('group', 'group-operator', 'container-inline'), 'data' => drupal_render($form['filter_groups']['groups'][$group_id]), 'rowspan' => max(array(2, count($contents) + 1)));
      // Title.
      $row[] = array('class' => array('group', 'group-title'), 'data' => '<span>' . $form['#group_options'][$group_id] . '</span>', 'colspan' => 4);
      $rows[] = array('class' => array('views-group-title'), 'data' => $row, 'id' => 'views-group-title-' . $group_id);

      // Row which will only appear if the group has nothing in it.
      $row = array();
      $class = 'group-' . (count($contents) ? 'populated' : 'empty');
      $instructions = '<span>' . t('No filters have been added.') . '</span> <span class="js-only">' . t('Drag to add filters.') . '</span>';
      // When JavaScript is enabled, the button for removing the group (if it's
      // present) should be hidden, since it will be replaced by a link on the
      // client side.
      if (!empty($form['remove_groups'][$group_id]['#type']) && $form['remove_groups'][$group_id]['#type'] == 'submit') {
        $form['remove_groups'][$group_id]['#attributes']['class'][] = 'js-hide';
      }
      $row[] = array('colspan' => 5, 'data' => $instructions . drupal_render($form['remove_groups'][$group_id]));
      $rows[] = array('class' => array("group-message", "group-$group_id-message", $class), 'data' => $row, 'id' => 'views-group-' . $group_id);
    }

    foreach ($contents as $id) {
      if (isset($form['filters'][$id]['name'])) {
        $row = array();
        $row[] = drupal_render($form['filters'][$id]['name']);
        $form['filters'][$id]['weight']['#attributes']['class'] = array('weight');
        $row[] = drupal_render($form['filters'][$id]['weight']);
        $form['filters'][$id]['group']['#attributes']['class'] = array('views-group-select views-group-select-' . $group_id);
        $row[] = drupal_render($form['filters'][$id]['group']);
        $form['filters'][$id]['removed']['#attributes']['class'][] = 'js-hide';
        $row[] = drupal_render($form['filters'][$id]['removed']) . l('<span>' . t('Remove') . '</span>', '', array('attributes' => array('id' => 'views-remove-link-' . $id, 'class' => array('views-hidden', 'views-button-remove', 'views-groups-remove-link', 'views-remove-link'), 'alt' => t('Remove this item'), 'title' => t('Remove this item')), 'html' => TRUE));

        $row = array('data' => $row, 'class' => array('draggable'), 'id' => 'views-row-' . $id);
        if ($group_id !== 'ungroupable') {
          $rows[] = $row;
        }
        else {
          $ungroupable_rows[] = $row;
        }
      }
    }
  }
  if (empty($rows)) {
    $rows[] = array(array('data' => t('No fields available.'), 'colspan' => '2'));
  }

  $output = drupal_render($form['override']);
  $output .= '<div class="scroll" data-drupal-views-scroll>';
  if ($grouping) {
    $output .= drupal_render($form['filter_groups']['operator']);
  }
  else {
    $form['filter_groups']['groups'][0]['#title'] = t('Operator');
    $output .= drupal_render($form['filter_groups']['groups'][0]);
  }

  if (!empty($ungroupable_rows)) {
    $header = array(t('Ungroupable filters'), t('Weight'), array('class' => array('views-hide-label'), 'data' => t('Group')), array('class' => array('views-hide-label'), 'data' => t('Remove')));
    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $ungroupable_rows,
      '#attributes' => array(
        'id' => 'views-rearrange-filters-ungroupable',
        'class' => array('arrange'),
      ),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        )
      ),
    );
    $output .= drupal_render($table);
  }

  // Set up tabledrag so that the weights are changed when rows are dragged.
  $table = array(
    '#type' => 'table',
    '#rows' => $rows,
    '#attributes' => array(
      'id' => 'views-rearrange-filters',
      'class' => array('arrange'),
    ),
    '#tabledrag' => array(
      array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'weight',
      )
    ),
  );
  $output .= drupal_render($table);
  $output .= '</div>';

  // When JavaScript is enabled, the button for adding a new group should be
  // hidden, since it will be replaced by a link on the client side.
  $form['actions']['add_group']['#attributes']['class'][] = 'js-hide';

  // Render the rest of the form and return.
  $output .= drupal_render_children($form);
  return $output;
}

/**
 * Prepares variables for style plugin table templates.
 *
 * Default template: views-ui-style-plugin-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
*/
function template_preprocess_views_ui_style_plugin_table(&$variables) {
  $form = $variables['form'];

  $header = array(
    t('Field'),
    t('Column'),
    t('Align'),
    t('Separator'),
    array(
      'data' => t('Sortable'),
      'align' => 'center',
    ),
    array(
      'data' => t('Default order'),
      'align' => 'center',
    ),
    array(
      'data' => t('Default sort'),
      'align' => 'center',
    ),
    array(
      'data' => t('Hide empty column'),
      'align' => 'center',
    ),
    array(
      'data' => t('Responsive'),
      'align' => 'center',
    ),
  );
  $rows = array();
  foreach (element_children($form['columns']) as $id) {
    $row = array();
    $row[]['data'] = $form['info'][$id]['name'];
    $row[]['data'] = $form['columns'][$id];
    $row[]['data'] = $form['info'][$id]['align'];
    $row[]['data'] = $form['info'][$id]['separator'];

    if (!empty($form['info'][$id]['sortable'])) {
      $row[] = array(
        'data' => $form['info'][$id]['sortable'],
        'align' => 'center',
      );
      $row[] = array(
        'data' => $form['info'][$id]['default_sort_order'],
        'align' => 'center',
      );
      $row[] = array(
        'data' => $form['default'][$id],
        'align' => 'center',
      );
    }
    else {
      $row[] = '';
      $row[] = '';
      $row[] = '';
    }
    $row[] = array(
      'data' => $form['info'][$id]['empty_column'],
      'align' => 'center',
    );
    $row[] = array(
      'data' => $form['info'][$id]['responsive'],
      'align' => 'center',
    );
    $rows[] = $row;
  }

  // Add the special 'None' row.
  $rows[] = array(array('data' => t('None'), 'colspan' => 6), array('align' => 'center', 'data' => $form['default'][-1]), array('colspan' => 2));

  // Unset elements from the form array that are used to build the table so that
  // they are not rendered twice.
  unset($form['default']);
  unset($form['info']);
  unset($form['columns']);

  $variables['table'] = array(
    '#type' => 'table',
    '#theme' => 'table__views_ui_style_plugin_table',
    '#header' => $header,
    '#rows' => $rows,
  );
  $variables['form'] = $form;
}

/**
 * Prepares variables for views UI view preview section templates.
 *
 * Default template: views-ui-view-preview-section.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: The view object.
 *   - section: The section name of a View (e.g. title, rows or pager).
 */
function template_preprocess_views_ui_view_preview_section(&$variables) {
  switch ($variables['section']) {
    case 'title':
      $variables['title'] = t('Title');
      $links = views_ui_view_preview_section_display_category_links($variables['view'], 'title', $variables['title']);
      break;
    case 'header':
      $variables['title'] = t('Header');
      $links = views_ui_view_preview_section_handler_links($variables['view'], $variables['section']);
      break;
    case 'empty':
      $variables['title'] = t('No results behavior');
      $links = views_ui_view_preview_section_handler_links($variables['view'], $variables['section']);
      break;
    case 'exposed':
      // @todo Sorts can be exposed too, so we may need a better title.
      $variables['title'] = t('Exposed Filters');
      $links = views_ui_view_preview_section_display_category_links($variables['view'], 'exposed_form_options', $variables['title']);
      break;
    case 'rows':
      // @todo The title needs to depend on what is being viewed.
      $variables['title'] = t('Content');
      $links = views_ui_view_preview_section_rows_links($variables['view']);
      break;
    case 'pager':
      $variables['title'] = t('Pager');
      $links = views_ui_view_preview_section_display_category_links($variables['view'], 'pager_options', $variables['title']);
      break;
    case 'more':
      $variables['title'] = t('More');
      $links = views_ui_view_preview_section_display_category_links($variables['view'], 'use_more', $variables['title']);
      break;
    case 'footer':
      $variables['title'] = t('Footer');
      $links = views_ui_view_preview_section_handler_links($variables['view'], $variables['section']);
      break;
    case 'attachment_before':
      // @todo: Add links to the attachment configuration page.
      $variables['title'] = t('Attachment before');
      break;
    case 'attachment_after':
      // @todo: Add links to the attachment configuration page.
      $variables['title'] = t('Attachment after');
      break;
  }

  if (isset($links)) {
    $build = array(
      '#theme' => 'links__contextual',
      '#links' => $links,
      '#attributes' => array('class' => array('contextual-links')),
      '#attached' => array(
        'library' => array('contextual/drupal.contextual-links'),
      ),
    );
    $variables['links'] = $build;
  }
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function views_ui_theme_suggestions_views_ui_view_preview_section(array $variables) {
  return array('views_ui_view_preview_section__' . $variables['section']);
}
