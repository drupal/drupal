<?php

/**
 * @file
 * Views wizard for user views.
 */

$plugin = array(
  'name' => 'users',
  'base_table' => 'users',
  'created_column' => 'created',
  'form_wizard_class' => array(
    'file' => 'views_ui_users_views_wizard.class.php',
    'class' => 'ViewsUiUsersViewsWizard',
  ),
  'title' => t('Users'),
  'filters' => array(
    'status' => array(
      'value' => '1',
      'table' => 'users',
      'field' => 'status',
    ),
  ),
  'path_field' => array(
    'id' => 'uid',
    'table' => 'users',
    'field' => 'uid',
    'exclude' => TRUE,
    'link_to_user' => FALSE,
    'alter' => array(
      'alter_text' => 1,
      'text' => 'user/[uid]',
    ),
  ),
);
