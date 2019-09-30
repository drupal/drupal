<?php

/**
 * @file
 * Post update functions for Media Library.
 */

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\MediaType;
use Drupal\user\RoleInterface;
use Drupal\views\Views;

/**
 * Create and configure Media Library form and view displays for media types.
 */
function media_library_post_update_display_modes() {
  // Ensure the custom view and form modes are created.
  $values = [
    'id' => 'media.media_library',
    'targetEntityType' => 'media',
    'label' => t('Media library'),
    'dependencies' => [
      'enforced' => [
        'module' => [
          'media_library',
        ],
      ],
      'module' => [
        'media',
      ],
    ],
  ];
  if (!EntityViewMode::load('media.media_library')) {
    EntityViewMode::create($values)->save();
  }
  if (!EntityFormMode::load('media.media_library')) {
    EntityFormMode::create($values)->save();
  }

  // The Media Library needs a special form display and view display to make
  // sure the Media Library is displayed properly. These were not automatically
  // created for custom media types, so let's make sure this is fixed.
  $types = [];
  foreach (MediaType::loadMultiple() as $type) {
    $form_display_created = _media_library_configure_form_display($type);
    $view_display_created = _media_library_configure_view_display($type);
    if ($form_display_created || $view_display_created) {
      $types[] = $type->label();
    }
  }
  if ($types) {
    return t('Media Library form and view displays have been created for the following media types: @types.', [
      '@types' => implode(', ', $types),
    ]);
  }
}

/**
 * Add a table display to the media library view and link grid/table displays.
 */
function media_library_post_update_table_display() {
  $view = Views::getView('media_library');

  if (!$view) {
    return t('The media_library view could not be updated because it has been deleted. The Media Library module needs this view in order to work properly. Uninstall and reinstall the module so the view will be re-created.');
  }

  // Override CSS classes to allow targeting grid displays.
  $view->setDisplay('default');
  $default_display = $view->getDisplay('default');
  $style = $default_display->getOption('style');
  $style['options']['row_class'] = 'media-library-item media-library-item--grid js-media-library-item js-click-to-select';
  $default_display->setOption('style', $style);

  // Override CSS classes to allow targeting widget displays.
  $view->setDisplay('widget');
  $grid_display = $view->getDisplay('widget');
  $grid_display->overrideOption('css_class', 'media-library-view js-media-library-view media-library-view--widget');

  // Create the new table display.
  $table_display = $view->newDisplay('page', 'Widget (table)', 'widget_table');
  $table_display->setOption('path', 'admin/content/media-widget-table');

  // Override CSS classes to allow targeting widget displays.
  $table_display->overrideOption('css_class', 'media-library-view js-media-library-view media-library-view--widget');

  // Set table as the display style.
  $table_display->overrideOption('style', [
    'type' => 'table',
    'options' => [
      'row_class' => 'media-library-item media-library-item--table js-media-library-item js-click-to-select',
      'default_row_class' => TRUE,
    ],
  ]);

  // Set fields for table display.
  $table_display->overrideOption('row', [
    'type' => 'fields',
  ]);
  $table_display->overrideOption('fields', [
    'media_library_select_form' => [
      'id' => 'media_library_select_form',
      'label' => '',
      'table' => 'media',
      'field' => 'media_library_select_form',
      'relationship' => 'none',
      'entity_type' => 'media',
      'plugin_id' => 'media_library_select_form',
      'element_wrapper_class' => 'js-click-to-select-checkbox',
      'element_class' => '',
    ],
    'thumbnail__target_id' => [
      'id' => 'thumbnail__target_id',
      'label' => 'Thumbnail',
      'table' => 'media_field_data',
      'field' => 'thumbnail__target_id',
      'relationship' => 'none',
      'type' => 'image',
      'entity_type' => 'media',
      'entity_field' => 'thumbnail',
      'plugin_id' => 'field',
      'settings' => [
        'image_style' => 'media_library',
        'image_link' => '',
      ],
    ],
    'name' => [
      'id' => 'name',
      'label' => 'Name',
      'table' => 'media_field_data',
      'field' => 'name',
      'relationship' => 'none',
      'type' => 'string',
      'entity_type' => 'media',
      'entity_field' => 'name',
      'plugin_id' => 'field',
      'settings' => [
        'link_to_entity' => FALSE,
      ],
    ],
    'uid' => [
      'id' => 'uid',
      'label' => 'Author',
      'table' => 'media_field_revision',
      'field' => 'uid',
      'relationship' => 'none',
      'type' => 'entity_reference_label',
      'entity_type' => 'media',
      'entity_field' => 'uid',
      'plugin_id' => 'field',
      'settings' => [
        'link' => TRUE,
      ],
    ],
    'changed' => [
      'id' => 'changed',
      'label' => 'Updated',
      'table' => 'media_field_data',
      'field' => 'changed',
      'relationship' => 'none',
      'type' => 'timestamp',
      'entity_type' => 'media',
      'entity_field' => 'changed',
      'plugin_id' => 'field',
      'settings' => [
        'date_format' => 'short',
        'custom_date_format' => '',
        'timezone' => '',
      ],
    ],
  ]);

  // Override the table display options in the same way as the grid display.
  $table_display->overrideOption('access', $grid_display->getOption('access'));
  $table_display->overrideOption('filters', $grid_display->getOption('filters'));
  $table_display->overrideOption('arguments', $grid_display->getOption('arguments'));

  // Also override the sorts and pager if the grid display has overrides.
  $defaults = $grid_display->getOption('defaults');
  if (isset($defaults['sorts']) && !$defaults['sorts']) {
    $table_display->overrideOption('sorts', $grid_display->getOption('sorts'));
  }
  if (isset($defaults['pager']) && !$defaults['pager']) {
    $table_display->overrideOption('pager', $grid_display->getOption('pager'));
  }

  // Add display links to both widget and widget table displays.
  $display_links = [
    'display_link_grid' => [
      'id' => 'display_link_grid',
      'table' => 'views',
      'field' => 'display_link',
      'display_id' => 'widget',
      'label' => 'Grid',
      'plugin_id' => 'display_link',
      'empty' => TRUE,
    ],
    'display_link_table' => [
      'id' => 'display_link_table',
      'table' => 'views',
      'field' => 'display_link',
      'display_id' => 'widget_table',
      'label' => 'Table',
      'plugin_id' => 'display_link',
      'empty' => TRUE,
    ],
  ];
  $grid_display->overrideOption('header', $display_links);
  $table_display->overrideOption('header', $display_links);

  $view->save();
}

/**
 * Create the 'media_library' image style if necessary.
 */
function media_library_post_update_add_media_library_image_style() {
  // Bail out early if the image style was already created by
  // media_library_update_8701(), or manually by the site owner.
  if (ImageStyle::load('media_library')) {
    return;
  }

  $image_style = ImageStyle::create([
    'name' => 'media_library',
    'label' => 'Media Library (220x220)',
  ]);
  // Add a scale effect.
  $image_style->addImageEffect([
    'id' => 'image_scale',
    'weight' => 0,
    'data' => [
      'width' => 220,
      'height' => 220,
      'upscale' => FALSE,
    ],
  ]);
  $image_style->save();

  return t('The %label image style has been created successfully.', ['%label' => 'Media Library (220x220)']);
}

/**
 * Add a status extra filter to the media library view default display.
 */
function media_library_post_update_add_status_extra_filter() {
  $view = Views::getView('media_library');

  if (!$view) {
    return t('The media_library view could not be updated because it has been deleted. The Media Library module needs this view in order to work properly. Uninstall and reinstall the module so the view will be re-created.');
  }

  // Fetch the filters from the default display and add the new 'status_extra'
  // filter if it does not yet exist.
  $default_display = $view->getDisplay();
  $filters = $default_display->getOption('filters');

  if (!isset($filters['status_extra'])) {
    $filters['status_extra'] = [
      'group_info' => [
        'widget' => 'select',
        'group_items' => [],
        'multiple' => FALSE,
        'description' => '',
        'default_group_multiple' => [],
        'default_group' => 'All',
        'label' => '',
        'identifier' => '',
        'optional' => TRUE,
        'remember' => FALSE,
      ],
      'group' => 1,
      'relationship' => 'none',
      'exposed' => FALSE,
      'expose' => [
        'use_operator' => FALSE,
        'remember' => FALSE,
        'operator_id' => '',
        'multiple' => FALSE,
        'description' => '',
        'required' => FALSE,
        'label' => '',
        'operator_limit_selection' => FALSE,
        'operator' => '',
        'identifier' => '',
        'operator_list' => [],
        'remember_roles' => [RoleInterface::AUTHENTICATED_ID => RoleInterface::AUTHENTICATED_ID],
      ],
      'entity_type' => 'media',
      'value' => '',
      'field' => 'status_extra',
      'is_grouped' => FALSE,
      'admin_label' => '',
      'operator' => '=',
      'table' => 'media_field_data',
      'plugin_id' => 'media_status',
      'id' => 'status_extra',
      'group_type' => 'group',
    ];
    $default_display->setOption('filters', $filters);
    $view->save();

    return t("The 'Published status or admin user' filter was added to the %label view.", [
      '%label' => $view->storage->label(),
    ]);
  }
}

/**
 * Add edit and delete button to media library view page display.
 */
function media_library_post_update_add_buttons_to_page_view() {
  $view = Views::getView('media_library');
  if (!$view) {
    return;
  }

  $display = &$view->storage->getDisplay('page');
  if ($display) {
    // Fetch the fields from the page display, if the fields are not yet
    // overridden, get the fields from the default display.
    if (empty($display['display_options']['fields'])) {
      $defaults = $view->storage->getDisplay('default');
      $display['display_options']['fields'] = $defaults['display_options']['fields'];
      // Override the fields for the page display.
      $display['display_options']['defaults']['fields'] = FALSE;
    }

    $fields = $display['display_options']['fields'];

    // Check if the name field already exists and add if it doesn't.
    if (!isset($fields['name'])) {
      $fields['name'] = [
        'id' => 'name',
        'table' => 'media_field_data',
        'field' => 'name',
        'relationship' => 'none',
        'group_type' => 'group',
        'admin_label' => '',
        'label' => '',
        'exclude' => TRUE,
        'alter' => [
          'alter_text' => FALSE,
          'text' => '',
          'make_link' => FALSE,
          'path' => '',
          'absolute' => FALSE,
          'external' => FALSE,
          'replace_spaces' => FALSE,
          'path_case' => 'none',
          'trim_whitespace' => FALSE,
          'alt' => '',
          'rel' => '',
          'link_class' => '',
          'prefix' => '',
          'suffix' => '',
          'target' => '',
          'nl2br' => FALSE,
          'max_length' => 0,
          'word_boundary' => TRUE,
          'ellipsis' => TRUE,
          'more_link' => FALSE,
          'more_link_text' => '',
          'more_link_path' => '',
          'strip_tags' => FALSE,
          'trim' => FALSE,
          'preserve_tags' => '',
          'html' => FALSE,
        ],
        'element_type' => '',
        'element_class' => '',
        'element_label_type' => '',
        'element_label_class' => '',
        'element_label_colon' => FALSE,
        'element_wrapper_type' => '',
        'element_wrapper_class' => '',
        'element_default_classes' => TRUE,
        'empty' => '',
        'hide_empty' => FALSE,
        'empty_zero' => FALSE,
        'hide_alter_empty' => TRUE,
        'click_sort_column' => 'value',
        'type' => 'string',
        'settings' => [
          'link_to_entity' => FALSE,
        ],
        'group_column' => 'value',
        'group_columns' => [],
        'group_rows' => TRUE,
        'delta_limit' => 0,
        'delta_offset' => 0,
        'delta_reversed' => FALSE,
        'delta_first_last' => FALSE,
        'multi_type' => 'separator',
        'separator' => ', ',
        'field_api_classes' => FALSE,
        'entity_type' => 'media',
        'entity_field' => 'name',
        'plugin_id' => 'field',
      ];
    }

    // Check if the edit link field already exists and add if it doesn't.
    if (!isset($fields['edit_media'])) {
      $fields['edit_media'] = [
        'id' => 'edit_media',
        'table' => 'media',
        'field' => 'edit_media',
        'relationship' => 'none',
        'group_type' => 'group',
        'admin_label' => '',
        'label' => '',
        'exclude' => FALSE,
        'alter' => [
          'alter_text' => TRUE,
          'text' => 'Edit {{ name }}',
          'make_link' => TRUE,
          'path' => '',
          'absolute' => FALSE,
          'external' => FALSE,
          'replace_spaces' => FALSE,
          'path_case' => 'none',
          'trim_whitespace' => FALSE,
          'alt' => 'Edit {{ name }}',
          'rel' => '',
          'link_class' => 'media-library-item__edit',
          'prefix' => '',
          'suffix' => '',
          'target' => '',
          'nl2br' => FALSE,
          'max_length' => 0,
          'word_boundary' => TRUE,
          'ellipsis' => TRUE,
          'more_link' => FALSE,
          'more_link_text' => '',
          'more_link_path' => '',
          'strip_tags' => FALSE,
          'trim' => FALSE,
          'preserve_tags' => '',
          'html' => FALSE,
        ],
        'element_type' => '',
        'element_class' => '',
        'element_label_type' => '',
        'element_label_class' => '',
        'element_label_colon' => FALSE,
        'element_wrapper_type' => '0',
        'element_wrapper_class' => '',
        'element_default_classes' => FALSE,
        'empty' => '',
        'hide_empty' => FALSE,
        'empty_zero' => FALSE,
        'hide_alter_empty' => TRUE,
        'text' => 'Edit',
        'output_url_as_text' => FALSE,
        'absolute' => FALSE,
        'entity_type' => 'media',
        'plugin_id' => 'entity_link_edit',
      ];
    }

    // Check if the delete link field already exists and add if it doesn't.
    if (!isset($fields['delete_media'])) {
      $fields['delete_media'] = [
        'id' => 'delete_media',
        'table' => 'media',
        'field' => 'delete_media',
        'relationship' => 'none',
        'group_type' => 'group',
        'admin_label' => '',
        'label' => '',
        'exclude' => FALSE,
        'alter' => [
          'alter_text' => TRUE,
          'text' => 'Delete {{ name }}',
          'make_link' => TRUE,
          'path' => '',
          'absolute' => FALSE,
          'external' => FALSE,
          'replace_spaces' => FALSE,
          'path_case' => 'none',
          'trim_whitespace' => FALSE,
          'alt' => 'Delete {{ name }}',
          'rel' => '',
          'link_class' => 'media-library-item__remove',
          'prefix' => '',
          'suffix' => '',
          'target' => '',
          'nl2br' => FALSE,
          'max_length' => 0,
          'word_boundary' => TRUE,
          'ellipsis' => TRUE,
          'more_link' => FALSE,
          'more_link_text' => '',
          'more_link_path' => '',
          'strip_tags' => FALSE,
          'trim' => FALSE,
          'preserve_tags' => '',
          'html' => FALSE,
        ],
        'element_type' => '',
        'element_class' => '',
        'element_label_type' => '',
        'element_label_class' => '',
        'element_label_colon' => FALSE,
        'element_wrapper_type' => '0',
        'element_wrapper_class' => '',
        'element_default_classes' => FALSE,
        'empty' => '',
        'hide_empty' => FALSE,
        'empty_zero' => FALSE,
        'hide_alter_empty' => TRUE,
        'text' => 'Delete',
        'output_url_as_text' => FALSE,
        'absolute' => FALSE,
        'entity_type' => 'media',
        'plugin_id' => 'entity_link_delete',
      ];
    }

    // Move the rendered entity field to the last position for accessibility.
    $rendered_entity = $fields['rendered_entity'];
    unset($fields['rendered_entity']);
    $fields['rendered_entity'] = $rendered_entity;

    $display['display_options']['fields'] = $fields;
    $view->storage->save(TRUE);
  }
}
