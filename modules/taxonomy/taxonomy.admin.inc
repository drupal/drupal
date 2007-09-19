<?php
// $Id: taxonomy.admin.inc,v 1.4 2007/09/19 19:02:57 goba Exp $

/**
 * @file
 * Administrative page callbacks for the taxonomy module.
 */

/**
 * List and manage vocabularies.
 */
function taxonomy_overview_vocabularies() {
  $vocabularies = taxonomy_get_vocabularies();
  $rows = array();
  foreach ($vocabularies as $vocabulary) {
    $types = array();
    foreach ($vocabulary->nodes as $type) {
      $node_type = node_get_types('name', $type);
      $types[] = $node_type ? $node_type : $type;
    }
    $rows[] = array('name' => check_plain($vocabulary->name),
      'type' => implode(', ', $types),
      'edit' => l(t('edit vocabulary'), "admin/content/taxonomy/edit/vocabulary/$vocabulary->vid"),
      'list' => l(t('list terms'), "admin/content/taxonomy/$vocabulary->vid"),
      'add' => l(t('add terms'), "admin/content/taxonomy/$vocabulary->vid/add/term")
    );
  }
  if (empty($rows)) {
    $rows[] = array(array('data' => t('No categories available.'), 'colspan' => '5'));
  }
  $header = array(t('Name'), t('Type'), array('data' => t('Operations'), 'colspan' => '3'));

  return theme('table', $header, $rows, array('id' => 'taxonomy'));
}

/**
 * Display form for adding and editing vocabularies.
 *
 * @ingroup forms
 * @see taxonomy_form_vocabulary_submit().
 */
function taxonomy_form_vocabulary(&$form_state, $edit = array()) {
  $edit += array(
    'name' => '',
    'description' => '',
    'help' => '',
    'nodes' => array(),
    'hierarchy' => 0,
    'relations' => 0,
    'tags' => 0,
    'multiple' => 0,
    'required' => 0,
    'weight' => 0,
  );
  $form['name'] = array('#type' => 'textfield',
    '#title' => t('Vocabulary name'),
    '#default_value' => $edit['name'],
    '#maxlength' => 255,
    '#description' => t('The name for this vocabulary. Example: "Topic".'),
    '#required' => TRUE,
  );
  $form['description'] = array('#type' => 'textarea',
    '#title' => t('Description'),
    '#default_value' => $edit['description'],
    '#description' => t('Description of the vocabulary; can be used by modules.'),
  );
  $form['help'] = array('#type' => 'textfield',
    '#title' => t('Help text'),
    '#maxlength' => 255,
    '#default_value' => $edit['help'],
    '#description' => t('Instructions to present to the user when choosing a term.'),
  );
  $form['nodes'] = array('#type' => 'checkboxes',
    '#title' => t('Content types'),
    '#default_value' => $edit['nodes'],
    '#options' => node_get_types('names'),
    '#description' => t('A list of content types you would like to categorize using this vocabulary.'),
  );
  $form['hierarchy'] = array('#type' => 'radios',
    '#title' => t('Hierarchy'),
    '#default_value' => $edit['hierarchy'],
    '#options' => array(t('Disabled'), t('Single'), t('Multiple')),
    '#description' => t('Allows <a href="@help-url">a tree-like hierarchy</a> between terms of this vocabulary.', array('@help-url' => url('admin/help/taxonomy', array('absolute' => TRUE)))),
  );
  $form['relations'] = array('#type' => 'checkbox',
    '#title' => t('Related terms'),
    '#default_value' => $edit['relations'],
    '#description' => t('Allows <a href="@help-url">related terms</a> in this vocabulary.', array('@help-url' => url('admin/help/taxonomy', array('absolute' => TRUE)))),
  );
  $form['tags'] = array('#type' => 'checkbox',
    '#title' => t('Free tagging'),
    '#default_value' => $edit['tags'],
    '#description' => t('Content is categorized by typing terms instead of choosing from a list.'),
  );
  $form['multiple'] = array('#type' => 'checkbox',
    '#title' => t('Multiple select'),
    '#default_value' => $edit['multiple'],
    '#description' => t('Allows nodes to have more than one term from this vocabulary (always true for free tagging).'),
  );
  $form['required'] = array('#type' => 'checkbox',
    '#title' => t('Required'),
    '#default_value' => $edit['required'],
    '#description' => t('If enabled, every node <strong>must</strong> have at least one term in this vocabulary.'),
  );
  $form['weight'] = array('#type' => 'weight',
    '#title' => t('Weight'),
    '#default_value' => $edit['weight'],
    '#description' => t('In listings, the heavier vocabularies will sink and the lighter vocabularies will be positioned nearer the top.'),
  );

  $form['submit'] = array('#type' => 'submit', '#value' => t('Save'));
  if (isset($edit['vid'])) {
    $form['delete'] = array('#type' => 'submit', '#value' => t('Delete'));
    $form['vid'] = array('#type' => 'value', '#value' => $edit['vid']);
    $form['module'] = array('#type' => 'value', '#value' => $edit['module']);
  }
  return $form;
}

/**
 * Accept the form submission for a vocabulary and save the results.
 */
function taxonomy_form_vocabulary_submit($form, &$form_state) {
  // Fix up the nodes array to remove unchecked nodes.
  $form_state['values']['nodes'] = array_filter($form_state['values']['nodes']);
  switch (taxonomy_save_vocabulary($form_state['values'])) {
    case SAVED_NEW:
      drupal_set_message(t('Created new vocabulary %name.', array('%name' => $form_state['values']['name'])));
      watchdog('taxonomy', 'Created new vocabulary %name.', array('%name' => $form_state['values']['name']), WATCHDOG_NOTICE, l(t('edit'), 'admin/content/taxonomy/edit/vocabulary/'. $form_state['values']['vid']));
      break;
    case SAVED_UPDATED:
      drupal_set_message(t('Updated vocabulary %name.', array('%name' => $form_state['values']['name'])));
      watchdog('taxonomy', 'Updated vocabulary %name.', array('%name' => $form_state['values']['name']), WATCHDOG_NOTICE, l(t('edit'), 'admin/content/taxonomy/edit/vocabulary/'. $form_state['values']['vid']));
      break;
  }

  $form_state['vid'] = $form_state['values']['vid'];
  $form_state['redirect'] = 'admin/content/taxonomy';
  return;
}

/**
 * Page to edit a vocabulary.
 */
function taxonomy_admin_vocabulary_edit($vocabulary) {
  if ((isset($_POST['op']) && $_POST['op'] == t('Delete')) || isset($_POST['confirm'])) {
    return drupal_get_form('taxonomy_vocabulary_confirm_delete', $vocabulary->vid);
  }
  return drupal_get_form('taxonomy_form_vocabulary', (array)$vocabulary);
}

/**
 * Page to edit a vocabulary term.
 */
function taxonomy_admin_term_edit($tid) {
  if ((isset($_POST['op']) && $_POST['op'] == t('Delete')) || isset($_POST['confirm'])) {
    return drupal_get_form('taxonomy_term_confirm_delete', $tid);
  }
  if ($term = (array)taxonomy_get_term($tid)) {
    return drupal_get_form('taxonomy_form_term', taxonomy_vocabulary_load($term['vid']), $term);
  }
  return drupal_not_found();
}

/**
 * Display a tree of all the terms in a vocabulary, with options to edit
 * each one.
 */
function taxonomy_overview_terms($vocabulary) {
  $destination = drupal_get_destination();

  $header = array(t('Name'), t('Operations'));

  drupal_set_title(t('Terms in %vocabulary', array('%vocabulary' => $vocabulary->name)));
  $start_from      = isset($_GET['page']) ? $_GET['page'] : 0;
  $total_entries   = 0;  // total count for pager
  $page_increment  = 25; // number of tids per page
  $displayed_count = 0;  // number of tids shown

  if ($vocabulary->tags) {
    // We are not calling taxonomy_get_tree because that might fail with a big
    // number of tags in the freetagging vocabulary.
    $results = pager_query(db_rewrite_sql('SELECT t.*, h.parent FROM {term_data} t INNER JOIN  {term_hierarchy} h ON t.tid = h.tid WHERE t.vid = %d ORDER BY weight, name', 't', 'tid'), $page_increment, 0, NULL, $vid);
    while ($term = db_fetch_object($results)) {
      $rows[] = array(
        l($term->name, "taxonomy/term/$term->tid"),
        l(t('edit'), "admin/content/taxonomy/edit/term/$term->tid", array('query' => $destination)),
      );
    }
  }
  else {
    $tree = taxonomy_get_tree($vocabulary->vid);
    foreach ($tree as $term) {
      $total_entries++; // we're counting all-totals, not displayed
      if (($start_from && ($start_from * $page_increment) >= $total_entries) || ($displayed_count == $page_increment)) {
        continue;
      }
      $rows[] = array(str_repeat('--', $term->depth) .' '. l($term->name, "taxonomy/term/$term->tid"), l(t('edit'), "admin/content/taxonomy/edit/term/$term->tid", array('query' => $destination)));
      $displayed_count++; // we're counting tids displayed
    }

    if (empty($rows)) {
      $rows[] = array(array('data' => t('No terms available.'), 'colspan' => '2'));
    }

    $GLOBALS['pager_page_array'][] = $start_from;  // FIXME
    $GLOBALS['pager_total'][] = intval($total_entries / $page_increment) + 1; // FIXME
  }

  $output .= theme('table', $header, $rows, array('id' => 'taxonomy'));
  if ($vocabulary->tags || $total_entries >= $page_increment) {
    $output .= theme('pager', NULL, $page_increment);
  }

  return $output;
}


/**
 * Menu callback; return the edit form for a new term after setting the title.
 */
function taxonomy_add_term_page($vocabulary) {
  drupal_set_title(t('Add term to %vocabulary', array('%vocabulary' => $vocabulary->name)));
  return drupal_get_form('taxonomy_form_term' , $vocabulary);
}

/**
 * Form function for the term edit form.
 *
 * @ingroup forms
 * @see taxonomy_form_term_submit().
 */
function taxonomy_form_term(&$form_state, $vocabulary, $edit = array()) {
  $edit += array(
    'name' => '',
    'description' => '',
    'tid' => NULL,
    'weight' => 0,
  );
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Term name'),
    '#default_value' => $edit['name'],
    '#maxlength' => 255,
    '#description' => t('The name of this term.'),
    '#required' => TRUE);

  $form['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
    '#default_value' => $edit['description'],
    '#description' => t('A description of the term.'));

  if ($vocabulary->hierarchy) {
    $parent = array_keys(taxonomy_get_parents($edit['tid']));
    $children = taxonomy_get_tree($vocabulary->vid, $edit['tid']);

    // A term can't be the child of itself, nor of its children.
    foreach ($children as $child) {
      $exclude[] = $child->tid;
    }
    $exclude[] = $edit['tid'];

    if ($vocabulary->hierarchy == 1) {
      $form['parent'] = _taxonomy_term_select(t('Parent'), 'parent', $parent, $vocabulary->vid, l(t('Parent term'), 'admin/help/taxonomy', array('fragment' => 'parent')) .'.', 0, '<'. t('root') .'>', $exclude);
    }
    elseif ($vocabulary->hierarchy == 2) {
      $form['parent'] = _taxonomy_term_select(t('Parents'), 'parent', $parent, $vocabulary->vid, l(t('Parent terms'), 'admin/help/taxonomy', array('fragment' => 'parent')) .'.', 1, '<'. t('root') .'>', $exclude);
    }
  }

  if ($vocabulary->relations) {
    $form['relations'] = _taxonomy_term_select(t('Related terms'), 'relations', array_keys(taxonomy_get_related($edit['tid'])), $vocabulary->vid, NULL, 1, '<'. t('none') .'>', array($edit['tid']));
  }

  $form['synonyms'] = array(
    '#type' => 'textarea',
    '#title' => t('Synonyms'),
    '#default_value' => implode("\n", taxonomy_get_synonyms($edit['tid'])),
    '#description' => t('<a href="@help-url">Synonyms</a> of this term, one synonym per line.', array('@help-url' => url('admin/help/taxonomy', array('absolute' => TRUE)))));
  $form['weight'] = array(
    '#type' => 'weight',
    '#title' => t('Weight'),
    '#default_value' => $edit['weight'],
    '#description' => t('In listings, the heavier terms will sink and the lighter terms will be positioned nearer the top.'));
  $form['vid'] = array(
    '#type' => 'value',
    '#value' => $vocabulary->vid);
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'));

  if ($edit['tid']) {
    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'));
    $form['tid'] = array(
      '#type' => 'value',
      '#value' => $edit['tid']);
  }
  else {
    $form['destination'] = array('#type' => 'hidden', '#value' => $_GET['q']);
  }

  return $form;
}

/**
 * Accept the form submission for a taxonomy term and save the result.
 */
function taxonomy_form_term_submit($form, &$form_state) {
  switch (taxonomy_save_term($form_state['values'])) {
    case SAVED_NEW:
      drupal_set_message(t('Created new term %term.', array('%term' => $form_state['values']['name'])));
      watchdog('taxonomy', 'Created new term %term.', array('%term' => $form_state['values']['name']), WATCHDOG_NOTICE, l(t('edit'), 'admin/content/taxonomy/edit/term/'. $form_state['values']['tid']));
      break;
    case SAVED_UPDATED:
      drupal_set_message(t('Updated term %term.', array('%term' => $form_state['values']['name'])));
      watchdog('taxonomy', 'Updated term %term.', array('%term' => $form_state['values']['name']), WATCHDOG_NOTICE, l(t('edit'), 'admin/content/taxonomy/edit/term/'. $form_state['values']['tid']));
      break;
  }

  $form_state['tid'] = $form_state['values']['tid'];
  $form_state['redirect'] = 'admin/content/taxonomy';
  return;
}

/**
 * Form builder for the term delete form.
 *
 * @ingroup forms
 * @see taxonomy_term_confirm_delete_submit().
 */
function taxonomy_term_confirm_delete(&$form_state, $tid) {
  $term = taxonomy_get_term($tid);

  $form['type'] = array('#type' => 'value', '#value' => 'term');
  $form['name'] = array('#type' => 'value', '#value' => $term->name);
  $form['tid'] = array('#type' => 'value', '#value' => $tid);
  return confirm_form($form,
                  t('Are you sure you want to delete the term %title?',
                  array('%title' => $term->name)),
                  'admin/content/taxonomy',
                  t('Deleting a term will delete all its children if there are any. This action cannot be undone.'),
                  t('Delete'),
                  t('Cancel'));
}

function taxonomy_term_confirm_delete_submit($form, &$form_state) {
  taxonomy_del_term($form_state['values']['tid']);
  drupal_set_message(t('Deleted term %name.', array('%name' => $form_state['values']['name'])));
  watchdog('taxonomy', 'Deleted term %name.', array('%name' => $form_state['values']['name']), WATCHDOG_NOTICE);
  $form_state['redirect'] = 'admin/content/taxonomy';
  return;
}

/**
 * Form builder for the vocabulary delete confirmation form.
 *
 * @ingroup forms
 * @see taxonomy_vocabulary_confirm_delete_submit().
 */
function taxonomy_vocabulary_confirm_delete(&$form_state, $vid) {
  $vocabulary = taxonomy_vocabulary_load($vid);

  $form['type'] = array('#type' => 'value', '#value' => 'vocabulary');
  $form['vid'] = array('#type' => 'value', '#value' => $vid);
  $form['name'] = array('#type' => 'value', '#value' => $vocabulary->name);
  return confirm_form($form,
                  t('Are you sure you want to delete the vocabulary %title?',
                  array('%title' => $vocabulary->name)),
                  'admin/content/taxonomy',
                  t('Deleting a vocabulary will delete all the terms in it. This action cannot be undone.'),
                  t('Delete'),
                  t('Cancel'));
}

function taxonomy_vocabulary_confirm_delete_submit($form, &$form_state) {
  $status = taxonomy_del_vocabulary($form_state['values']['vid']);
  drupal_set_message(t('Deleted vocabulary %name.', array('%name' => $form_state['values']['name'])));
  watchdog('taxonomy', 'Deleted vocabulary %name.', array('%name' => $form_state['values']['name']), WATCHDOG_NOTICE);
  $form_state['redirect'] = 'admin/content/taxonomy';
  return;
}
