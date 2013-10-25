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
    'id' => 'random',
    ),
  );

  $data['views']['null'] = array(
    'title' => t('Null'),
    'help' => t('Allow a contextual filter value to be ignored. The query will not be altered by this contextual filter value. Can be used when contextual filter values come from the URL, and a part of the URL needs to be ignored.'),
    'argument' => array(
      'id' => 'null',
    ),
  );

  $data['views']['nothing'] = array(
    'title' => t('Custom text'),
    'help' => t('Provide custom text or link.'),
    'field' => array(
      'id' => 'custom',
    ),
  );

  $data['views']['counter'] = array(
    'title' => t('View result counter'),
    'help' => t('Displays the actual position of the view result'),
    'field' => array(
      'id' => 'counter',
    ),
  );

  $data['views']['area'] = array(
    'title' => t('Text area'),
    'help' => t('Provide markup text for the area.'),
    'area' => array(
      'id' => 'text',
    ),
  );

  $data['views']['area_text_custom'] = array(
    'title' => t('Unfiltered text'),
    'help' => t('Add unrestricted, custom text or markup. This is similar to the custom text field.'),
    'area' => array(
      'id' => 'text_custom',
    ),
  );

  $data['views']['title'] = array(
    'title' => t('Title override'),
    'help' => t('Override the default view title for this view. This is useful to display an alternative title when a view is empty.'),
    'area' => array(
      'id' => 'title',
      'sub_type' => 'empty',
    ),
  );

  $data['views']['view'] = array(
    'title' => t('View area'),
    'help' => t('Insert a view inside an area.'),
    'area' => array(
      'id' => 'view',
    ),
  );

  $data['views']['result'] = array(
    'title' => t('Result summary'),
    'help' => t('Shows result summary, for example the items per page.'),
    'area' => array(
      'id' => 'result',
    ),
  );

  $data['views']['http_status_code'] = array(
    'title' => t('Response status code'),
    'help' => t('Alter the HTTP response status code used by this view, mostly helpful for empty results.'),
    'area' => array(
      'id' => 'http_status_code',
    ),
  );

  $data['views']['combine'] = array(
   'title' => t('Combine fields filter'),
    'help' => t('Combine two fields together and search by them.'),
    'filter' => array(
      'id' => 'combine',
    ),
  );

  $data['views']['dropbutton'] = array(
    'title' => t('Dropbutton'),
    'help' => t('Display fields in a dropbutton.'),
    'field' => array(
      'id' => 'dropbutton',
    ),
  );

  // Registers an entity area handler per entity type.
  foreach (entity_get_info() as $entity_type => $entity_info) {
    // Exclude entity types, which cannot be rendered.
    if (!empty($entity_info['controllers']['view_builder'])) {
      $label = $entity_info['label'];
      $data['views']['entity_' . $entity_type] = array(
        'title' => t('Rendered entity - @label', array('@label' => $label)),
        'help' => t('Displays a rendered @label entity in an area.', array('@label' => $label)),
        'area' => array(
          'entity_type' => $entity_type,
          'id' => 'entity',
        ),
      );
    }
  }

  return $data;
}
