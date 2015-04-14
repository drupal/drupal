<?php

/**
 * @file
 * Contains \Drupal\user\UserViewsData.
 */

namespace Drupal\user;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the user entity type.
 */
class UserViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['users_field_data']['table']['base']['help'] = t('Users who have created accounts on your site.');
    $data['users_field_data']['table']['base']['access query tag'] = 'user_access';

    $data['users_field_data']['table']['wizard_id'] = 'user';

    $data['users_field_data']['uid']['field']['id'] = 'user';
    $data['users_field_data']['uid']['argument']['id'] = 'user_uid';
    $data['users_field_data']['uid']['argument'] += array(
      'name table' => 'users_field_data',
      'name field' => 'name',
      'empty field name' => \Drupal::config('user.settings')->get('anonymous'),
    );
    $data['users_field_data']['uid']['filter']['id'] = 'user_name';
    $data['users_field_data']['uid']['filter']['title'] = t('Name');
    $data['users_field_data']['uid']['relationship'] = array(
      'title' => t('Content authored'),
      'help' => t('Relate content to the user who created it. This relationship will create one record for each content item created by the user.'),
      'id' => 'standard',
      'base' => 'node_field_data',
      'base field' => 'uid',
      'field' => 'uid',
      'label' => t('nodes'),
    );

    $data['users_field_data']['uid_raw'] = array(
      'help' => t('The raw numeric user ID.'),
      'real field' => 'uid',
      'filter' => array(
        'title' => t('The user ID'),
        'id' => 'numeric',
      ),
    );

    $data['users_field_data']['uid_representative'] = array(
      'relationship' => array(
        'title' => t('Representative node'),
        'label'  => t('Representative node'),
        'help' => t('Obtains a single representative node for each user, according to a chosen sort criterion.'),
        'id' => 'groupwise_max',
        'relationship field' => 'uid',
        'outer field' => 'users_field_data.uid',
        'argument table' => 'users_field_data',
        'argument field' => 'uid',
        'base' => 'node_field_data',
        'field' => 'nid',
        'relationship' => 'node_field_data:uid'
      ),
    );

    $data['users']['uid_current'] = array(
      'real field' => 'uid',
      'title' => t('Current'),
      'help' => t('Filter the view to the currently logged in user.'),
      'filter' => array(
        'id' => 'user_current',
        'type' => 'yes-no',
      ),
    );

    $data['users_field_data']['name']['help'] = t('The user or author name.');
    $data['users_field_data']['name']['field']['default_formatter'] = 'user_name';
    $data['users_field_data']['name']['filter']['title'] = t('Name (raw)');
    $data['users_field_data']['name']['filter']['help'] = t('The user or author name. This filter does not check if the user exists and allows partial matching. Does not use autocomplete.');

    // Note that this field implements field level access control.
    $data['users_field_data']['mail']['help'] = t('Email address for a given user. This field is normally not shown to users, so be cautious when using it.');

    $data['users_field_data']['langcode']['help'] = t('Original language of the user information');
    $data['users_field_data']['langcode']['help'] = t('Language of the translation of user information');

    $data['users_field_data']['preferred_langcode']['title'] = t('Preferred language');
    $data['users_field_data']['preferred_langcode']['help'] = t('Preferred language of the user');
    $data['users_field_data']['preferred_admin_langcode']['title'] = t('Preferred admin language');
    $data['users_field_data']['preferred_admin_langcode']['help'] = t('Preferred administrative language of the user');

    $data['users']['view_user'] = array(
      'field' => array(
        'title' => t('Link to user'),
        'help' => t('Provide a simple link to the user.'),
        'id' => 'user_link',
        'click sortable' => FALSE,
      ),
    );

    $data['users_field_data']['created_fulldate'] = array(
      'title' => t('Created date'),
      'help' => t('Date in the form of CCYYMMDD.'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_fulldate',
      ),
    );

    $data['users_field_data']['created_year_month'] = array(
      'title' => t('Created year + month'),
      'help' => t('Date in the form of YYYYMM.'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_year_month',
      ),
    );

    $data['users_field_data']['created_year'] = array(
      'title' => t('Created year'),
      'help' => t('Date in the form of YYYY.'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_year',
      ),
    );

    $data['users_field_data']['created_month'] = array(
      'title' => t('Created month'),
      'help' => t('Date in the form of MM (01 - 12).'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_month',
      ),
    );

    $data['users_field_data']['created_day'] = array(
      'title' => t('Created day'),
      'help' => t('Date in the form of DD (01 - 31).'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_day',
      ),
    );

    $data['users_field_data']['created_week'] = array(
      'title' => t('Created week'),
      'help' => t('Date in the form of WW (01 - 53).'),
      'argument' => array(
        'field' => 'created',
        'id' => 'date_week',
      ),
    );

    $data['users_field_data']['status']['filter']['label'] = t('Active');
    $data['users_field_data']['status']['filter']['type'] = 'yes-no';

    $data['users_field_data']['changed']['title'] = t('Updated date');

    $data['users_field_data']['changed_fulldate'] = array(
      'title' => t('Updated date'),
      'help' => t('Date in the form of CCYYMMDD.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_fulldate',
      ),
    );

    $data['users_field_data']['changed_year_month'] = array(
      'title' => t('Updated year + month'),
      'help' => t('Date in the form of YYYYMM.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_year_month',
      ),
    );

    $data['users_field_data']['changed_year'] = array(
      'title' => t('Updated year'),
      'help' => t('Date in the form of YYYY.'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_year',
      ),
    );

    $data['users_field_data']['changed_month'] = array(
      'title' => t('Updated month'),
      'help' => t('Date in the form of MM (01 - 12).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_month',
      ),
    );

    $data['users_field_data']['changed_day'] = array(
      'title' => t('Updated day'),
      'help' => t('Date in the form of DD (01 - 31).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_day',
      ),
    );

    $data['users_field_data']['changed_week'] = array(
      'title' => t('Updated week'),
      'help' => t('Date in the form of WW (01 - 53).'),
      'argument' => array(
        'field' => 'changed',
        'id' => 'date_week',
      ),
    );

    if (\Drupal::moduleHandler()->moduleExists('content_translation')) {
      $data['users']['translation_link'] = array(
        'title' => t('Translation link'),
        'help' => t('Provide a link to the translations overview for users.'),
        'field' => array(
          'id' => 'content_translation_link',
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

    $data['users']['data'] = array(
      'title' => t('Data'),
      'help' => t('Provides access to the user data service.'),
      'real field' => 'uid',
      'field' => array(
        'id' => 'user_data',
      ),
    );

    $data['users']['user_bulk_form'] = array(
      'title' => t('Bulk update'),
      'help' => t('Add a form element that lets you run operations on multiple users.'),
      'field' => array(
        'id' => 'user_bulk_form',
      ),
    );

    $data['user__roles']['table']['group']  = t('User');

    $data['user__roles']['table']['join'] = array(
      'users_field_data' => array(
        'left_field' => 'uid',
        'field' => 'entity_id',
      ),
    );

    $data['user__roles']['roles_target_id'] = array(
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
        'id' => 'user__roles_target_id',
        'name table' => 'role',
        'name field' => 'name',
        'empty field name' => t('No role'),
        'zero is null' => TRUE,
        'numeric' => TRUE,
      ),
    );

    $data['user__roles']['permission'] = array(
      'title' => t('Permission'),
      'help' => t('The user permissions.'),
      'field' => array(
        'id' => 'user_permissions',
        'no group by' => TRUE,
      ),
      'filter' => array(
        'id' => 'user_permissions',
        'real field' => 'roles_target_id',
      ),
    );

    return $data;
  }

}
