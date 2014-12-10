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

    $data['users']['table']['base']['help'] = t('Users who have created accounts on your site.');
    $data['users']['table']['base']['access query tag'] = 'user_access';

    $data['users']['table']['wizard_id'] = 'user';

    $data['users']['uid']['field']['id'] = 'user';
    $data['users']['uid']['argument']['id'] = 'user_uid';
    $data['users']['uid']['argument'] += array(
      'name table' => 'users_field_data',
      'name field' => 'name',
      'empty field name' => \Drupal::config('user.settings')->get('anonymous'),
    );
    $data['users']['uid']['filter']['id'] = 'user_name';
    $data['users']['uid']['filter']['title'] = t('Name');
    $data['users']['uid']['relationship'] = array(
      'title' => t('Content authored'),
      'help' => t('Relate content to the user who created it. This relationship will create one record for each content item created by the user.'),
      'id' => 'standard',
      'base' => 'node_field_data',
      'base field' => 'uid',
      'field' => 'uid',
      'label' => t('nodes'),
    );

    $data['users']['uid_raw'] = array(
      'help' => t('The raw numeric user ID.'),
      'real field' => 'uid',
      'filter' => array(
        'title' => t('The user ID'),
        'id' => 'numeric',
      ),
    );

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
    $data['users_field_data']['name']['field']['id'] = 'user_name';
    $data['users_field_data']['name']['filter']['title'] = t('Name (raw)');
    $data['users_field_data']['name']['filter']['help'] = t('The user or author name. This filter does not check if the user exists and allows partial matching. Does not use autocomplete.');

    // Note that this field implements field level access control.
    $data['users_field_data']['mail']['help'] = t('Email address for a given user. This field is normally not shown to users, so be cautious when using it.');
    $data['users_field_data']['mail']['field']['id'] = 'user_mail';

    $data['users']['langcode']['id'] = 'user_language';
    $data['users']['langcode']['help'] = t('Original language of the user information');
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

    $data['users_field_data']['status']['field']['output formats'] = array(
      'active-blocked' => array(t('Active'), t('Blocked')),
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

    unset($data['users_field_data']['signature']);
    unset($data['users_field_data']['signature_format']);

    if (\Drupal::moduleHandler()->moduleExists('filter')) {
      $data['users_field_data']['signature'] = array(
        'title' => t('Signature'),
        'help' => t("The user's signature."),
        'field' => array(
          'id' => 'markup',
          'format' => filter_fallback_format(),
          'click sortable' => FALSE,
        ),
        'filter' => array(
          'id' => 'string',
        ),
      );
    }

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

    $data['user__roles']['table']['group']  = t('User');

    $data['user__roles']['table']['join'] = array(
      'users' => array(
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
