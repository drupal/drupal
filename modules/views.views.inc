<?php

/**
 * @file
 * Provide views data and handlers that aren't tied to any other module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function views_views_data() {
  $data['views']['table']['group'] = t('Global');
  $data['views']['table']['join'] = array(
    // #global is a special flag which let's a table appear all the time.
    '#global' => array(),
  );

  $data['views']['random'] = array(
    'title' => t('Random'),
    'help' => t('Randomize the display order.'),
    'sort' => array(
      'handler' => 'views_handler_sort_random',
    ),
  );

  $data['views']['null'] = array(
    'title' => t('Null'),
    'help' => t('Allow a contextual filter value to be ignored. The query will not be altered by this contextual filter value. Can be used when contextual filter values come from the URL, and a part of the URL needs to be ignored.'),
    'argument' => array(
      'handler' => 'views_handler_argument_null',
    ),
  );

  $data['views']['nothing'] = array(
    'title' => t('Custom text'),
    'help' => t('Provide custom text or link.'),
    'field' => array(
      'handler' => 'views_handler_field_custom',
    ),
  );

  $data['views']['counter'] = array(
    'title' => t('View result counter'),
    'help' => t('Displays the actual position of the view result'),
    'field' => array(
      'handler' => 'views_handler_field_counter',
    ),
  );

  $data['views']['area'] = array(
    'title' => t('Text area'),
    'help' => t('Provide markup text for the area.'),
    'area' => array(
      'handler' => 'views_handler_area_text',
    ),
  );

  $data['views']['view'] = array(
    'title' => t('View area'),
    'help' => t('Insert a view inside an area.'),
    'area' => array(
      'handler' => 'views_handler_area_view',
    ),
  );

  $data['views']['result'] = array(
    'title' => t('Result summary'),
    'help' => t('Shows result summary, for example the items per page.'),
    'area' => array(
      'handler' => 'views_handler_area_result',
    ),
  );

  if (module_exists('contextual')) {
    $data['views']['contextual_links'] = array(
      'title' => t('Contextual Links'),
      'help' => t('Display fields in a contextual links menu.'),
      'field' => array(
        'handler' => 'views_handler_field_contextual_links',
      ),
    );
  }

  $data['views']['combine'] = array(
   'title' => t('Combine fields filter'),
    'help' => t('Combine two fields together and search by them.'),
    'filter' => array(
      'handler' => 'views_handler_filter_combine',
    ),
  );

  if (module_invoke('ctools', 'api_version', '1.7.1')) {
    $data['views']['expression'] = array(
      'title' => t('Math expression'),
      'help' => t('Evaluates a mathematical expression and displays it.'),
      'field' => array(
        'handler' => 'views_handler_field_math',
        'float' => TRUE,
      ),
    );
  }

  return $data;
}
