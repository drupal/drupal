<?php

/**
 * @file
 * Provide views data and handlers for taxonomy.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function taxonomy_views_data() {
  $data = array();

  // ----------------------------------------------------------------------
  // taxonomy_vocabulary table

  $data['vocabulary']['moved to'] = 'taxonomy_vocabulary';
  $data['taxonomy_vocabulary']['table']['group']  = t('Taxonomy vocabulary');

  $data['taxonomy_vocabulary']['table']['join'] = array(
    // vocabulary links to taxonomy_term_data directly via vid.
    'taxonomy_term_data' => array(
      'left_field' => 'vid',
      'field' => 'vid',
    ),
  );

  // Provide a "default relationship" to keep older views from choking.
  $data['taxonomy_vocabulary']['table']['default_relationship'] = array(
    'node' => array(
      'table' => 'node',
      'field' => 'term_node_tid',
    ),
  );

  // vocabulary name
  $data['taxonomy_vocabulary']['name'] = array(
    'title' => t('Name'), // The item it appears as on the UI,
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
    'title' => t('Machine name'), // The item it appears as on the UI,
    'field' => array(
      'help' => t('Machine-Name of the vocabulary a term is a member of. This will be the vocabulary that whichever term the "Taxonomy: Term" field is; and can similarly cause duplicates.'),
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'help' => t('Filter the results of "Taxonomy: Term" to a particular vocabulary.'),
      'handler' => 'views_handler_filter_vocabulary_machine_name',
    ),
    'argument' => array(
      'help' => t('Filter the results of "Taxonomy: Term" to a particular vocabulary.'),
      'handler' => 'views_handler_argument_vocabulary_machine_name',
    ),
  );
  $data['taxonomy_vocabulary']['vid'] = array(
    'title' => t('Vocabulary ID'), // The item it appears as on the UI,
    'help' => t('The taxonomy vocabulary ID'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_vocabulary_vid',
      'name field' => 'name',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['taxonomy_vocabulary']['description'] = array(
    'title' => t('Description'), // The item it appears as on the UI,
    'help' => t('The taxonomy vocabulary description'),
    'field' => array(
      'handler' => 'views_handler_field',
    ),
  );
  $data['taxonomy_vocabulary']['weight'] = array(
    'title' => t('Weight'),
    'help' => t('The taxonomy vocabulary weight'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'name field' => 'weight',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
  );

  // ----------------------------------------------------------------------
 // taxonomy_term_data table

  $data['term_data']['moved to'] = 'taxonomy_term_data';
  $data['taxonomy_term_data']['table']['group']  = t('Taxonomy term');
  $data['taxonomy_term_data']['table']['base'] = array(
    'field' => 'tid',
    'title' => t('Term'),
    'help' => t('Taxonomy terms are attached to nodes.'),
    'access query tag' => 'term_access',
  );
  $data['taxonomy_term_data']['table']['entity type'] = 'taxonomy_term';



  // The term data table
  $data['taxonomy_term_data']['table']['join'] = array(
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

  // Provide a "default relationship" to keep older views from choking.
  $data['taxonomy_term_data']['table']['default_relationship'] = array(
    'node' => array(
      'table' => 'node',
      'field' => 'term_node_tid',
    ),
  );

  // tid field
  $data['taxonomy_term_data']['tid'] = array(
    'title' => t('Term ID'),
    'help' => t('The tid of a taxonomy term.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_taxonomy',
      'name field' => 'name',
      'zero is null' => TRUE,
    ),
    'filter' => array(
      'title' => t('Term'),
      'help' => t('Taxonomy term chosen from autocomplete or select widget.'),
      'handler' => 'views_handler_filter_term_node_tid',
      'hierarchy table' => 'taxonomy_term_hierarchy',
      'numeric' => TRUE,
    ),
  );

  // raw tid field
  $data['taxonomy_term_data']['tid_raw'] = array(
    'title' => t('Term ID'),
    'help' => t('The tid of a taxonomy term.'),
    'real field' => 'tid',
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
      'allow empty' => TRUE,
    ),
  );

  $data['taxonomy_term_data']['tid_representative'] = array(
    'relationship' => array(
      'title' => t('Representative node'),
      'label'  => t('Representative node'),
      'help' => t('Obtains a single representative node for each term, according to a chosen sort criterion.'),
      'handler' => 'views_handler_relationship_groupwise_max',
      'relationship field' => 'tid',
      'outer field' => 'taxonomy_term_data.tid',
      'argument table' => 'taxonomy_term_data',
      'argument field' =>  'tid',
      'base'   => 'node',
      'field'  => 'nid',
    ),
  );

  // Term name field
  $data['taxonomy_term_data']['name'] = array(
    'title' => t('Name'),
    'help' => t('The taxonomy term name.'),
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
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  // Term description
  $data['taxonomy_term_data']['description'] = array(
    'title' => t('Term description'),
    'help' => t('The description associated with a taxonomy term.'),
    'field' => array(
      'handler' => 'views_handler_field_markup',
      'format' => array('field' => 'format'),
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
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

  $data['term_node']['moved to'] = 'taxonomy_index';
  $data['taxonomy_index']['table']['group']  = t('Taxonomy term');

  $data['taxonomy_index']['table']['join'] = array(
    'taxonomy_term_data' => array(
      // links directly to taxonomy_term_data via tid
      'left_field' => 'tid',
      'field' => 'tid',
    ),
    'node' => array(
      // links directly to node via nid
      'left_field' => 'nid',
      'field' => 'nid',
    ),
    'taxonomy_term_hierarchy' => array(
      'left_field' => 'tid',
      'field' => 'tid',
    ),
  );

  $data['taxonomy_index']['nid'] = array(
    'title' => t('Content with term'),
    'help' => t('Relate all content tagged with a term.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'node',
      'base field' => 'nid',
      'label' => t('node'),
      'skip base' => 'node',
    ),
  );

  // @todo This stuff needs to move to a node field since
  // really it's all about nodes.
  // tid field
  $data['taxonomy_index']['tid'] = array(
    'group' => t('Content'),
    'title' => t('Has taxonomy term ID'),
    'help' => t('Display content if it has the selected taxonomy terms.'),
    'argument' => array(
      'handler' => 'views_handler_argument_term_node_tid',
      'name table' => 'taxonomy_term_data',
      'name field' => 'name',
      'empty field name' => t('Uncategorized'),
      'numeric' => TRUE,
      'skip base' => 'taxonomy_term_data',
    ),
    'filter' => array(
      'title' => t('Has taxonomy term'),
      'handler' => 'views_handler_filter_term_node_tid',
      'hierarchy table' => 'taxonomy_term_hierarchy',
      'numeric' => TRUE,
      'skip base' => 'taxonomy_term_data',
      'allow empty' => TRUE,
    ),
  );

  // ----------------------------------------------------------------------
  // term_hierarchy table

  $data['taxonomy_term_hierarchy']['table']['group']  = t('Taxonomy term');

  $data['term_hierarchy']['moved to'] = 'taxonomy_term_hierarchy';
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
  );

  // Provide a "default relationship" to keep older views from choking.
  $data['taxonomy_term_hierarchy']['table']['default_relationship'] = array(
    'node' => array(
      'table' => 'node',
      'field' => 'term_node_tid',
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
    'filter' => array(
      'help' => t('Filter the results of "Taxonomy: Term" by the parent pid.'),
      'handler' => 'views_handler_filter_numeric',
    ),
    'argument' => array(
      'help' => t('The parent term of the term.'),
      'handler' => 'views_handler_argument_taxonomy',
    ),
  );

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function taxonomy_views_data_alter(&$data) {
  $data['node']['term_node_tid'] = array(
    'title' => t('Taxonomy terms on node'),
    'help' => t('Relate nodes to taxonomy terms, specifiying which vocabulary or vocabularies to use. This relationship will cause duplicated records if there are multiple terms.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship_node_term_data',
      'label' => t('term'),
      'base' => 'taxonomy_term_data',
    ),
    'field' => array(
      'title' => t('All taxonomy terms'),
      'help' => t('Display all taxonomy terms associated with a node from specified vocabularies.'),
      'handler' => 'views_handler_field_term_node_tid',
      'no group by' => TRUE,
    ),
  );

  $data['node']['term_node_tid_depth'] = array(
    'help' => t('Display content if it has the selected taxonomy terms, or children of the selected terms. Due to additional complexity, this has fewer options than the versions without depth.'),
    'real field' => 'nid',
    'argument' => array(
      'title' => t('Has taxonomy term ID (with depth)'),
      'handler' => 'views_handler_argument_term_node_tid_depth',
      'accept depth modifier' => TRUE,
    ),
    'filter' => array(
      'title' => t('Has taxonomy terms (with depth)'),
      'handler' => 'views_handler_filter_term_node_tid_depth',
    ),
  );

  $data['node']['term_node_tid_depth_modifier'] = array(
    'title' => t('Has taxonomy term ID depth modifier'),
    'help' => t('Allows the "depth" for Taxonomy: Term ID (with depth) to be modified via an additional contextual filter value.'),
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
      if (isset($field_data['filter']) && $field_name != 'delta') {
        $data[$table_name][$field_name]['filter']['handler'] = 'views_handler_filter_term_node_tid';
        $data[$table_name][$field_name]['filter']['vocabulary'] = $field['settings']['allowed_values'][0]['vocabulary'];
      }
    }

    // Add the relationship only on the tid field.
    $data[$table_name][$field['field_name'] . '_tid']['relationship'] = array(
      'handler' => 'views_handler_relationship',
      'base' => 'taxonomy_term_data',
      'base field' => 'tid',
      'label' => t('term from !field_name', array('!field_name' => $field['field_name'])),
    );

  }

  return $data;
}

/**
 * Implements hook_field_views_data_views_data_alter().
 *
 * Views integration to provide reverse relationships on term references.
 */
function taxonomy_field_views_data_views_data_alter(&$data, $field) {
  foreach ($field['bundles'] as $entity_type => $bundles) {
    $entity_info = entity_get_info($entity_type);
    $pseudo_field_name = 'reverse_' . $field['field_name'] . '_' . $entity_type;

    list($label, $all_labels) = field_views_field_label($field['field_name']);
    $entity = $entity_info['label'];
    if ($entity == t('Node')) {
      $entity = t('Content');
    }

    $data['taxonomy_term_data'][$pseudo_field_name]['relationship'] = array(
      'title' => t('@entity using @field', array('@entity' => $entity, '@field' => $label)),
      'help' => t('Relate each @entity with a @field set to the term.', array('@entity' => $entity, '@field' => $label)),
      'handler' => 'views_handler_relationship_entity_reverse',
      'field_name' => $field['field_name'],
      'field table' => _field_sql_storage_tablename($field),
      'field field' => $field['field_name'] . '_tid',
      'base' => $entity_info['base table'],
      'base field' => $entity_info['entity keys']['id'],
      'label' => t('!field_name', array('!field_name' => $field['field_name'])),
      'join_extra' => array(
        0 => array(
          'field' => 'entity_type',
          'value' => $entity_type,
        ),
        1 => array(
          'field' => 'deleted',
          'value' => 0,
          'numeric' => TRUE,
        ),
      ),
    );
  }
}

/**
 * Implements hook_views_plugins().
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
        'title' => t('Taxonomy term ID from URL'),
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
    if (!empty($argument->options['use_taxonomy_term_path'])) {
      $path = taxonomy_term_uri($parent);
      $path = $path['path'];
    }
    else {
      $args[$argument->position] = $parent->tid;
      $path = $argument->view->get_url($args);
    }
    $breadcrumb[$path] = check_plain($parent->name);
  }
}
