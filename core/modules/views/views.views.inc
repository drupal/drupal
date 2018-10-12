<?php

/**
 * @file
 * Provide views data that isn't tied to any other module.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Render\Markup;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\system\ActionConfigEntityInterface;

/**
 * Implements hook_views_data().
 */
function views_views_data() {
  $data['views']['table']['group'] = t('Global');
  $data['views']['table']['join'] = [
  // #global is a special flag which allows a table to appear all the time.
  '#global' => [],
  ];

  $data['views']['random'] = [
  'title' => t('Random'),
  'help' => t('Randomize the display order.'),
  'sort' => [
    'id' => 'random',
    ],
  ];

  $data['views']['null'] = [
    'title' => t('Null'),
    'help' => t('Allow a contextual filter value to be ignored. The query will not be altered by this contextual filter value. Can be used when contextual filter values come from the URL, and a part of the URL needs to be ignored.'),
    'argument' => [
      'id' => 'null',
    ],
  ];

  $data['views']['nothing'] = [
    'title' => t('Custom text'),
    'help' => t('Provide custom text or link.'),
    'field' => [
      'id' => 'custom',
    ],
  ];

  $data['views']['counter'] = [
    'title' => t('View result counter'),
    'help' => t('Displays the actual position of the view result'),
    'field' => [
      'id' => 'counter',
    ],
  ];

  $data['views']['area'] = [
    'title' => t('Text area'),
    'help' => t('Provide markup text for the area.'),
    'area' => [
      'id' => 'text',
    ],
  ];

  $data['views']['area_text_custom'] = [
    'title' => t('Unfiltered text'),
    'help' => t('Add unrestricted, custom text or markup. This is similar to the custom text field.'),
    'area' => [
      'id' => 'text_custom',
    ],
  ];

  $data['views']['title'] = [
    'title' => t('Title override'),
    'help' => t('Override the default view title for this view. This is useful to display an alternative title when a view is empty.'),
    'area' => [
      'id' => 'title',
      'sub_type' => 'empty',
    ],
  ];

  $data['views']['view'] = [
    'title' => t('View area'),
    'help' => t('Insert a view inside an area.'),
    'area' => [
      'id' => 'view',
    ],
  ];

  $data['views']['result'] = [
    'title' => t('Result summary'),
    'help' => t('Shows result summary, for example the items per page.'),
    'area' => [
      'id' => 'result',
    ],
  ];

  $data['views']['messages'] = [
    'title' => t('Messages'),
    'help' => t('Displays messages in an area.'),
    'area' => [
      'id' => 'messages',
    ],
  ];

  $data['views']['http_status_code'] = [
    'title' => t('Response status code'),
    'help' => t('Alter the HTTP response status code used by this view, mostly helpful for empty results.'),
    'area' => [
      'id' => 'http_status_code',
    ],
  ];

  $data['views']['combine'] = [
   'title' => t('Combine fields filter'),
    'help' => t('Combine multiple fields together and search by them.'),
    'filter' => [
      'id' => 'combine',
    ],
  ];

  $data['views']['dropbutton'] = [
    'title' => t('Dropbutton'),
    'help' => t('Display fields in a dropbutton.'),
    'field' => [
      'id' => 'dropbutton',
    ],
  ];

  // Registers an entity area handler per entity type.
  foreach (\Drupal::entityManager()->getDefinitions() as $entity_type_id => $entity_type) {
    // Excludes entity types, which cannot be rendered.
    if ($entity_type->hasViewBuilderClass()) {
      $label = $entity_type->getLabel();
      $data['views']['entity_' . $entity_type_id] = [
        'title' => t('Rendered entity - @label', ['@label' => $label]),
        'help' => t('Displays a rendered @label entity in an area.', ['@label' => $label]),
        'area' => [
          'entity_type' => $entity_type_id,
          'id' => 'entity',
        ],
      ];
    }
  }

  // Registers an action bulk form per entity.
  foreach (\Drupal::entityManager()->getDefinitions() as $entity_type => $entity_info) {
    $actions = array_filter(\Drupal::entityManager()->getStorage('action')->loadMultiple(), function (ActionConfigEntityInterface $action) use ($entity_type) {
      return $action->getType() == $entity_type;
    });
    if (empty($actions)) {
      continue;
    }
    $data[$entity_info->getBaseTable()][$entity_type . '_bulk_form'] = [
      'title' => t('Bulk update'),
      'help' => t('Allows users to apply an action to one or more items.'),
      'field' => [
        'id' => 'bulk_form',
      ],
    ];
  }

  // Registers views data for the entity itself.
  foreach (\Drupal::entityManager()->getDefinitions() as $entity_type_id => $entity_type) {
    if ($entity_type->hasHandlerClass('views_data')) {
      /** @var \Drupal\views\EntityViewsDataInterface $views_data */
      $views_data = \Drupal::entityManager()->getHandler($entity_type_id, 'views_data');
      $data = NestedArray::mergeDeep($data, $views_data->getViewsData());
    }
  }

  // Field modules can implement hook_field_views_data() to override the default
  // behavior for adding fields.
  $module_handler = \Drupal::moduleHandler();

  $entity_manager = \Drupal::entityManager();
  if ($entity_manager->hasDefinition('field_storage_config')) {
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    foreach ($entity_manager->getStorage('field_storage_config')->loadMultiple() as $field_storage) {
      if (_views_field_get_entity_type_storage($field_storage)) {
        $result = (array) $module_handler->invoke($field_storage->getTypeProvider(), 'field_views_data', [$field_storage]);
        if (empty($result)) {
          $result = views_field_default_views_data($field_storage);
        }
        $module_handler->alter('field_views_data', $result, $field_storage);

        if (is_array($result)) {
          $data = NestedArray::mergeDeep($result, $data);
        }
      }
    }
  }

  return $data;
}

/**
 * Implements hook_views_data_alter().
 *
 * Field modules can implement hook_field_views_data_views_data_alter() to
 * alter the views data on a per field basis. This is weirdly named so as
 * not to conflict with the \Drupal::moduleHandler()->alter('field_views_data')
 * in views_views_data().
 */
function views_views_data_alter(&$data) {
  $entity_manager = \Drupal::entityManager();
  if (!$entity_manager->hasDefinition('field_storage_config')) {
    return;
  }
  /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
  foreach ($entity_manager->getStorage('field_storage_config')->loadMultiple() as $field_storage) {
    if (_views_field_get_entity_type_storage($field_storage)) {
      $function = $field_storage->getTypeProvider() . '_field_views_data_views_data_alter';
      if (function_exists($function)) {
        $function($data, $field_storage);
      }
    }
  }
}

/**
 * Determines whether the entity type the field appears in is SQL based.
 *
 * @param \Drupal\field\FieldStorageConfigInterface $field_storage
 *   The field storage definition.
 *
 * @return \Drupal\Core\Entity\Sql\SqlContentEntityStorage
 *   Returns the entity type storage if supported.
 */
function _views_field_get_entity_type_storage(FieldStorageConfigInterface $field_storage) {
  $result = FALSE;
  $entity_manager = \Drupal::entityManager();
  if ($entity_manager->hasDefinition($field_storage->getTargetEntityTypeId())) {
    $storage = $entity_manager->getStorage($field_storage->getTargetEntityTypeId());
    $result = $storage instanceof SqlContentEntityStorage ? $storage : FALSE;
  }
  return $result;
}

/**
 * Returns the label of a certain field.
 *
 * Therefore it looks up in all bundles to find the most used field.
 */
function views_entity_field_label($entity_type, $field_name) {
  $label_counter = [];
  $all_labels = [];
  // Count the amount of fields per label per field storage.
  foreach (array_keys(\Drupal::entityManager()->getBundleInfo($entity_type)) as $bundle) {
    $bundle_fields = array_filter(\Drupal::entityManager()->getFieldDefinitions($entity_type, $bundle), function ($field_definition) {
      return $field_definition instanceof FieldConfigInterface;
    });
    if (isset($bundle_fields[$field_name])) {
      $field = $bundle_fields[$field_name];
      $label = $field->getLabel();
      $label_counter[$label] = isset($label_counter[$label]) ? ++$label_counter[$label] : 1;
      $all_labels[$label] = TRUE;
    }
  }
  if (empty($label_counter)) {
    return [$field_name, $all_labels];
  }
  // Sort the field labels by it most used label and return the most used one.
  // If the counts are equal, sort by the label to ensure the result is
  // deterministic.
  uksort($label_counter, function ($a, $b) use ($label_counter) {
    if ($label_counter[$a] === $label_counter[$b]) {
      return strcmp($a, $b);
    }
    return $label_counter[$a] > $label_counter[$b] ? -1 : 1;
  });
  $label_counter = array_keys($label_counter);
  return [$label_counter[0], $all_labels];
}

/**
 * Default views data implementation for a field.
 *
 * @param \Drupal\field\FieldStorageConfigInterface $field_storage
 *   The field definition.
 *
 * @return array
 *   The default views data for the field.
 */
function views_field_default_views_data(FieldStorageConfigInterface $field_storage) {
  $data = [];

  // Check the field type is available.
  if (!\Drupal::service('plugin.manager.field.field_type')->hasDefinition($field_storage->getType())) {
    return $data;
  }
  // Check the field storage has fields.
  if (!$field_storage->getBundles()) {
    return $data;
  }

  // Ignore custom storage too.
  if ($field_storage->hasCustomStorage()) {
    return $data;
  }

  // Check whether the entity type storage is supported.
  $storage = _views_field_get_entity_type_storage($field_storage);
  if (!$storage) {
    return $data;
  }

  $field_name = $field_storage->getName();
  $field_columns = $field_storage->getColumns();

  // Grab information about the entity type tables.
  // We need to join to both the base table and the data table, if available.
  $entity_manager = \Drupal::entityManager();
  $entity_type_id = $field_storage->getTargetEntityTypeId();
  $entity_type = $entity_manager->getDefinition($entity_type_id);
  if (!$base_table = $entity_type->getBaseTable()) {
    // We cannot do anything if for some reason there is no base table.
    return $data;
  }
  $entity_tables = [$base_table => $entity_type_id];
  // Some entities may not have a data table.
  $data_table = $entity_type->getDataTable();
  if ($data_table) {
    $entity_tables[$data_table] = $entity_type_id;
  }
  $entity_revision_table = $entity_type->getRevisionTable();
  $supports_revisions = $entity_type->hasKey('revision') && $entity_revision_table;
  if ($supports_revisions) {
    $entity_tables[$entity_revision_table] = $entity_type_id;
    $entity_revision_data_table = $entity_type->getRevisionDataTable();
    if ($entity_revision_data_table) {
      $entity_tables[$entity_revision_data_table] = $entity_type_id;
    }
  }

  // Description of the field tables.
  // @todo Generalize this code to make it work with any table layout. See
  //   https://www.drupal.org/node/2079019.
  $table_mapping = $storage->getTableMapping();
  $field_tables = [
    EntityStorageInterface::FIELD_LOAD_CURRENT => [
      'table' => $table_mapping->getDedicatedDataTableName($field_storage),
      'alias' => "{$entity_type_id}__{$field_name}",
    ],
  ];
  if ($supports_revisions) {
    $field_tables[EntityStorageInterface::FIELD_LOAD_REVISION] = [
      'table' => $table_mapping->getDedicatedRevisionTableName($field_storage),
      'alias' => "{$entity_type_id}_revision__{$field_name}",
    ];
  }

  // Determine if the fields are translatable.
  $bundles_names = $field_storage->getBundles();
  $translation_join_type = FALSE;
  $fields = [];
  $translatable_configs = [];
  $untranslatable_configs = [];
  $untranslatable_config_bundles = [];

  foreach ($bundles_names as $bundle) {
    $fields[$bundle] = FieldConfig::loadByName($entity_type->id(), $bundle, $field_name);
  }
  foreach ($fields as $bundle => $config_entity) {
    if (!empty($config_entity)) {
      if ($config_entity->isTranslatable()) {
        $translatable_configs[$bundle] = $config_entity;
      }
      else {
        $untranslatable_configs[$bundle] = $config_entity;
      }
    }
    else {
      // https://www.drupal.org/node/2451657#comment-11462881
      \Drupal::logger('views')->error(
        'A non-existent config entity name returned by FieldStorageConfigInterface::getBundles(): entity type: %entity_type, bundle: %bundle, field name: %field',
        [
          '%entity_type' => $entity_type->id(),
          '%bundle' => $bundle,
          '%field' => $field_name,
        ]
      );
    }
  }

  // If the field is translatable on all the bundles, there will be a join on
  // the langcode.
  if (!empty($translatable_configs) && empty($untranslatable_configs)) {
    $translation_join_type = 'language';
  }
  // If the field is translatable only on certain bundles, there will be a join
  // on langcode OR bundle name.
  elseif (!empty($translatable_configs) && !empty($untranslatable_configs)) {
    foreach ($untranslatable_configs as $config) {
      $untranslatable_config_bundles[] = $config->getTargetBundle();
    }
    $translation_join_type = 'language_bundle';
  }

  // Build the relationships between the field table and the entity tables.
  $table_alias = $field_tables[EntityStorageInterface::FIELD_LOAD_CURRENT]['alias'];
  if ($data_table) {
    // Tell Views how to join to the base table, via the data table.
    $data[$table_alias]['table']['join'][$data_table] = [
      'table' => $table_mapping->getDedicatedDataTableName($field_storage),
      'left_field' => $entity_type->getKey('id'),
      'field' => 'entity_id',
      'extra' => [
        ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
      ],
    ];
  }
  else {
    // If there is no data table, just join directly.
    $data[$table_alias]['table']['join'][$base_table] = [
      'table' => $table_mapping->getDedicatedDataTableName($field_storage),
      'left_field' => $entity_type->getKey('id'),
      'field' => 'entity_id',
      'extra' => [
        ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
      ],
    ];
  }

  if ($translation_join_type === 'language_bundle') {
    $data[$table_alias]['table']['join'][$data_table]['join_id'] = 'field_or_language_join';
    $data[$table_alias]['table']['join'][$data_table]['extra'][] = [
      'left_field' => 'langcode',
      'field' => 'langcode',
    ];
    $data[$table_alias]['table']['join'][$data_table]['extra'][] = [
      'field' => 'bundle',
      'value' => $untranslatable_config_bundles,
    ];
  }
  elseif ($translation_join_type === 'language') {
    $data[$table_alias]['table']['join'][$data_table]['extra'][] = [
      'left_field' => 'langcode',
      'field' => 'langcode',
    ];
  }

  if ($supports_revisions) {
    $table_alias = $field_tables[EntityStorageInterface::FIELD_LOAD_REVISION]['alias'];
    if ($entity_revision_data_table) {
      // Tell Views how to join to the revision table, via the data table.
      $data[$table_alias]['table']['join'][$entity_revision_data_table] = [
        'table' => $table_mapping->getDedicatedRevisionTableName($field_storage),
        'left_field' => $entity_type->getKey('revision'),
        'field' => 'revision_id',
        'extra' => [
          ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
        ],
      ];
    }
    else {
      // If there is no data table, just join directly.
      $data[$table_alias]['table']['join'][$entity_revision_table] = [
        'table' => $table_mapping->getDedicatedRevisionTableName($field_storage),
        'left_field' => $entity_type->getKey('revision'),
        'field' => 'revision_id',
        'extra' => [
          ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
        ],
      ];
    }
    if ($translation_join_type === 'language_bundle') {
      $data[$table_alias]['table']['join'][$entity_revision_data_table]['join_id'] = 'field_or_language_join';
      $data[$table_alias]['table']['join'][$entity_revision_data_table]['extra'][] = [
        'left_field' => 'langcode',
        'field' => 'langcode',
      ];
      $data[$table_alias]['table']['join'][$entity_revision_data_table]['extra'][] = [
        'value' => $untranslatable_config_bundles,
        'field' => 'bundle',
      ];
    }
    elseif ($translation_join_type === 'language') {
      $data[$table_alias]['table']['join'][$entity_revision_data_table]['extra'][] = [
        'left_field' => 'langcode',
        'field' => 'langcode',
      ];
    }
  }

  $group_name = $entity_type->getLabel();
  // Get the list of bundles the field appears in.
  $bundles_names = $field_storage->getBundles();
  // Build the list of additional fields to add to queries.
  $add_fields = ['delta', 'langcode', 'bundle'];
  foreach (array_keys($field_columns) as $column) {
    $add_fields[] = $table_mapping->getFieldColumnName($field_storage, $column);
  }
  // Determine the label to use for the field. We don't have a label available
  // at the field level, so we just go through all fields and take the one
  // which is used the most frequently.
  list($label, $all_labels) = views_entity_field_label($entity_type_id, $field_name);

  // Expose data for the field as a whole.
  foreach ($field_tables as $type => $table_info) {
    $table = $table_info['table'];
    $table_alias = $table_info['alias'];

    if ($type == EntityStorageInterface::FIELD_LOAD_CURRENT) {
      $group = $group_name;
      $field_alias = $field_name;
    }
    else {
      $group = t('@group (historical data)', ['@group' => $group_name]);
      $field_alias = $field_name . '-revision_id';
    }

    $data[$table_alias][$field_alias] = [
      'group' => $group,
      'title' => $label,
      'title short' => $label,
      'help' => t('Appears in: @bundles.', ['@bundles' => implode(', ', $bundles_names)]),
    ];

    // Go through and create a list of aliases for all possible combinations of
    // entity type + name.
    $aliases = [];
    $also_known = [];
    foreach ($all_labels as $label_name => $true) {
      if ($type == EntityStorageInterface::FIELD_LOAD_CURRENT) {
        if ($label != $label_name) {
          $aliases[] = [
            'base' => $base_table,
            'group' => $group_name,
            'title' => $label_name,
            'help' => t('This is an alias of @group: @field.', ['@group' => $group_name, '@field' => $label]),
          ];
          $also_known[] = t('@group: @field', ['@group' => $group_name, '@field' => $label_name]);
        }
      }
      elseif ($supports_revisions && $label != $label_name) {
        $aliases[] = [
          'base' => $table,
          'group' => t('@group (historical data)', ['@group' => $group_name]),
          'title' => $label_name,
          'help' => t('This is an alias of @group: @field.', ['@group' => $group_name, '@field' => $label]),
        ];
        $also_known[] = t('@group (historical data): @field', ['@group' => $group_name, '@field' => $label_name]);
      }
    }
    if ($aliases) {
      $data[$table_alias][$field_alias]['aliases'] = $aliases;
      // The $also_known variable contains markup that is HTML escaped and that
      // loses safeness when imploded. The help text is used in #description
      // and therefore XSS admin filtered by default. Escaped HTML is not
      // altered by XSS filtering, therefore it is safe to just concatenate the
      // strings. Afterwards we mark the entire string as safe, so it won't be
      // escaped, no matter where it is used.
      // Considering the dual use of this help data (both as metadata and as
      // help text), other patterns such as use of #markup would not be correct
      // here.
      $data[$table_alias][$field_alias]['help'] = Markup::create($data[$table_alias][$field_alias]['help'] . ' ' . t('Also known as:') . ' ' . implode(', ', $also_known));
    }

    $keys = array_keys($field_columns);
    $real_field = reset($keys);
    $data[$table_alias][$field_alias]['field'] = [
      'table' => $table,
      'id' => 'field',
      'field_name' => $field_name,
      'entity_type' => $entity_type_id,
      // Provide a real field for group by.
      'real field' => $field_alias . '_' . $real_field,
      'additional fields' => $add_fields,
      // Default the element type to div, let the UI change it if necessary.
      'element type' => 'div',
      'is revision' => $type == EntityStorageInterface::FIELD_LOAD_REVISION,
    ];
  }

  // Expose data for each field property individually.
  foreach ($field_columns as $column => $attributes) {
    $allow_sort = TRUE;

    // Identify likely filters and arguments for each column based on field type.
    switch ($attributes['type']) {
      case 'int':
      case 'mediumint':
      case 'tinyint':
      case 'bigint':
      case 'serial':
      case 'numeric':
      case 'float':
        $filter = 'numeric';
        $argument = 'numeric';
        $sort = 'standard';
        if ($field_storage->getType() == 'boolean') {
          $filter = 'boolean';
        }
        break;
      case 'text':
      case 'blob':
        // It does not make sense to sort by blob or text.
        $allow_sort = FALSE;
      default:
        $filter = 'string';
        $argument = 'string';
        $sort = 'standard';
        break;
    }

    if (count($field_columns) == 1 || $column == 'value') {
      $title = t('@label (@name)', ['@label' => $label, '@name' => $field_name]);
      $title_short = $label;
    }
    else {
      $title = t('@label (@name:@column)', ['@label' => $label, '@name' => $field_name, '@column' => $column]);
      $title_short = t('@label:@column', ['@label' => $label, '@column' => $column]);
    }

    // Expose data for the property.
    foreach ($field_tables as $type => $table_info) {
      $table = $table_info['table'];
      $table_alias = $table_info['alias'];

      if ($type == EntityStorageInterface::FIELD_LOAD_CURRENT) {
        $group = $group_name;
      }
      else {
        $group = t('@group (historical data)', ['@group' => $group_name]);
      }
      $column_real_name = $table_mapping->getFieldColumnName($field_storage, $column);

      // Load all the fields from the table by default.
      $additional_fields = $table_mapping->getAllColumns($table);

      $data[$table_alias][$column_real_name] = [
        'group' => $group,
        'title' => $title,
        'title short' => $title_short,
        'help' => t('Appears in: @bundles.', ['@bundles' => implode(', ', $bundles_names)]),
      ];

      // Go through and create a list of aliases for all possible combinations of
      // entity type + name.
      $aliases = [];
      $also_known = [];
      foreach ($all_labels as $label_name => $true) {
        if ($label != $label_name) {
          if (count($field_columns) == 1 || $column == 'value') {
            $alias_title = t('@label (@name)', ['@label' => $label_name, '@name' => $field_name]);
          }
          else {
            $alias_title = t('@label (@name:@column)', ['@label' => $label_name, '@name' => $field_name, '@column' => $column]);
          }
          $aliases[] = [
            'group' => $group_name,
            'title' => $alias_title,
            'help' => t('This is an alias of @group: @field.', ['@group' => $group_name, '@field' => $title]),
          ];
          $also_known[] = t('@group: @field', ['@group' => $group_name, '@field' => $title]);
        }
      }
      if ($aliases) {
        $data[$table_alias][$column_real_name]['aliases'] = $aliases;
        // The $also_known variable contains markup that is HTML escaped and
        // that loses safeness when imploded. The help text is used in
        // #description and therefore XSS admin filtered by default. Escaped
        // HTML is not altered by XSS filtering, therefore it is safe to just
        // concatenate the strings. Afterwards we mark the entire string as
        // safe, so it won't be escaped, no matter where it is used.
        // Considering the dual use of this help data (both as metadata and as
        // help text), other patterns such as use of #markup would not be
        // correct here.
        $data[$table_alias][$column_real_name]['help'] = Markup::create($data[$table_alias][$column_real_name]['help'] . ' ' . t('Also known as:') . ' ' . implode(', ', $also_known));
      }

      $data[$table_alias][$column_real_name]['argument'] = [
        'field' => $column_real_name,
        'table' => $table,
        'id' => $argument,
        'additional fields' => $additional_fields,
        'field_name' => $field_name,
        'entity_type' => $entity_type_id,
        'empty field name' => t('- No value -'),
      ];
      $data[$table_alias][$column_real_name]['filter'] = [
        'field' => $column_real_name,
        'table' => $table,
        'id' => $filter,
        'additional fields' => $additional_fields,
        'field_name' => $field_name,
        'entity_type' => $entity_type_id,
        'allow empty' => TRUE,
      ];
      if (!empty($allow_sort)) {
        $data[$table_alias][$column_real_name]['sort'] = [
          'field' => $column_real_name,
          'table' => $table,
          'id' => $sort,
          'additional fields' => $additional_fields,
          'field_name' => $field_name,
          'entity_type' => $entity_type_id,
        ];
      }

      // Set click sortable if there is a field definition.
      if (isset($data[$table_alias][$field_name]['field'])) {
        $data[$table_alias][$field_name]['field']['click sortable'] = $allow_sort;
      }

      // Expose additional delta column for multiple value fields.
      if ($field_storage->isMultiple()) {
        $title_delta = t('@label (@name:delta)', ['@label' => $label, '@name' => $field_name]);
        $title_short_delta = t('@label:delta', ['@label' => $label]);

        $data[$table_alias]['delta'] = [
          'group' => $group,
          'title' => $title_delta,
          'title short' => $title_short_delta,
          'help' => t('Delta - Appears in: @bundles.', ['@bundles' => implode(', ', $bundles_names)]),
        ];
        $data[$table_alias]['delta']['field'] = [
          'id' => 'numeric',
        ];
        $data[$table_alias]['delta']['argument'] = [
          'field' => 'delta',
          'table' => $table,
          'id' => 'numeric',
          'additional fields' => $additional_fields,
          'empty field name' => t('- No value -'),
          'field_name' => $field_name,
          'entity_type' => $entity_type_id,
        ];
        $data[$table_alias]['delta']['filter'] = [
          'field' => 'delta',
          'table' => $table,
          'id' => 'numeric',
          'additional fields' => $additional_fields,
          'field_name' => $field_name,
          'entity_type' => $entity_type_id,
          'allow empty' => TRUE,
        ];
        $data[$table_alias]['delta']['sort'] = [
          'field' => 'delta',
          'table' => $table,
          'id' => 'standard',
          'additional fields' => $additional_fields,
          'field_name' => $field_name,
          'entity_type' => $entity_type_id,
        ];
      }
    }
  }

  return $data;
}

/**
 * Implements hook_field_views_data().
 *
 * The function implements the hook in behalf of 'core' because it adds a
 * relationship and a reverse relationship to entity_reference field type, which
 * is provided by core.
 */
function core_field_views_data(FieldStorageConfigInterface $field_storage) {
  $data = views_field_default_views_data($field_storage);

  // The code below only deals with the Entity reference field type.
  if ($field_storage->getType() != 'entity_reference') {
    return $data;
  }

  $entity_manager = \Drupal::entityManager();
  $entity_type_id = $field_storage->getTargetEntityTypeId();
  /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
  $table_mapping = $entity_manager->getStorage($entity_type_id)->getTableMapping();

  foreach ($data as $table_name => $table_data) {
    // Add a relationship to the target entity type.
    $target_entity_type_id = $field_storage->getSetting('target_type');
    $target_entity_type = $entity_manager->getDefinition($target_entity_type_id);
    $entity_type_id = $field_storage->getTargetEntityTypeId();
    $entity_type = $entity_manager->getDefinition($entity_type_id);
    $target_base_table = $target_entity_type->getDataTable() ?: $target_entity_type->getBaseTable();
    $field_name = $field_storage->getName();

    // Provide a relationship for the entity type with the entity reference
    // field.
    $args = [
      '@label' => $target_entity_type->getLabel(),
      '@field_name' => $field_name,
    ];
    $data[$table_name][$field_name]['relationship'] = [
      'title' => t('@label referenced from @field_name', $args),
      'label' => t('@field_name: @label', $args),
      'group' => $entity_type->getLabel(),
      'help' => t('Appears in: @bundles.', ['@bundles' => implode(', ', $field_storage->getBundles())]),
      'id' => 'standard',
      'base' => $target_base_table,
      'entity type' => $target_entity_type_id,
      'base field' => $target_entity_type->getKey('id'),
      'relationship field' => $field_name . '_target_id',
    ];

    // Provide a reverse relationship for the entity type that is referenced by
    // the field.
    $args['@entity'] = $entity_type->getLabel();
    $args['@label'] = $target_entity_type->getLowercaseLabel();
    $pseudo_field_name = 'reverse__' . $entity_type_id . '__' . $field_name;
    $data[$target_base_table][$pseudo_field_name]['relationship'] = [
      'title' => t('@entity using @field_name', $args),
      'label' => t('@field_name', ['@field_name' => $field_name]),
      'group' => $target_entity_type->getLabel(),
      'help' => t('Relate each @entity with a @field_name set to the @label.', $args),
      'id' => 'entity_reverse',
      'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
      'entity_type' => $entity_type_id,
      'base field' => $entity_type->getKey('id'),
      'field_name' => $field_name,
      'field table' => $table_mapping->getDedicatedDataTableName($field_storage),
      'field field' => $field_name . '_target_id',
      'join_extra' => [
        [
          'field' => 'deleted',
          'value' => 0,
          'numeric' => TRUE,
        ],
      ],
    ];
  }

  return $data;
}
