<?php

/**
 * @file
 * Provide views data and handlers for user.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function user_views_data() {
  // users table

  // Define the base group of this table. Fields that don't
  // have a group defined will go into this field by default.
  $data['users']['table']['group']  = t('User');

  $data['users']['table']['base'] = array(
    'field' => 'uid',
    'title' => t('User'),
    'help' => t('Users who have created accounts on your site.'),
    'access query tag' => 'user_access',
  );
  $data['users']['table']['entity type'] = 'user';
  $data['users']['table']['wizard_id'] = 'user';

  // uid
  $data['users']['uid'] = array(
    'title' => t('Uid'),
    'help' => t('The user ID'), // The help that appears on the UI,
    'field' => array(
      'id' => 'user',
    ),
    'argument' => array(
      'id' => 'user_uid',
      'name field' => 'name', // display this field in the summary
    ),
    'filter' => array(
      'title' => t('Name'),
      'id' => 'user_name',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'relationship' => array(
      'title' => t('Content authored'),
      'help' => t('Relate content to the user who created it. This relationship will create one record for each content item created by the user.'),
      'id' => 'standard',
      'base' => 'node',
      'base field' => 'uid',
      'field' => 'uid',
      'label' => t('nodes'),
    ),
  );

  // uid_raw
  $data['users']['uid_raw'] = array(
    'help' => t('The raw numeric user ID.'),
    'real field' => 'uid',
    'filter' => array(
      'title' => t('The user ID'),
      'id' => 'numeric',
    ),
  );

  // uid
  $data['users']['uid_representative'] = array(
    'relationship' => array(
      'title' => t('Representative node'),
      'label'  => t('Representative node'),
      'help' => t('Obtains a single representative node for each user, according to a chosen sort criterion.'),
      'id' => 'groupwise_max',
      'relationship field' => 'uid',
      'outer field' => 'users.uid',
      'argument table' => 'users',
      'argument field' => 'uid',
      'base' => 'node',
      'field' => 'nid',
      'relationship' => 'node:uid'
    ),
  );

  // uid
  $data['users']['uid_current'] = array(
    'real field' => 'uid',
    'title' => t('Current'),
    'help' => t('Filter the view to the currently logged in user.'),
    'filter' => array(
      'id' => 'user_current',
      'type' => 'yes-no',
    ),
  );

  // name
  $data['users']['name'] = array(
    'title' => t('Name'), // The item it appears as on the UI,
    'help' => t('The user or author name.'), // The help that appears on the UI,
    'field' => array(
      'id' => 'user_name',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
      'title' => t('Name (raw)'),
      'help' => t('The user or author name. This filter does not check if the user exists and allows partial matching. Does not utilize autocomplete.')
    ),
  );

  // mail
  // Note that this field implements field level access control.
  $data['users']['mail'] = array(
    'title' => t('E-mail'),
    'help' => t('E-mail address for a given user. This field is normally not shown to users, so be cautious when using it.'),
    'field' => array(
      'id' => 'user_mail',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  // language
  $data['users']['langcode'] = array(
    'title' => t('Language'), // The item it appears as on the UI,
    'help' => t('Language of the user'),
    'field' => array(
      'id' => 'user_language',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'language',
    ),
    'argument' => array(
      'id' => 'language',
    ),
  );

  // link
  $data['users']['view_user'] = array(
    'field' => array(
      'title' => t('Link to user'),
      'help' => t('Provide a simple link to the user.'),
      'id' => 'user_link',
      'click sortable' => FALSE,
    ),
  );

  // created field
  $data['users']['created'] = array(
    'title' => t('Created date'), // The item it appears as on the UI,
    'help' => t('The date the user was created.'), // The help that appears on the UI,
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date'
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  $data['users']['created_fulldate'] = array(
    'title' => t('Created date'),
    'help' => t('Date in the form of CCYYMMDD.'),
    'argument' => array(
      'field' => 'created',
      'id' => 'node_created_fulldate',
    ),
  );

  $data['users']['created_year_month'] = array(
    'title' => t('Created year + month'),
    'help' => t('Date in the form of YYYYMM.'),
    'argument' => array(
      'field' => 'created',
      'id' => 'node_created_year_month',
    ),
  );

  $data['users']['created_year'] = array(
    'title' => t('Created year'),
    'help' => t('Date in the form of YYYY.'),
    'argument' => array(
      'field' => 'created',
      'id' => 'node_created_year',
    ),
  );

  $data['users']['created_month'] = array(
    'title' => t('Created month'),
    'help' => t('Date in the form of MM (01 - 12).'),
    'argument' => array(
      'field' => 'created',
      'id' => 'node_created_month',
    ),
  );

  $data['users']['created_day'] = array(
    'title' => t('Created day'),
    'help' => t('Date in the form of DD (01 - 31).'),
    'argument' => array(
      'field' => 'created',
      'id' => 'node_created_day',
    ),
  );

  $data['users']['created_week'] = array(
    'title' => t('Created week'),
    'help' => t('Date in the form of WW (01 - 53).'),
    'argument' => array(
      'field' => 'created',
      'id' => 'node_created_week',
    ),
  );

  // access field
  $data['users']['access'] = array(
    'title' => t('Last access'), // The item it appears as on the UI,
    'help' => t("The user's last access date."), // The help that appears on the UI,
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date'
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  // login field
  $data['users']['login'] = array(
    'title' => t('Last login'), // The item it appears as on the UI,
    'help' => t("The user's last login date."), // The help that appears on the UI,
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date'
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  // active status
  $data['users']['status'] = array(
    'title' => t('Active status'),
    'help' => t('Whether a user is active or blocked.'), // The help that appears on the UI,
     // Information for displaying a title as a field
    'field' => array(
      'id' => 'boolean',
      'output formats' => array(
        'active-blocked' => array(t('Active'), t('Blocked')),
      ),
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Active'),
      'type' => 'yes-no',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // log field
  $data['users']['signature'] = array(
    'title' => t('Signature'), // The item it appears as on the UI,
    'help' => t("The user's signature."), // The help that appears on the UI,
     // Information for displaying a title as a field
    'field' => array(
      'id' => 'markup',
      'format' => filter_fallback_format(),
      'click sortable' => FALSE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  // Entity translation field.
  if (drupal_container()->get('module_handler')->moduleExists('translation_entity')) {
    $data['users']['translation_link'] = array(
      'title' => t('Translation link'),
      'help' => t('Provide a link to the translations overview for users.'),
      'field' => array(
        'id' => 'translation_entity_link',
      ),
    );
  }

  $data['users']['edit_node'] = array(
    'field' => array(
      'title' => t('Link to edit user'),
      'help' => t('Provide a simple link to edit the user.'),
      'id' => 'user_link_edit',
      'click sortable' => FALSE,
    ),
  );

  $data['users']['cancel_node'] = array(
    'field' => array(
      'title' => t('Link to cancel user'),
      'help' => t('Provide a simple link to cancel the user.'),
      'id' => 'user_link_cancel',
      'click sortable' => FALSE,
    ),
  );

  // users_roles table

  $data['users_roles']['table']['group']  = t('User');

  // Explain how this table joins to others.
  $data['users_roles']['table']['join'] = array(
     // Directly links to users table.
    'users' => array(
      'left_field' => 'uid',
      'field' => 'uid',
    ),
  );

  $data['users_roles']['rid'] = array(
    'title' => t('Roles'),
    'help' => t('Roles that a user belongs to.'),
    'field' => array(
      'id' => 'user_roles',
      'no group by' => TRUE,
    ),
    'filter' => array(
      'id' => 'user_roles',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'id' => 'users_roles_rid',
      'name table' => 'role',
      'name field' => 'name',
      'empty field name' => t('No role'),
      'zero is null' => TRUE,
      'numeric' => TRUE,
    ),
  );

  // permission table
  $data['role_permission']['table']['group']  = t('User');
  $data['role_permission']['table']['join'] = array(
     // Directly links to users table.
    'users' => array(
      'left_table' => 'users_roles',
      'left_field' => 'rid',
      'field' => 'rid',
    ),
  );

  $data['role_permission']['permission'] = array(
    'title' => t('Permission'),
    'help' => t('The user permissions.'),
    'field' => array(
      'id' => 'user_permissions',
      'no group by' => TRUE,
    ),
    'filter' => array(
      'id' => 'user_permissions',
    ),
  );

  return $data;
}

/**
 * Allow replacement of current userid so we can cache these queries
 */
function user_views_query_substitutions($view) {
  global $user;
  return array('***CURRENT_USER***' => intval($user->uid));
}
