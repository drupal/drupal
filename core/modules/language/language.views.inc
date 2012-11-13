<?php

/**
 * @file
 * Provide views data and handlers for language.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function language_views_data() {
  $data['language']['table']['group']  = t('Language');

  $data['language']['table']['base'] = array(
    'field' => 'langcode',
    'title' => t('Language'),
    'help' => t('A language used in drupal.'),
  );

  $data['language']['langcode'] = array(
    'title' => t('Language code'),
    'help' => t("Language code, e.g. 'de' or 'en-US'."),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string'
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['language']['name'] = array(
    'title' => t('Language name'),
    'help' => t("Language name, e.g. 'German' or 'English'."),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string'
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['language']['direction'] = array(
    'title' => t('Direction'),
    'help' => t('Direction of language (Left-to-Right = 0, Right-to-Left = 1).'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric'
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['language']['weight'] = array(
    'title' => t('Weight'),
    'help' => t('Weight, used in lists of languages.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric'
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['language']['locked'] = array(
    'title' => t('Locked'),
    'help' => t('A boolean indicating whether the administrator can edit or delete the language.'),
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

  return $data;
}
