<?php
// $Id: taxonomy.views.inc,v 1.57.4.18 2010/12/04 07:46:13 dereine Exp $
/**
 * @file
 *
 * Provide views data and handlers for taxonomy.module
 */

/**
 * @defgroup views_taxonomy_module taxonomy.module handlers
 *
 * @{
 */

/**
 * Implements hook_views_data()
 */
function taxonomy_views_data() {
  $data = array();

  // ----------------------------------------------------------------------
  // taxonomy_vocabulary table

  $data['taxonomy_vocabulary']['table']['group']  = t('Taxonomy');

  $data['taxonomy_vocabulary']['table']['join'] = array(
    // vocabulary links to taxonomy_term_data directly via vid.
    'taxonomy_term_data' => array(
      'left_field' => 'vid',
      'field' => 'vid',
    ),
    // vocabulary links to node through taxonomy_term_data via vid
    'node' => array(
      'left_table' => 'taxonomy_term_data',
      'left_field' => 'vid',
      'field' => 'vid',
    ),
  );

  // vocabulary name
  $data['taxonomy_vocabulary']['name'] = array(
    'title' => t('Vocabulary name'), // The item it appears as on the UI,
    'field' => array(
      'help' => t('Name of the vocabulary a term is a member of. This will be the vocabulary that whichever term the "Taxonomy: Term" field is; and can similarly cause duplicates.'),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
      'help' => t('The taxonomy vocabulary name'),
    ),
  );
  $data['taxonomy_vocabulary']['machine_name'] = array(
    'title' => t('Vocabulary machine name'), // The item it appears as on the UI,
    'field' => array(
      'help' => t('Machine-Name of the vocabulary a term is a member of. This will be the vocabulary that whichever term the "Taxonomy: Term" field is; and can similarly cause duplicates.'),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('Filter the results of "Taxonomy: Term" to a particular vocabulary.'),
      'handler' => 'views_handler_filter_vocabulary_machine_name',
    ),
  );
  $data['taxonomy_vocabulary']['vid'] = array(
    'title' => t('Vocabulary ID'), // The item it appears as on the UI,
    'help' => t('The taxonomy vocabulary ID'),
    'argument' => array(
      'handler' => 'views_handler_argument_vocabulary_vid',
      'name field' => 'name',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // ----------------------------------------------------------------------
 // taxonomy_term_data table

  $data['taxonomy_term_data']['table']['group']  = t('Taxonomy');
  $data['taxonomy_term_data']['table']['base'] = array(
    'field' => 'tid',
    'title' => t('Term'),
    'help' => t('Taxonomy terms are attached to nodes.'),
    'access query tag' => 'term_access',
  );


  // The term data table
  $data['taxonomy_term_data']['table']['join'] = array(
    'node' => array(
      'left_table' => 'taxonomy_index',
      'left_field' => 'tid',
      'field' => 'tid',
    ),
    'taxonomy_vocabulary' => array(
      'field' => 'vid',
      'left_field' => 'vid',
    ),
    // This is provided for many_to_one argument
    'taxonomy_index' => array(
      'field' => 'tid',
      'left_field' => 'tid',
    ),
  );

  // tid field
  $data['taxonomy_term_data']['tid'] = array(
    'title' => t('Term ID'),
    'help' => t('The taxonomy term ID'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'name field' => 'name',
      'skip base' => array('node'),
      'zero is null' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_term_node_tid',
      'hierarchy table' => 'taxonomy_term_hierarchy',
      'numeric' => TRUE,
      'skip base' => array('node'),
    ),
  );

  // Term name field
  $data['taxonomy_term_data']['name'] = array(
    'title' => t('Term'),
    'help' => t('Taxonomy terms. Note that using this can cause duplicate nodes to appear in views; you must add filters to reduce the result set.'),
    'field' => array(
      'handler' => 'views_handler_field_taxonomy',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
      'help' => t('Taxonomy term name.'),
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
      'help' => t('Taxonomy term name.'),
      'many to one' => TRUE,
      'empty field name' => t('Uncategorized'),
    ),
  );

  // taxonomy weight
  $data['taxonomy_term_data']['weight'] = array(
    'title' => t('Weight'),
    'help' => t('The term weight field'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Term description
  $data['taxonomy_term_data']['description'] = array(
    'title' => t('Term description'), // The item it appears as on the UI,
    'help' => t('The description associated with a taxonomy term.'),
    'field' => array(
      'field' => 'description', // the real field
      'group' => t('Taxonomy'), // The group it appears in on the UI,
      'handler' => 'views_handler_field_markup',
      'format' => array('field' => 'format'),
    ),
  );

  // Term vocabulary
  $data['taxonomy_term_data']['vid'] = array(
    'title' => t('Vocabulary'),
    'help' => t('Filter the results of "Taxonomy: Term" to a particular vocabulary.'),
    'filter' => array(
      'handler' => 'views_handler_filter_vocabulary_vid',
    ),
  );

  // Link to edit the term
  $data['taxonomy_term_data']['edit_term'] = array(
    'field' => array(
      'title' => t('Term edit link'),
      'help' => t('Provide a simple link to edit the term.'),
      'handler' => 'views_handler_field_term_link_edit',
    ),
  );

  // ----------------------------------------------------------------------
  // taxonomy_index table

  $data['taxonomy_index']['table']['group']  = t('Taxonomy');

  $data['taxonomy_index']['table']['join'] = array(
    'taxonomy_term_data' => array(
      // links directly to taxonomy_term_data via tid
      'left_field' => 'tid',
      'field' => 'tid',
    ),
    'node' => array(
      // links directly to node via vid
      'left_field' => 'nid',
      'field' => 'nid',
    ),
    'taxonomy_term_hierarchy' => array(
      'left_field' => 'tid',
      'field' => 'tid',
    ),
  );

  $data['taxonomy_index']['nid'] = array(
    'title' => t('Node'),
    'help' => t('Get all nodes tagged with a term.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'node',
      'base field' => 'nid',
      'label' => t('node'),
    ),
  );

  // tid field
  $data['taxonomy_index']['tid'] = array(
    'title' => t('Term ID'),
    'help' => t('The taxonomy term ID'),
    'field' => array(
      'title' => t('All terms'),
      'help' => t('Display all taxonomy terms associated with a node from specified vocabularies.'),
      'handler' => 'views_handler_field_term_node_tid',
      'skip base' => 'taxonomy_term_data',
      'no group by' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_term_node_tid',
      'name table' => 'taxonomy_term_data',
      'name field' => 'name',
      'empty field name' => t('Uncategorized'),
      'numeric' => TRUE,
      'skip base' => 'taxonomy_term_data',
    ),
    'filter' => array(
      'title' => t('Term'),
      'handler' => 'views_handler_filter_term_node_tid',
      'hierarchy table' => 'taxonomy_term_hierarchy',
      'numeric' => TRUE,
      'skip base' => 'taxonomy_term_data',
      'allow empty' => TRUE,
    ),
  );

  // ----------------------------------------------------------------------
  // term_hierarchy table

  $data['taxonomy_term_hierarchy']['table']['group']  = t('Taxonomy');

  $data['taxonomy_term_hierarchy']['table']['join'] = array(
    'taxonomy_term_hierarchy' => array(
      // links to self through left.parent = right.tid (going down in depth)
      'left_field' => 'tid',
      'field' => 'parent',
    ),
    'taxonomy_term_data' => array(
      // links directly to taxonomy_term_data via tid
      'left_field' => 'tid',
      'field' => 'tid',
    ),
    'node' => array(
      // links to node thorugh taxonomy_term_data
      'left_table' => 'taxonomy_term_data',
      'left_field' => 'tid',
      'field' => 'tid',
    ),
  );

  $data['taxonomy_term_hierarchy']['parent'] = array(
    'title' => t('Parent term'),
    'help' => t('The parent term of the term. This can produce duplicate entries if you are using a vocabulary that allows multiple parents.'),
    'relationship' => array(
      'base' => 'taxonomy_term_data',
      'field' => 'parent',
      'label' => t('Parent'),
    ),
    'argument' => array(
      'help' => t('The parent term of the term.'),
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  // ----------------------------------------------------------------------
  // term_synonym table

  $data['term_synonym']['table']['group']  = t('Taxonomy');

  $data['term_synonym']['table']['join'] = array(
    'taxonomy_term_data' => array(
      // links directly to taxonomy_term_data via tid
      'left_field' => 'tid',
      'field' => 'tid',
    ),
    'node' => array(
      'left_table' => 'taxonomy_index',
      'left_field' => 'tid',
      'field' => 'tid',
    ),
  );

  $data['term_synonym']['name'] = array(
    'title' => t('Term synonym'),
    'help' => t('Term synonyms may be used to find terms by alternate names.'),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
      'many to one' => TRUE,
      'empty field name' => t('Uncategorized'),
    ),
  );
  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function taxonomy_views_data_alter(&$data) {
  $data['node']['term_node_tid'] = array(
    'group' => t('Taxonomy'),
    'title' => t('Related terms'),
    'help' => t('Relate nodes to taxonomy terms, specifiying which vocabulary or vocabularies to use. This relationship will cause duplicated records if there are multiple terms.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship_node_term_data',
      'label' => t('term'),
      'base' => 'taxonomy_term_data',
    ),
  );

  $data['node']['term_node_tid_depth'] = array(
    'group' => t('Taxonomy'),
    'title' => t('Term ID (with depth)'),
    'help' => t('The depth filter is more complex, so provides fewer options.'),
    'real field' => 'nid',
    'argument' => array(
      'handler' => 'views_handler_argument_term_node_tid_depth',
      'accept depth modifier' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_term_node_tid_depth',
    ),
  );

  $data['node']['term_node_tid_depth_modifier'] = array(
    'group' => t('Taxonomy'),
    'title' => t('Term ID depth modifier'),
    'help' => t('Allows the "depth" for Taxonomy: Term ID (with depth) to be modified via an additional argument.'),
    'argument' => array(
      'handler' => 'views_handler_argument_term_node_tid_depth_modifier',
    ),
  );
}

/**
 * Implements hook_field_views_data().
 *
 * Views integration for taxonomy_term_reference fields. Adds a term relationship to the default
 * field data.
 *
 * @see field_views_field_default_views_data()
 */
function taxonomy_field_views_data($field) {
  $data = field_views_field_default_views_data($field);
  foreach ($data as $table_name => $table_data) {
    foreach ($table_data as $field_name => $field_data) {
      if (!in_array($field_name, array('table', 'entity_id', 'revision_id'))) {
        $data[$table_name][$field_name]['relationship'] = array(
          'handler' => 'views_handler_relationship',
          'base' => 'taxonomy_term_data',
          'base field' => 'tid',
          'label' => t('term from !field_name', array('!field_name' => $field['field_name'])),
        );
      }
    }
  }
  return $data;
}

/**
 * Implements hook_views_plugins
 */
function taxonomy_views_plugins() {
  return array(
    'module' => 'views', // This just tells our themes are elsewhere.
    'argument validator' => array(
      'taxonomy_term' => array(
        'title' => t('Taxonomy term'),
        'handler' => 'views_plugin_argument_validate_taxonomy_term',
        'path' => drupal_get_path('module', 'views') . '/modules/taxonomy', // not necessary for most modules
      ),
    ),
    'argument default' => array(
      'taxonomy_tid' => array(
        'title' => t('Taxonomy Term ID from URL'),
        'handler' => 'views_plugin_argument_default_taxonomy_tid',
        'path' => drupal_get_path('module', 'views') . '/modules/taxonomy',
        'parent' => 'fixed',
      ),
    ),
  );
}

/**
 * Helper function to set a breadcrumb for taxonomy.
 */
function views_taxonomy_set_breadcrumb(&$breadcrumb, &$argument) {
  if (empty($argument->options['set_breadcrumb'])) {
    return;
  }

  $args = $argument->view->args;
  $parents = taxonomy_get_parents_all($argument->argument);
  foreach (array_reverse($parents) as $parent) {
    // Unfortunately parents includes the current argument. Skip.
    if ($parent->tid == $argument->argument) {
      continue;
    }
    if ($argument->options['use_taxonomy_term_path']) {
      $path = taxonomy_term_path($parent);
    }
    else {
      $args[$argument->position] = $parent->tid;
      $path = $argument->view->get_url($args);
    }
    $breadcrumb[$path] = check_plain($parent->name);
  }
}

/**
 * @}
 */
