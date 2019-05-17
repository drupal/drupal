<?php

/**
 * @file
 * Post update functions for Media library.
 */

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\MediaType;
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
    return t('The media_library view could not be updated because it has been deleted. The Media library module needs this view in order to work properly. Uninstall and reinstall the module so the view will be re-created.');
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
