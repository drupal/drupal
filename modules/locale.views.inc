<?php

/**
 * @file
 * Provides views data and handlers for locale.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function locale_views_data() {
  // Basic table information.

  // Define the base group of this table.
  $data['locales_source']['table']['group']  = t('Locale source');

  // Advertise this table as a possible base table.
  $data['locales_source']['table']['base'] = array(
    'field' => 'lid',
    'title' => t('Locale source'),
    'help' => t('A source string for translation, in English or the default site language.'),
  );

  // lid
  $data['locales_source']['lid'] = array(
    'title' => t('LID'),
    'help' => t('The ID of the source string.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
      'numeric' => TRUE,
      'validate type' => 'lid',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // location
  $data['locales_source']['location'] = array(
    'group' => t('Locale source'),
    'title' => t('Location'),
    'help' => t('A description of the location or context of the string.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
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

  // Source field
  $data['locales_source']['source'] = array(
    'group' => t('Locale source'),
    'title' => t('Source'),
    'help' => t('The full original string.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  // Source field
  $data['locales_source']['context'] = array(
    'group' => t('Locale source'),
    'title' => t('Context'),
    'help' => t('The context this string applies to.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  // Version field
  $data['locales_source']['version'] = array(
    'group' => t('Locale source'),
    'title' => t('Version'),
    'help' => t('The version of Drupal core that this string is for.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'locale_version',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['locales_source']['edit_lid'] = array(
    'group' => t('Locale source'),
    'field' => array(
      'title' => t('Edit link'),
      'help' => t('Provide a simple link to edit the translations.'),
      'id' => 'locale_link_edit',
    ),
  );

  // Locales target table

  // Define the base group of this table. Fields that don't
  // have a group defined will go into this field by default.
  $data['locales_target']['table']['group']  = t('Locale target');

  // Join information
  $data['locales_target']['table']['join'] = array(
    'locales_source' => array(
      'left_field' => 'lid',
      'field' => 'lid',
    ),
  );

  // Translation field
  $data['locales_target']['translation'] = array(
    'group' => t('Locale target'),
    'title' => t('Translation'),
    'help' => t('The full translation string.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  // Language field
  $data['locales_target']['language'] = array(
    'group' => t('Locale target'),
    'title' => t('Language'),
    'help' => t('The language this translation is in.'),
    'field' => array(
      'id' => 'locale_language',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'locale_language',
    ),
    'argument' => array(
      'id' => 'locale_language',
    ),
  );

  $data['locales_target']['plid'] = array(
    'group' => t('Locale target'),
    'title' => t('Singular LID'),
    'help' => t('The ID of the parent translation.'),
    'field' => array(
      'id' => 'standard',
    ),
  );

  // Plural
  $data['locales_target']['plural'] = array(
    'group' => t('Locale target'),
    'title' => t('Plural'),
    'help' => t('Whether or not the translation is plural.'),
    'field' => array(
      'id' => 'boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Plural'),
      'type' => 'yes-no',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  return $data;
}
