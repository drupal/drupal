<?php

/**
 * @file
 * Post update functions for Media Library.
 */

/**
 * Implements hook_removed_post_updates().
 */
function media_library_removed_post_updates() {
  return [
    'media_library_post_update_display_modes' => '9.0.0',
    'media_library_post_update_table_display' => '9.0.0',
    'media_library_post_update_add_media_library_image_style' => '9.0.0',
    'media_library_post_update_add_status_extra_filter' => '9.0.0',
    'media_library_post_update_add_buttons_to_page_view' => '9.0.0',
    'media_library_post_update_update_8001_checkbox_classes' => '9.0.0',
    'media_library_post_update_default_administrative_list_to_table_display' => '9.0.0',
    'media_library_post_update_add_langcode_filters' => '9.0.0',
  ];
}

/**
 * Add a current selection display to the media library view.
 */
function media_library_post_update_current_selection_display() {
  $view = Views::getView('media_library');

  if (!$view) {
    return t('The media_library view could not be updated because it has been deleted. The Media Library module needs this view in order to work properly. Uninstall and reinstall the module so the view will be re-created.');
  }

  $options = [
    'defaults',
    'row',
    'style',
    'fields',
    'filters',
    'filter_groups',
    'access',
    'rendering_language',
  ];

  // Create the new grid/table selection displays and copy the settings from the
  // existing displays.
  $view->setDisplay('widget');
  $grid_selection_display = $view->newDisplay('page', 'Widget selection', 'widget_selection');
  $grid_selection_display->setOption('path', 'admin/content/media-widget-selection');
  foreach ($options as $option) {
    $grid_selection_display->setOption($option, $view->getDisplay()->getOption($option));
  }
  $view->setDisplay('widget_table');
  $table_selection_display = $view->newDisplay('page', 'Widget selection (table)', 'widget_selection_table');
  $table_selection_display->setOption('path', 'admin/content/media-widget-selection-table');
  foreach ($options as $option) {
    $table_selection_display->setOption($option, $view->getDisplay()->getOption($option));
  }

  // Update the header links to switch between grid/table of selection views.
  // Add display links to both widget and widget table displays.
  $display_links = [
    'display_link_grid' => [
      'id' => 'display_link_grid',
      'table' => 'views',
      'field' => 'display_link',
      'display_id' => 'widget_selection',
      'label' => 'Grid',
      'plugin_id' => 'display_link',
      'empty' => TRUE,
    ],
    'display_link_table' => [
      'id' => 'display_link_table',
      'table' => 'views',
      'field' => 'display_link',
      'display_id' => 'widget_selection_table',
      'label' => 'Table',
      'plugin_id' => 'display_link',
      'empty' => TRUE,
    ],
  ];
  $grid_selection_display->overrideOption('header', $display_links);
  $table_selection_display->overrideOption('header', $display_links);

  // Add an argument to pass media IDs.
  $arguments = [
    'mid' => [
      'id' => 'mid',
      'table' => 'media_field_data',
      'field' => 'mid',
      'relationship' => 'none',
      'group_type' => 'group',
      'admin_label' => '',
      'default_action' => 'empty',
      'exception' => [
        'value' => '',
        'title_enable' => FALSE,
        'title' => 'All',
      ],
      'title_enable' => FALSE,
      'title' => '',
      'default_argument_type' => 'fixed',
      'default_argument_options' => ['argument' => ''],
      'default_argument_skip_url' => FALSE,
      'summary_options' => [
        'base_path' => '',
        'count' => TRUE,
        'items_per_page' => 24,
        'override' => FALSE,
      ],
      'summary' => [
        'sort_order' => 'asc',
        'number_of_records' => 0,
        'format' => 'default_summary',
      ],
      'specify_validation' => FALSE,
      'validate' => [
        'type' => 'none',
        'fail' => 'not found',
      ],
      'validate_options' => [],
      'break_phrase' => TRUE,
      'not' => FALSE,
      'entity_type' => 'media',
      'entity_field' => 'mid',
      'plugin_id' => 'numeric',
    ],
  ];
  $grid_selection_display->overrideOption('arguments', $arguments);
  $table_selection_display->overrideOption('arguments', $arguments);

  $view->save();
}
