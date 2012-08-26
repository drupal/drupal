<?php

/**
 * @file
 * Provide views data and handlers for system.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function system_views_data() {
  $data = array();

  // ----------------------------------------------------------------------
  // system table
  $data['system']['table']['group']  = t('System');

  // Advertise this table as a possible base table
  $data['system']['table']['base'] = array(
    'field' => 'filename',
    'title' => t('Module/Theme/Theme engine'),
    'help' => t('Modules/Themes/Theme engines in your codebase.'),
  );

  // fields
  // - filename
  $data['system']['filename'] = array(
    'title' => t('Module/Theme/Theme engine filename'),
    'help' => t('The path of the primary file for this item, relative to the Drupal root; e.g. modules/node/node.module.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'string',
      'name field' => 'filename', // the field to display in the summary.
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  // - name
  $data['system']['name'] = array(
    'title' => t('Module/Theme/Theme engine name'),
    'help' => t('The name of the item; e.g. node.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'string',
      'name field' => 'name', // the field to display in the summary.
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  // - type
  $data['system']['type'] = array(
    'title' => t('Type'),
    'help' => t('The type of the item, either module, theme, or theme_engine.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'string',
      'name field' => 'type', // the field to display in the summary.
    ),
    'filter' => array(
      'id' => 'filter_system_type',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  // - status
  $data['system']['status'] = array(
    'title' => t('Status'),
    'help' => t('Boolean indicating whether or not this item is enabled.'),
    'field' => array(
      'id' => 'boolean',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
      'name field' => 'status', // the field to display in the summary.
    ),
    'filter' => array(
      'id' => 'boolean',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  // - schema version
  $data['system']['schema_version'] = array(
    'title' => t('Schema version'),
    'help' => t("The module's database schema version number. -1 if the module is not installed (its tables do not exist); 0 or the largest N of the module's hook_update_N() function that has either been run or existed when the module was first installed."),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
      'name field' => 'schema_version', // the field to display in the summary.
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  return $data;
}
