<?php

/**
 * @file
 * Provide views data and handlers for entity_test.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function entity_test_views_data() {
  $data = array();

  $data['entity_test']['table']['group'] = t('Entity test');
  $data['entity_test']['table']['base'] = array(
    'field' => 'id',
    'title' => t('Entity test'),
  );
  $data['entity_test']['table']['entity type'] = 'entity_test';

  $data['entity_test']['id'] = array(
    'title' => t('ID'),
    'help' => t('Primary Key: Unique entity-test item ID.'),
    'argument' => array(
      'id' => 'numeric',
    ),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['entity_test']['uuid'] = array(
    'title' => t('UUID'),
    'help' => t('Unique Key: Universally unique identifier for this entity.'),
    'argument' => array(
      'id' => 'string',
    ),
    'field' => array(
      'id' => 'standard',
      'click sortable' => FALSE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  if (module_exists('langcode')) {
    $data['entity_test']['langcode'] = array(
      'title' => t('Language'),
      'help' => t('The {language}.langcode of the original variant of this test entity.'),
      'field' => array(
        'id' => 'language',
      ),
      'filter' => array(
        'id' => 'language',
      ),
      'argument' => array(
        'id' => 'language',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
  }

  $data['entity_test_property_data']['table']['join']['entity_test'] = array(
    'left_field' => 'id',
    'field' => 'id',
  );

  $data['entity_test_property_data']['id'] = array(
    'title' => t('ID'),
    'help' => t('The {entity_test}.id of the test entity.'),
    'argument' => array(
      'id' => 'numeric',
    ),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  if (module_exists('langcode')) {
    $data['entity_test_property_data']['langcode'] = array(
      'title' => t('Language'),
      'help' => t('The {language}.langcode of this variant of this test entity.'),
      'field' => array(
        'id' => 'language',
      ),
      'filter' => array(
        'id' => 'language',
      ),
      'argument' => array(
        'id' => 'language',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
    $data['entity_test_property_data']['default_langcode'] = array(
      'title' => t('Default langcode'),
      'help' => t('Boolean indicating whether the current variant is in the original entity language.'),
      'field' => array(
        'id' => 'boolean',
      ),
      'filter' => array(
        'id' => 'boolean',
      ),
      'argument' => array(
        'id' => 'numeric',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
    );
  }

  $data['entity_test_property_data']['name'] = array(
    'title' => t('Name'),
    'help' => t('The name of the test entity.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['entity_test_property_data']['user_id'] = array(
    'title' => t('Name'),
    'help' => t('The name of the test entity.'),
    'field' => array(
      'id' => 'user',
    ),
    'filter' => array(
      'id' => 'user_name',
    ),
    'argument' => array(
      'id' => 'uid',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'relationship' => array(
      'title' => t('UID'),
      'help' => t('The The {users}.uid of the associated user.'),
      'base' => 'users',
      'base field' => 'uid',
      'id' => 'standard',
      'label' => t('UID'),
    ),
  );

  return $data;
}
