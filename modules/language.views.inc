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

  // name
  // direction
  // weight

  $data['language']['langcode'] = array(
    'title' => t('Language code'),
    'help' => t("Language code, e.g. 'de' or 'en-US'."),
    'field' => array(
      'plugin_id' => 'standard',
    ),
    'filter' => array(
      'plugin_id' => 'string'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['language']['name'] = array(
    'title' => t('Language name'),
    'help' => t("Language name, e.g. 'German' or 'English'."),
    'field' => array(
      'plugin_id' => 'standard',
    ),
    'filter' => array(
      'plugin_id' => 'string'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['language']['direction'] = array(
    'title' => t('Direction'),
    'help' => t('Direction of language (Left-to-Right = 0, Right-to-Left = 1).'),
    'field' => array(
      'plugin_id' => 'numeric',
    ),
    'filter' => array(
      'plugin_id' => 'numeric'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['language']['weight'] = array(
    'title' => t('Weight'),
    'help' => t('Weight, used in lists of languages.'),
    'field' => array(
      'plugin_id' => 'numeric',
    ),
    'filter' => array(
      'plugin_id' => 'numeric'
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  return $data;
}
