<?php

/**
 * @file
 * Provide views data and handlers for translation.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data_alter().
 *
 * Add translation information to the node table.
 */
function translation_views_data_alter(&$data) {

  // Joins
  $data['node']['table']['join']['node'] = array(
    'left_field' => 'tnid',
    'field' => 'tnid',
  );

  // The translation ID (nid of the "source" translation)
  $data['node']['tnid'] = array(
    'group' => t('Content translation'),
    'title' => t('Translation set node ID'),
    'help' => t('The ID of the translation set the content belongs to.'),
    'field' => array(
      'handler' => 'views_handler_field_node',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_node_tnid',
      'name field' => 'title', // the field to display in the summary.
      'numeric' => TRUE,
      'validate type' => 'tnid',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'relationship' => array(
      'title' => t('Source translation'),
      'help' => t('The source that this content was translated from.'),
      'base' => 'node',
      'base field' => 'nid',
      'handler' => 'views_handler_relationship',
      'label' => t('Source translation'),
    ),
  );

  // The source translation.
  $data['node']['translation'] = array(
    'group' => t('Content translation'),
    'title' => t('Translations'),
    'help' => t('Versions of content in different languages.'),
    'relationship' => array(
      'title' => t('Translations'),
      'help' => t('Versions of content in different languages.'),
      'base' => 'node',
      'base field' => 'tnid',
      'relationship table' => 'node',
      'relationship field' => 'nid',
      'handler' => 'views_handler_relationship_translation',
      'label' => t('Translations'),
    ),
  );

  // The source translation.
  $data['node']['source_translation'] = array(
    'group' => t('Content translation'),
    'title' => t('Source translation'),
    'help' => t('Content that is either untranslated or is the original version of a translation set.'),
    'filter' => array(
      'handler' => 'views_handler_filter_node_tnid',
    ),
  );

  // The source translation.
  $data['node']['child_translation'] = array(
    'group' => t('Node translation'),
    'title' => t('Child translation'),
    'help' => t('Content that is a translation of a source translation.'),
    'filter' => array(
      'handler' => 'views_handler_filter_node_tnid_child',
    ),
  );

  // Translation status
  $data['node']['translate'] = array(
    'group' => t('Content translation'),
    'title' => t('Translation status'),
    'help' => t('The translation status of the content - whether or not the translation needs to be updated.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
      'label' => t('Outdated'),
      'type' => 'yes-no',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Translate node link.
  $data['node']['translate_node'] = array(
    'group' => t('Content translation'),
    'title' => t('Translate link'),
    'help' => t('Provide a simple link to translate the node.'),
    'field' => array(
      'handler' => 'views_handler_field_node_link_translate',
    ),
  );


}
